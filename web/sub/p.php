<?php
// Use default layout to render this page
Layout::open('default')->activate();


Stupid::add_rule('show_issue',
    array('type' => 'url_path',
        'chunk[2]' => '/^([^\+].+)$/', 'chunk[3]' => '/^\+issue$/', 'chunk[4]' => '/^([\d]+)$/'));
Stupid::add_rule('show_project',
    array('type' => 'url_path', 'chunk[2]' => '/^([^\+].+)$/'));
Stupid::set_default_action('default_projects');
Stupid::chain_reaction();


function show_project($name)
{
    if (!($p = Project::open($name)))
        not_found();
        
    Layout::open('default')->get_document()->title = $p->title;
    etag('h1', $p->title);

    etag('ul class="issues"')->push_parent();
    foreach($p->issues->all() as $issue)
        etag('li',
            tag('a', array('href' => url('/p/' . $p->name . '/+issue/' . $issue->id)),
                tag('span class="id"', (string)$issue->id),
                tag('span class="title"', $issue->title),
                tag('span class="status"', $issue->status)->add_class($issue->status)
            )
        );
    Output_HTMLTag::pop_parent();
}

function show_issue($p_name, $issue_id)
{
    if (!($i = Issue::open($issue_id)))
        not_found();

    $p = $i->project;
    if ($p->name != $p_name)
        not_found();

    // Forms
    $post_frm = new UI_IssuePostCommentForm($i);

    // Render issue
    Layout::open('default')->get_document()->title = "Issue #{$i->id} in {$p->title} | {$i->title}";
    etag('div class="issue"',
        tag('h1', $i->title),
        tag('span class="description"', $i->description),
        tag('span class="date"', $i->created->format('U')),
        $ul_posts = tag('ul class="actions"')
    );


    foreach($i->actions->all() as $action)
    {
        $li = tag('li',
            tag('span class="actor"', $action->actor),
            tag('span class="date"', $action->date->format('U'))
        )->appendTo($ul_posts)->add_class($action->type);

        if ($action->type == 'comment')
            tag('span class="post"', $action->get_details()->post)->appendTo($li);
        else if ($action->type == 'status_change')
        {   $change = $action->get_details();
            tag('span class="status_change"',
                tag('span class="old_status"', $change->old_status),
                tag('span class="new_status"', $change->new_status)
            )->appendTo($li);
        }
        else if ($action->type == 'tag_change')
        {   $change = $action->get_details();
            tag('span class="tag_change"',
                ($change->operation == 'add'?'Added ':'Removed '),
                tag('span class=""', $change->tag)
            )->appendTo($li)->add_class($change->operation);
        }
    }

    etag('div', $post_frm->render());
}

function default_projects()
{
    // Show all projects
    etag('ul class="projects"')->push_parent();

    foreach(Project::open_all() as $p)
    {
        etag('li',
            tag('a', array('href' => url('/p/' . $p->name)), 
                $p->title
            )
        );
    }
    Output_HTMLTag::pop_parent();
}


?>
