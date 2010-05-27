<?php
// Use default layout to render this page
Layout::open('default')->activate();

// Redirect

Stupid::add_rule(
function($issue_id)
{
    if (!($i = Issue::open($issue_id)))
        not_found();
        
    UrlFactory::craft('issue.view', $i)->redirect();
},
array('type' => 'url_path', 'chunk[2]' => '/^(?P<issue_id>[\d]+)$/'),
array('type' => 'authz', 'resource' => 'project', 'backref_instance' => 'issue_id', 'action' => 'view'));

Stupid::set_default_action('not_found');
Stupid::chain_reaction();

?>
