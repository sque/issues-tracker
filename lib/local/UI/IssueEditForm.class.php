<?php


class UI_IssueEditForm extends Output_HTML_Form
{
    private $project;

    private $issue;
    
    public function __construct($project, $issue = null)
    {
        $this->project = $project;
        $this->issue = $issue;
            
        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'recheck' => '/^.{3,}$/s', 'value' => $this->issue->title),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'regcheck' => '/^.+$/s',
                    'onerror' => 'You must add description on issue.',
                    'value' => $this->issue->description
                 ),
                'tags' => array('display' => 'Tags', 'hint' => 'Add tags seperated by a space',
                    'regcheck' => '/^([\w\-]+(?:(?:\s(?!$))?))+$/',
                    'onerror' => 'Tags must be seperated with a space',
                    'hint' => 'Add tags seperated by a space',
                    'value' => implode(' ' , $this->issue->tag_names()))
            ),
            array(
                'buttons' => array(
                    'Save' => array(),
                    'Cancel' => array('type' => 'button', 'onclick' => 
                        "window.location='" . UrlFactory::craft('issue.view', $issue) . "'")
                )
            )
        );
    }
    
    public function on_valid($values)
    {
        $this->issue->title = $values['title'];
        $this->issue->description = $values['description'];
        $this->issue->save();
        
        
        // Add tags
        $tags = explode(' ', $values['tags']);
        
        $added_tags = array_diff($tags, $this->issue->tag_names());
        $removed_tags = array_diff($this->issue->tag_names(), $tags);
        var_dump('Adding ', $added_tags);
        var_dump('Removed ', $removed_tags);
        
        foreach($added_tags as $t)
            $this->issue->action_add_tag(Auth_Realm::get_identity(), new DateTime(), $t);

        foreach($removed_tags as $t)
            $this->issue->action_remove_tag(Auth_Realm::get_identity(), new DateTime(), $t);
        UrlFactory::open('issue.view')->redirect();
    }
}
?>
