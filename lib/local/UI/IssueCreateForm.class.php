<?php


class UI_IssueCreateForm extends Output_HTML_Form
{
    private $project;

    public function __construct($project = null)
    {
        $this->project = $project;
        $assignees = array_merge(
            array('' => '-- Unassigned --'),
            Membership::get_users('dev')
        );
        
        $fields = array(
            'title' => array('display' => 'Title', 'regcheck' => '/^.{3,}$/'),
            'description' => array('display' => 'Description', 'type' => 'textarea'),
            'project' => array('display' => 'Project', 'type' => 'dropbox', 'mustselect' => true,
                'onerror' => 'You must select a project for your issue.'),
            'more' => array('type' => 'custom', 'value' => '<a class="ui-form-more">More ...</a>'),
            'assignee' => array('display' => 'Assigned to', 'type' => 'dropbox', 'optionlist' => $assignees,
                'mustselect' => false),
            'tags' => array('display' => 'Tags',
                'regcheck' => '/^([\w\-]+(?:(?:\s(?!$))?))*$/',
                'onerror' => 'Tags must be seperated with a space',
                'hint' => 'Add tags seperated by a space'),
            'attachment1' => array('display' => 'Attachment', 'type' => 'file',
                'htmlattribs' => array('class' => 'ui-form-attachments-start')),
            'attachment2' => array('display' => '', 'type' => 'file'),
            'attachment3' => array('display' => '', 'type' => 'file'),
            'attachment4' => array('display' => '', 'type' => 'file'),
            'attachment5' => array('display' => '', 'type' => 'file'),
            'attachment6' => array('display' => '', 'type' => 'file'),
            'attachment7' => array('display' => '', 'type' => 'file'),
            'attachment8' => array('display' => '', 'type' => 'file'),
            'attachment9' => array('display' => '', 'type' => 'file'),
            'attachment10' => array('display' => '', 'type' => 'file'),
        );
        if ($project === null)
        {
            $projects = array('' => '-- Select one --');
            foreach(Project::raw_query()->select(array('name', 'title'))->execute() as $p)
                $projects[$p['name']] = $p['title'];
            $fields['project']['optionlist'] = $projects;
        }
        else
            unset($fields['project']);

        parent::__construct(
            $fields,
            array(
                'title' => 'Report a new issue',
                'buttons' => array(
                    'Post' => array(),                    
                    'Cancel' => array('type' => 'button', 'onclick' => 
                        "window.location='" . ($this->project?
                            UrlFactory::craft('project.view', $this->project):
                            UrlFactory::craft('projects')) .
                        "'"
                    )
                )
            )
        );
    }
    
    public function on_valid($values)
    {
        if (($this->project === null) && (!($this->project = Project::open($values['project']))))
        {
            $this->invalidate_field('project', 'You must select a valid project');
            return;
        }
        
        $values['poster'] = Authn_Realm::get_identity()->id();
        $values['project_name'] = $this->project->name;
        $values['status'] = Config::get('issue.default_status');
        $values['created'] = new DateTime();
        
        // Create issue
        if (!($i = Issue::create($values)))
        {
            $this->invalidate_field('title','Error posting new issue.');
            return false;
        }
        
        // Add tags
        $tags = array_filter(array_unique(explode(' ', strtolower($values['tags']))),
            function($el){  if (!empty($el))    return true;    });
            
        foreach($tags as $t)
            IssueTag::create(array('issue_id' => $i->id, 'tag' => $t));
            
        // Add attachments
        $valid_attachments = array();
        for($k = 1;$k <= 10;$k++)
            if ($values["attachment{$k}"])
                $valid_attachments["attachment{$k}"] = $values["attachment{$k}"];

        if (!empty($valid_attachments))
        {   // Create a new empty comment to add attachments on
           $action = $i->action_comment(
                Authn_Realm::get_identity()->id(),
                new DateTime(),
                ''
            );
            foreach($valid_attachments as $attach)
                $action->save_attachment($attach);
        }
        // Send mail
        $mail = new MailerIssue($i,
            UserProfile::open($values['poster'])->fullname . " posted a new issue...\n" .
            "------------------------------------------------\n\n" .
            $i->description);
        $mail->send();

        // Update counters
        $i->project->update_counters();
        UrlFactory::craft('issue.view', $i)->redirect();
    }
}
?>
