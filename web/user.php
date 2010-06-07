<?php

Stupid::add_rule('edit_user',
    array('type' => 'url_path', 'chunk[1]' => '/^~(.+)$/', 'chunk[2]' => '/^\+edit$/'),
    array('type' => 'authz', 'resource' => 'userprofile', 'action' => 'edit'));
Stupid::add_rule('view_user',
    array('type' => 'url_path', 'chunk[1]' => '/^~(.+)$/'),
    array('type' => 'authz', 'resource' => 'userprofile', 'action' => 'view'));
Stupid::set_default_action('not_found');
Stupid::chain_reaction();

function view_user($user)
{
    Layout::open('default')->activate();
    if ($p = UserProfile::open($user))
    {
        Layout::open('default')->get_document()->title = $p->fullname;
        etag('h1 class="user"', $p->fullname);
        etag('dl',
            tag('dt', 'Nickname:',
              tag('dd', $p->username)),
            tag('dt', 'Email:',
              tag('dd', $p->email))
        );
    }
    else
        etag('h1', $user);
    
    // Open assigned issues List
    $issues = Issue::open_query()
        ->where_in('status', array('new', 'accepted'))
        ->order_by('status = ?', 'DECS')
        ->where('assignee = ?')
        ->order_by('created', 'DESC')
        ->execute($user, 'accepted');
    if (!empty($issues))
    {
        etag('h2', 'Open assigned issues');
        $grid = new UI_IssuesGrid($issues);
        etag('div', $grid->render());
    }
    
    // Show all related issues
    $issues = Issue::open_query()
        ->where('poster = ?')
        ->where('assignee = ?', 'OR')
        ->order_by('created', 'DESC')
        ->execute($user, $user);

    $issues = array_merge($issues, 
        Issue::open_query()
        ->left_join('IssueAction', 'id', 'issue_id')
        ->where('l.actor = ?')
        ->execute($user));

    $f_issues = array();
    foreach($issues as $i)
        $f_issues[$i->id] = $i;
    $f_issues = array_values($f_issues);

    if (!empty($f_issues))
    {
        etag('h2', 'Watch list');
        $grid = new UI_IssuesGrid($f_issues);
        etag('div', $grid->render());
    }
}

?>
