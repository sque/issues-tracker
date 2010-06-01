<?php
// Use default layout to render this page
Layout::open('default')->activate();

Stupid::add_rule('edit_issue',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+issue$/',
            'chunk[4]' => '/^(?P<issue_id>[\d]+)$/', 'chunk[5]' => '/^\+edit$/'),
    array('type' => 'authz', 'resource' => 'issue', 'backref_instance' => 'issue_id', 'action' => 'edit'));

Stupid::add_rule('show_issue',
    array('type' => 'url_path',
        'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+issue$/', 'chunk[4]' => '/^(?P<issue_id>[\d]+)$/'),
    array('type' => 'authz', 'resource' => 'issue', 'backref_instance' => 'issue_id', 'action' => 'view'));

Stupid::add_rule('show_tag',
    array('type' => 'url_path',
        'chunk[2]' => '/^(?P<project_id>[^\+].+)$/', 'chunk[3]' => '/^\+tag$/', 'chunk[4]' => '/^(?P<tag>[\w\-]+)$/'),
    array('type' => 'authz', 'resource' => 'project', 'backref_instance' => 'project_id', 'action' => 'view'));
    
Stupid::add_rule('create_issue',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+createissue$/'),
    array('type' => 'authz', 'resource' => 'project', 'action' => 'post-issue'));
        
Stupid::add_rule('edit_project',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+edit$/'),
    array('type' => 'authz', 'resource' => 'project', 'action' => 'edit')
);
Stupid::add_rule('show_project',
    array('type' => 'url_path', 'chunk[2]' => '/^(?P<project_id>[^\+].+)$/'),
    array('type' => 'authz', 'resource' => 'project', 'backref_instance' => 'project_id', 'action' => 'view')
);
Stupid::add_rule('create_project',
    array('type' => 'url_path', 'chunk[2]' => '/^\+create$/'),
    array('type' => 'authz', 'resource' => 'project', 'action' => 'create')
);
Stupid::set_default_action('default_projects');
Stupid::chain_reaction();

function project_breadcrumb($project = null, $issue = null)
{
    $bc = new SmartMenu(array('class' => 'breadcrumb'));
    $bc->create_link('Projects', UrlFactory::craft('projects'));
    if ($project)
        $bc->create_link($project->title, UrlFactory::craft('project.view', $project), null, array('class' => 'project'));
    if ($issue)
        $bc->create_link("Issue #{$issue->id}", UrlFactory::craft('issue.view', $issue), null, array('class' => 'issue'));
    
    return $bc;
}

function get_submenu()
{
    return Layout::open('default')->get_submenu();
}

function show_tag($pname, $tagname)
{
    if (!($p = Project::open($pname)))
        not_found();
        
    $bc = project_breadcrumb($p);
    $bc->create_link($tagname,
        UrlFactory::craft('project.tag', $p, $tagname),
        null,
        array('class' => 'tag')
    );
    
    etag('h1', $p->title);
    etag('div', $bc->render());
    
    $issues = $p->issues->subquery()
        ->left_join('IssueTag', 'id', 'issue_id')
        ->where('l.tag = ?')
        ->execute($tagname);
    
    etag('h2', "Issues tagged with \"{$tagname}\"");
    if (!empty($issues))
    {
    
        $grid = new UI_IssuesGrid($issues, array('project'));
        etag('div', $grid->render());
    }
}

function show_project($name)
{
    if (!($p = Project::open($name)))
        not_found();

    $sb = get_submenu();
    $sb->create_link('Edit project', UrlFactory::craft('project.edit', $p));
    $sb->create_link('Create Issue', UrlFactory::craft('issue.create', $p));
        
    Layout::open('default')->get_document()->title = $p->title;
    etag('h1', $p->title);
    etag('div', project_breadcrumb($p)->render());
    etag('p class="description" nl_escape_on', $p->description);

    $issues = $p->issues
        ->subquery()
        ->order_by('created', 'DESC')
        ->execute();
        
    if (!empty($issues))
    {
        etag('h2', 'Issues');
        $grid = new UI_IssuesGrid($issues, array('project'));
        etag('div', $grid->render());
    }
    
    $tag_counters = $p->tag_counters->all();
    if (!empty($tag_counters))
    {
        $ul = tag('div class="tag-cloud"');
        foreach($tag_counters as $counter)
            UrlFactory::craft('project.tag', $p, $counter->tag)
                ->anchor($counter->tag)
                ->attr('style', 'font-size: ' . ($counter->percent + 1.0) . 'em;')
                ->appendTo($ul);
        Layout::open('default')->add_widget('Tags', $ul, $prepend = true);
    }

}

function create_project()
{
    $bc = project_breadcrumb($p);
    $bc->create_link('Create project', UrlFactory::craft('project.create'));
    etag('div', $bc->render());
    $frm = new UI_ProjectCreateForm();
    etag('div html_escape_off', $frm->render());
}

function edit_project($p_name)
{
    if (!($p = Project::open($p_name)))
        not_found();

    $bc = project_breadcrumb($p);
    $bc->create_link('Edit', UrlFactory::craft('project.edit', $p));
    etag('div', $bc->render());
    $edit_frm = new UI_ProjectEditForm($p);
    etag('div html_escape_off', $edit_frm->render());
}

function edit_issue($p_name, $issue_id)
{
    if (!($i = Issue::open($issue_id)))
        not_found();

    $p = $i->project;
    if ($p->name != $p_name)
        not_found();

    $bc = project_breadcrumb($p, $i);
    $bc->create_link('Edit', UrlFactory::craft('issue.edit', $i));
    etag('div', $bc->render());
    $edit_frm = new UI_IssueEditForm($p, $i);
    etag('div html_escape_off', $edit_frm->render());
}

function create_issue($p_name)
{
    if (!($p = Project::open($p_name)))
        not_found();

    $bc = project_breadcrumb($p);
    $bc->create_link('Create issue', UrlFactory::craft('issue.create', $p));
    
    etag('h1', $p->title);
    etag('div', $bc->render());
    $frm = new UI_IssueCreateForm($p);
    etag('div html_escape_off', $frm->render());
}

function show_issue($p_name, $issue_id)
{
    if (!($i = Issue::open($issue_id)))
        not_found();

    $p = $i->project;
    if ($p->name != $p_name)
        not_found();

    get_submenu()
        ->create_link('Edit', UrlFactory::craft('issue.edit', $i));
        
    // Forms
    if (Authz::is_allowed(array('issue', $issue_id), 'comment'))
        $post_frm = new UI_IssuePostCommentForm($i);

    // Render issue
    Layout::open('default')->get_document()->title = "Issue #{$i->id} in {$p->title} | {$i->title}";
    etag('div class="issue-view"',
        tag('h1', $i->title),
        project_breadcrumb($p, $i)->render(),
        tag('span class="date"', date_exformat($i->created)->smart_details()),
        tag_user($i->poster, 'poster'),
        ($i->assignee == ''?'None':tag_user($i->assignee, 'assignee')),
        tag('span class="description" nl_escape_on', $i->description),
        $ul_tags = tag('ul class="tags"'),
        $ul_actions = tag('ul class="actions"')
    );

    // Tags
    foreach($i->tags->all() as $t)
        tag('li class="tag"', 
            UrlFactory::craft('project.tag', $p, $t->tag)->anchor($t->tag)
        )->appendTo($ul_tags);

    // Actions
    $action_count = 0;
    foreach($i->actions->all() as $action)
    {   
        $li = tag('li',
            tag('a class="anchor"', '#' . ($action_count +=1))->attr('href', '#comment_' . $action->id),
            tag_user($action->actor, 'actor'),
            tag('span class="date"', date_exformat($action->date)->smart_details())
        )->attr('id', 'comment_' . $action->id)->appendTo($ul_actions)->add_class($action->type);

        if ($action->type == 'comment')
        {   $comments = $action->get_details();
            tag('span class="post"', $comments->post)->appendTo($li);
            if ($comments->attachment)
                tag('a class="attachment"',
                    $comments->attachment->filename,
                    array('href' => UrlFactory::craft('attachment.view', $comments->attachment))
                )->appendTo($li);
        }
        else if ($action->type == 'status_change')
        {   
            $change = $action->get_details();
            tag('span class="status_change"',
                tag('span class="title"', 'Status changed:'),
                tag('span class="old"', $change->old_status)
                    ->add_class('status')->add_class($change->old_status),
                ' → ',
                tag('span class="new"', $change->new_status)->add_class('status')
                    ->add_class($change->new_status)
            )->appendTo($li);
        }
        else if ($action->type == 'tag_change')
        {   
            $change = $action->get_details();
            tag('span class="tag_change"',
                ($change->operation == 'add'?'Added ':'Removed '),
                tag('span class=""', $change->tag)
            )->appendTo($li)->add_class($change->operation);
        }
        else if ($action->type == 'details_change')
        {   
            $change = $action->get_details();
            if ($change->old_title != $change->new_title)
                $li->append(
                    tag('span class="title_change"',
                        tag('span class="title"', 'Issue title changed:'),
                        tag('div', htmlDiff($change->old_title, $change->new_title))
                ));
            if ($change->old_description != $change->new_description)
                $li->append(
                    tag('span class="title_change"',
                        tag('span class="title"', 'Issue description changed:'),
                        tag('div', htmlDiff($change->old_description, $change->new_description))
                ));
        }
    }

    if (Authz::is_allowed(array('issue', $issue_id), 'comment'))
        etag('div', $post_frm->render());
}

function default_projects()
{   
    //var_dump(Authz::get_role_feeder()->has_role('kpal'));
    //var_dump(Membership::open_query()->where('username = ?')->execute('kpal'));

    if (!Authz::is_allowed('project', 'list'))
        return;

    etag('h1', 'Projects');
    get_submenu()
        ->create_link('Add Project', UrlFactory::craft('project.create'));

    // Show all projects
    $p_grid = new UI_ProjectsGrid(Project::open_all());
    etag('div', $p_grid->render());
}


?>
