<?php

UrlFactory::register('issue.view', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}');
UrlFactory::register('issue.edit', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}/+edit');
UrlFactory::register('issue.create', '$project', '/p/{$project->name}/+createissue');
UrlFactory::register('project.view', '$project', '/p/{$project->name}');
UrlFactory::register('project.edit', '$project', '/p/{$project->name}/+edit');
UrlFactory::register('project.create', '', '/p/+create');
UrlFactory::register('projects', '', '/p');
UrlFactory::register('attachment.view', '$a', '/file/{$a->id}/{$a->filename}');
?>
