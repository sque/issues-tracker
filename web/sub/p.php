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
    array('type' => 'authz', 'resource' => 'project', 'backref_instance' => 'issue_id', 'action' => 'view'));
    
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
    array('type' => 'authz', 'resource' => 'project', 'action' => 'create'),
    array('type' => 'url_path', 'chunk[2]' => '/^\+create$/'));
Stupid::set_default_action('default_projects');
Stupid::chain_reaction();

function project_breadcrumb($project = null, $issue = null)
{
    $dl = Layout::open('default');
    $dl->breadcrumb->create_link('Projects', UrlFactory::craft('projects'));
    if ($project)
        $dl->breadcrumb->create_link($project->title, UrlFactory::craft('project.view', $project));
    if ($issue)
        $dl->breadcrumb->create_link($issue->title, UrlFactory::craft('issue.view', $issue));
    return $dl->breadcrumb;
}

function get_submenu()
{
    $dl = Layout::open('default');
    $dl->submenu_enabled = true;
    return $dl->submenu;
}

function show_project($name)
{
    if (!($p = Project::open($name)))
        not_found();

    project_breadcrumb($p);
    $sb = get_submenu();
    $sb->create_link('Edit project', UrlFactory::craft('project.edit', $p));
    $sb->create_link('Create Issue', UrlFactory::craft('issue.create', $p));
        
    Layout::open('default')->get_document()->title = $p->title;
    etag('h1', $p->title);
    etag('p class="description" nl_escape_on', $p->description);


    $grid = new UI_IssuesGrid($p->issues
        ->subquery()
        ->order_by('created', 'DESC')
        ->execute(), array('project'));
    etag('div', $grid->render());
}

function create_project()
{
    project_breadcrumb()
        ->create_link('Create project', UrlFactory::craft('project.create'));
    $frm = new UI_ProjectCreateForm();
    etag('div html_escape_off', $frm->render());
}

function edit_project($p_name)
{
    if (!($p = Project::open($p_name)))
        not_found();

    project_breadcrumb($p)
        ->create_link('Edit', UrlFactory::craft('project.edit', $p));
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

    project_breadcrumb($p, $i)
        ->create_link('Edit', UrlFactory::craft('issue.edit', $i));
    $edit_frm = new UI_IssueEditForm($p, $i);
    etag('div html_escape_off', $edit_frm->render());
}

function create_issue($p_name)
{
    if (!($p = Project::open($p_name)))
        not_found();

    project_breadcrumb($p)
        ->create_link('Create issue', UrlFactory::craft('issue.create', $p));
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

    project_breadcrumb($p, $i);
        
    get_submenu()
        ->create_link('Edit', UrlFactory::craft('issue.edit', $i));
        
    // Forms
    if (Authz::is_allowed(array('issue', $issue_id), 'comment'))
        $post_frm = new UI_IssuePostCommentForm($i);

    // Render issue
    Layout::open('default')->get_document()->title = "Issue #{$i->id} in {$p->title} | {$i->title}";
    etag('div class="issue"',
        tag('h1', $i->title),
        tag('span class="description" nl_escape_on', $i->description),
        tag('span class="date"', date_exformat($i->created)->smart_details()),
        tag_user($i->poster, 'poster'),
        ($i->assignee == ''?'None':tag_user($i->assignee, 'assignee')),
        $ul_tags = tag('ul class="tags"'),
        $ul_actions = tag('ul class="actions"')
    );

    // Tags
    foreach($i->tags->all() as $t)
        tag('li', $t->tag)->appendTo($ul_tags);

    // Actions
    foreach($i->actions->all() as $action)
    {
        $li = tag('li',
            tag_user($action->actor, 'actor'),
            tag('span class="date"', date_exformat($action->date)->smart_details())
        )->appendTo($ul_actions)->add_class($action->type);

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
                tag('span class="old_status"', $change->old_status),
                tag('span class="new_status"', $change->new_status)
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
                    tag('span class="title_change"', 'Title changed from ',
                        tag('span class="old"', $change->old_title), ' to ',
                        tag('span class="new"', $change->new_title)));
            if ($change->old_description != $change->new_description)
                $li->append(
                    tag('span class="title_change"', 'Description changed from ',
                        tag('span class="old"', $change->old_description), ' to ',
                        tag('span class="new"', $change->new_description)));
        }
    }

    if (Authz::is_allowed(array('issue', $issue_id), 'comment'))
        etag('div', $post_frm->render());
}

function default_projects()
{
    if (!Authz::is_allowed('project', 'list'))
        return;

    project_breadcrumb();
    get_submenu()
        ->create_link('Add Project', UrlFactory::craft('project.create'));

    // Show all projects
    $p_grid = new UI_ProjectsGrid(Project::open_all());
    etag('div', $p_grid->render());
}


?>
