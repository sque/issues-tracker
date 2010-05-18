<?php


class UI_IssueCreateForm extends Output_HTML_Form
{
    private $project;

    public function __construct($project)
    {
        $this->project = $project;
            
        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'recheck' => '/^.{3,}$/'),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'onerror' => 'You must add description on issue.'
                 ),
                'tags' => array('display' => 'Tags',
                    'regcheck' => '/^(\w+(?:(?:\s(?!$))?))+$/',
                    'onerror' => 'Tags must be seperated with a space',
                    'hint' => 'Add tags seperated by a space')
            ),
            array(
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
        $values['poster'] = Auth_Realm::get_identity();
        $values['project_name'] = $this->project->name;
        $values['created'] = new DateTime();
        
        // Create issue
        if (!($i = Issue::create($values)))
        {
            $this->invalidate_field('title','Error posting new issue.');
            return false;
        }
        
        // Add tags
        $tags = explode(' ', $values['tags']);
        foreach($tags as $t)
            if (!empty($t))
                IssueTag::create(array('issue_id' => $i->id, 'tag' => $t));
        
        UrlFactory::craft('issue.view', $i)->redirect();
    }
}
?>
