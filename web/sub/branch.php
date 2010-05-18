<?php



Stupid::add_rule('view_home_branch',
    array('type' => 'url_path', 'chunk[2]' => '/^~(.+)$/'));
Stupid::add_rule('direct_proxy_url',
    array('type' => 'url_path', 'chunk[2]' => '/^static$/'));
Stupid::set_default_action('branch_explorer');
Stupid::chain_reaction();

function view_home_branch($user)
{
    if ($user !== Auth_Realm::get_identity()->id())
        not_found();

    direct_proxy_url();
}

function direct_proxy_url()
{
    $proxy = new ProxyHandler('',Config::get('loggerhead.url'));
    $proxy->execute();

}

function branch_explorer()
{
    // Use default layout to render this page
    Layout::open('default')->activate();
    
    etag('h1', 'Branches');
    etag('ul class="branches"')->push_parent();
    
    etag('li',
        tag('a', array('href' => url('/branch/~' . Auth_Realm::get_identity()->id() . '/')),
            'Private')
    );
    Output_HTMLTag::pop_parent();
}
?>
