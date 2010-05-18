<?php

UrlFactory::register('issue.view', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}');
UrlFactory::register('issue.edit', '$issue', '/p/{$issue->project->name}/+issue/{$issue->id}/+edit');
UrlFactory::register('issue.create', '$project', '/p/{$project->name}/+createissue');
UrlFactory::register('project.view', '$project', '/p/{$project->name}');
UrlFactory::register('project.edit', '$project', '/p/{$project->name}/+edit');
UrlFactory::register('project.create', '', '/p/{$project->name}/+create');
UrlFactory::register('projects', '', '/p');

?>
