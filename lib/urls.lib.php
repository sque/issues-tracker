<?php

UrlFactory::register('issue.view', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}');
UrlFactory::register('issue.edit', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}/+edit');
UrlFactory::register('issue.create', '$project', '/p/{$project->name}/+createissue');
UrlFactory::register('project.view', '$project', '/p/{$project->name}');
UrlFactory::register('project.edit', '$project', '/p/{$project->name}/+edit');
UrlFactory::register('project.create', '', '/p/+create');
UrlFactory::register('projects', '', '/p');
UrlFactory::register('attachment.view', '$a', '/file/{$a->id}/{$a->filename}');
UrlFactory::register('user.view', '$name', '/~{$name}');
UrlFactory::register('group.view', '$name', '/@{$name}');

function tag_user($username, $extra_classes = array())
{   $display_name = $username;
    if (empty($username))
        return;
        
    if ($p = UserProfile::open($username))
        $display_name = $p->fullname;

    $a = tag('a class="user"', $display_name, 
        array('href' => UrlFactory::craft('user.view', $username))
    );
    
    if (is_array($extra_classes))
        foreach($extra_classes as $class)
            $a->add_class($class);
    else
        $a->add_class($extra_classes);
    return $a;
}
?>
