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
                'hint' => 'Add tags seperated by a space')
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
        $tags = array_filter(array_unique(explode(' ', $values['tags'])),
            function($el){  if (!empty($el))    return true;    });
            
        foreach($tags as $t)
            IssueTag::create(array('issue_id' => $i->id, 'tag' => $t));
            
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
