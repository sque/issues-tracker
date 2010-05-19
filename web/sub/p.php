<?php
// Use default layout to render this page
Layout::open('default')->activate();

Stupid::add_rule('edit_issue',
    array('type' => 'url_path',
        'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+issue$/', 'chunk[4]' => '/^([\d]+)$/', 'chunk[5]' => '/^\+edit$/'));
Stupid::add_rule('show_issue',
    array('type' => 'url_path',
        'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+issue$/', 'chunk[4]' => '/^([\d]+)$/'));
Stupid::add_rule('create_issue',
    array('type' => 'url_path',
        'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+createissue$/'));
Stupid::add_rule('edit_project',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+edit$/'));
Stupid::add_rule('show_project',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/'));
Stupid::add_rule('create_project',
    array('type' => 'url_path', 'chunk[2]' => '/^\+create$/'));
Stupid::set_default_action('default_projects');
Stupid::chain_reaction();

function create_breadcrumb($project = null, $issue = null)
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

    create_breadcrumb($p);
    $sb = get_submenu();
    $sb->create_link('Edit project', UrlFactory::craft('project.edit', $p));
    $sb->create_link('Create Issue', UrlFactory::craft('issue.create', $p));
        
    Layout::open('default')->get_document()->title = $p->title;
    etag('h1', $p->title);
    etag('p class="description" nl_escape_on', $p->description);

    etag('ul class="issues"')->push_parent();
    foreach($p->issues->subquery()->order_by('created', 'DESC')->execute() as $issue)
        etag('li',
            tag('a', array('href' => UrlFactory::craft('issue.view', $issue)),
                tag('span class="id"', (string)$issue->id),
                tag('span class="title"', $issue->title),
                tag('span class="status"', $issue->status)->add_class($issue->status),
                tag('span class="date"', date_exformat($issue->created)->smart_details())
            )
        );
    Output_HTMLTag::pop_parent();
}

function create_project()
{
    create_breadcrumb()
        ->create_link('Create project', UrlFactory::craft('project.create'));
    $frm = new UI_ProjectCreateForm();
    etag('div html_escape_off', $frm->render());
}

function edit_project($p_name)
{
    if (!($p = Project::open($p_name)))
        not_found();

    create_breadcrumb($p)
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

    create_breadcrumb($p, $i)
        ->create_link('Edit', UrlFactory::craft('issue.edit', $i));
    $edit_frm = new UI_IssueEditForm($p, $i);
    etag('div html_escape_off', $edit_frm->render());
}

function create_issue($p_name)
{

    if (!($p = Project::open($p_name)))
        not_found();

    create_breadcrumb($p)
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

    create_breadcrumb($p, $i);
        
    get_submenu()
        ->create_link('Edit', UrlFactory::craft('issue.edit', $i));
        
    // Forms
    $post_frm = new UI_IssuePostCommentForm($i);

    // Render issue
    Layout::open('default')->get_document()->title = "Issue #{$i->id} in {$p->title} | {$i->title}";
    etag('div class="issue"',
        tag('h1', $i->title),
        tag('span class="description" nl_escape_on', $i->description),
        tag('span class="date"', date_exformat($i->created)->smart_details()),
        tag('span class="poster user"', $i->poster),
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
            tag('span class="actor"', $action->actor),
            tag('span class="date"', date_exformat($action->date)->smart_details())
        )->appendTo($ul_actions)->add_class($action->type);

        if ($action->type == 'comment')
            tag('span class="post"', $action->get_details()->post)->appendTo($li);
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

    etag('div', $post_frm->render());
}

function default_projects()
{
    // Show all projects
    etag('ul class="projects"')->push_parent();
    
    create_breadcrumb();
    get_submenu()
        ->create_link('Add Project', UrlFactory::craft('project.create'));

    foreach(Project::open_all() as $p)
    {
        etag('li',
            tag('a', array('href' => UrlFactory::craft('project.view', $p)), 
                $p->title
            )
        );
    }
    Output_HTMLTag::pop_parent();
}


?>
