<?php

class UI_ProjectEditForm extends Output_HTML_Form
{

    public function __construct($p)
    {
        $this->project = $p;
        
        $devs = Membership::get_users('dev');
        
        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'regcheck' => '/^.{3,}$/',
                    'value' => $p->title),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'onerror' => 'You must add description on project.',
                    'value' => $p->description),
                'manager' => array('display' => 'Supervisor', 'type' => 'dropbox', 'optionlist' => $devs,
                    'value' => $p->manager, 'mustselect' => false),
            ),
            array(
                'buttons' => array(
                    'Save' => array(),
                    'Cancel' => array('type' => 'button', 'onclick' => 
                        "window.location='" . UrlFactory::craft('project.view', $this->project) . "'")
                )
            )
        );
    }
    
    public function on_valid($values)
    {
        $this->project->title = $values['title'];
        $this->project->description = $values['description'];
        $this->project->manager = $values['manager'];
        $this->project->save();
        
        UrlFactory::craft('project.view', $this->project)->redirect();
    }
}

?>
