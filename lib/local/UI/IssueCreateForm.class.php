<?php


class UI_IssueCreateForm extends Output_HTML_Form
{
    private $project;

    public function __construct($project)
    {
        $this->project = $project;

        $assignees = array_merge(
            array('' => '-- Unassigned --'),
            Membership::get_users('dev')
        );
        
        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'regcheck' => '/^.{3,}$/'),
                'description' => array('display' => 'Description', 'type' => 'textarea'),
                'assignee' => array('display' => 'Assigned to', 'type' => 'dropbox', 'optionlist' => $assignees,
                    'mustselect' => false),
                'tags' => array('display' => 'Tags',
                    'regcheck' => '/^([\w\-]+(?:(?:\s(?!$))?))*$/',
                    'onerror' => 'Tags must be seperated with a space',
                    'hint' => 'Add tags seperated by a space')
            ),
            array(
                'title' => 'Create a new issue',
                'buttons' => array(
                    'Post' => array(),
                    'Cancel' => array('type' => 'button', 'onclick' => 
                        "window.location='" . UrlFactory::craft('project.view', $this->project) . "'")
                )
            )
        );
    }
    
    public function on_valid($values)
    {
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
        $mail = new MailerIssue($i, Authn_Realm::get_identity()->id() . " posted a new issue\n\n" . $i->description);
        $mail->send();

        // Update counters
        $i->project->update_counters();
        UrlFactory::craft('issue.view', $i)->redirect();
    }
}
?>
