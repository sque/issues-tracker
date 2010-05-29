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
        etag('h1', 'User: ', tag('em', $p->fullname));
        etag('dl',
            tag('dt', 'Nickname:',
              tag('dd', $p->username)),
            tag('dt', 'Email:',
              tag('dd', $p->email))
        );
    }
    else
        etag('h1', $user);
    
    // TODO List
    etag('h2', 'Assigned issues');
    $grid = new UI_IssuesGrid(Issue::open_query()
        ->where('assignee = ?')
        ->order_by('created', 'DESC')
        ->execute($user));
    etag('div', $grid->render());
    
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

    etag('h2', 'Involved issues');
    $grid = new UI_IssuesGrid($f_issues);
    etag('div', $grid->render());
}


?>
