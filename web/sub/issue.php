<?php
// Use default layout to render this page
Layout::open('default')->activate();

// Redirect on issue id
Stupid::add_rule(
    function()
    {
        $bc = new SmartMenu(array('class' => 'breadcrumb'));
        $bc->create_link('Projects', UrlFactory::craft('projects'));
        $bc->create_link('Report issue', UrlFactory::craft('issue.create'));
        $frm = new UI_IssueCreateForm();
        
        etag('div', $bc->render());
        etag('div', $frm->render());
    },
    array('type' => 'url_path', 'chunk[2]' => '/^\+create$/'),
    array('type' => 'authz', 'resource' => 'project', 'action' => 'post-issue')
);

Stupid::add_rule(
    function($issue_id)
    {
        if (!($i = Issue::open($issue_id)))
            not_found();
            
        UrlFactory::craft('issue.view', $i)->redirect();
    },
    array('type' => 'url_path', 'chunk[2]' => '/^(?P<issue_id>[\d]+)$/'),
    array('type' => 'authz', 'resource' => 'issue', 'backref_instance' => 'issue_id', 'action' => 'view')
);

Stupid::set_default_action('not_found');
Stupid::chain_reaction();

?>
