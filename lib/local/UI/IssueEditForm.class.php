<?php


class UI_IssueEditForm extends Output_HTML_Form
{
    private $project;

    private $issue;
    
    public function __construct($project, $issue = null)
    {
        $this->project = $project;
        $this->issue = $issue;

        $assignees = array_merge(
            array('' => '-- Unassigned --'),
            Membership::get_users('dev')
        );

        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'recheck' => '/^.{3,}$/s', 'value' => $this->issue->title),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'regcheck' => '/^.+$/s',
                    'onerror' => 'You must add description on issue.',
                    'value' => $this->issue->description
                 ),
                'assignee' => array('display' => 'Assigned to', 'type' => 'dropbox', 'optionlist' => $assignees,
                    'value' => $this->issue->assignee, 'mustselect' => false),
                'tags' => array('display' => 'Tags', 'hint' => 'Add tags seperated by a space',
                    'regcheck' => '/^([\w\-]+(?:(?:\s(?!$))?))*$/',
                    'onerror' => 'Tags must be seperated with a space',
                    'hint' => 'Add tags seperated by a space',
                    'value' => implode(' ' , $this->issue->tag_names()))
            ),
            array(
                'title' => 'Edit Issue',
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
        // Detail changes
        $this->issue->action_edit(Authn_Realm::get_identity()->id(),
            new DateTime(), $values['title'], $values['description']);        
        
        // Assigne changes
        $this->issue->assignee = $values['assignee'];
        $this->issue->save();

        // Tag Changes
        $tags = array_filter(array_unique(explode(' ', $values['tags'])),
            function($el){  if (!empty($el))    return true;    });
        
        $added_tags = array_diff($tags, $this->issue->tag_names());
        $removed_tags = array_diff($this->issue->tag_names(), $tags);
        
        foreach($added_tags as $t)
            $this->issue->action_add_tag(Authn_Realm::get_identity()->id(), new DateTime(), $t);

        foreach($removed_tags as $t)
            $this->issue->action_remove_tag(Authn_Realm::get_identity()->id(), new DateTime(), $t);
        
        UrlFactory::craft('issue.view', $this->issue)->redirect();
    }
}
?>
