<?php

class UI_ProjectCreateForm extends Output_HTML_Form
{

    public function __construct()
    {
        $devs = Membership::get_users('dev');
        parent::__construct(
            array(
                'name' => array('display' => 'Unique name', 'regcheck' => '/^\w{3,}$/',
                    'onerror' => 'Unique name must be a 3 letters at least word.'),
                'title' => array('display' => 'Title', 'regcheck' => '/^.{3,}$/'),
                'description' => array('display' => 'Description', 'type' => 'textarea',
                    'onerror' => 'You must add description on project.'),
                'manager' => array('display' => 'Supervisor', 'type' => 'dropbox', 'optionlist' => $devs,
                    'mustselect' => false),
            ),
            array(
                'title' => 'Create new project',
                'buttons' => array(
                    'Create' => array(),
                    'Cancel' => array('type' => 'button', 'onclick' => 
                        "window.location='" . UrlFactory::craft('projects') . "'")
                )
            )
        );
    }
    
    public function on_valid($values)
    {
        $values['created'] = new DateTime();
        $p = Project::create($values);
        
        UrlFactory::craft('project.view', $p)->redirect();
    }
}

?>
