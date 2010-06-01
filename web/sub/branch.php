<?php

Stupid::add_rule('view_home_branch',
    array('type' => 'url_path', 'chunk[2]' => '/^~(.+)$/'),
    array('type' => 'authz', 'resource' => 'branch', 'instance' => 'private', 'action' => 'view'));

Stupid::add_rule('view_pub_branch',
    array('type' => 'url_path', 'chunk[2]' => '/^pub$/'),
    array('type' => 'authz', 'resource' => 'branch', 'instance' => 'pub', 'action' => 'view'));

Stupid::add_rule('direct_proxy_url',
    array('type' => 'url_path', 'chunk[2]' => '/^static$/'));

Stupid::set_default_action('branch_explorer');
Stupid::chain_reaction();

function view_home_branch($user)
{
    if ($user !== Authn_Realm::get_identity()->id())
        not_found();

    direct_proxy_url();
}

function view_pub_branch()
{
    $proxy = new ReverseProxyHandler(url('/branch/pub/') ,Config::get('loggerhead.url'));
    $proxy->execute();
}

function direct_proxy_url()
{
    $proxy = new ReverseProxyHandler(url('/branch') ,Config::get('loggerhead.url'));
    $proxy->execute();
}

function branch_explorer()
{
    // Use default layout to render this page
    Layout::open('default')->activate();
    Layout::open('default')->get_document()->title = 'Branches | Issues Tracker';
    
    etag('h1', 'Branches');
    etag('ul class="branches"')->push_parent();
    
    etag('li',
        tag('a', array('href' => url('/branch/~' . Authn_Realm::get_identity()->id() . '/')),
            'Private'),
        tag('p', 'Your own private repository of branches located in your home folder.')
    );
    etag('li',
        tag('a', array('href' => url('/branch/pub/')),
            'Public'),
        tag('p', 'The public repository for all developpers.')
    );

    Output_HTMLTag::pop_parent();
}
?>
