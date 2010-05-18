<?php

class UI_ProjectEditForm extends Output_HTML_Form
{

    public function __construct($p)
    {
        $this->project = $p;
        
        parent::__construct(
            array(
                'title' => array('display' => 'Title', 'regcheck' => '/^.{3,}$/',
                    'value' => $p->title),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'onerror' => 'You must add description on project.',
                    'value' => $p->description
                 )
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
        $this->project->save();
        
        UrlFactory::craft('project.view', $this->project)->redirect();
    }
}

?>
