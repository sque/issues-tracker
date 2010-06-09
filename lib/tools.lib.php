<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


//! Create an absolute url based on root file
function url($relative)
{
    if (! strstr($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']))
	    return (dirname($_SERVER['SCRIPT_NAME']) != '/'? dirname($_SERVER['SCRIPT_NAME']):'')  . $relative;
    return $_SERVER['SCRIPT_NAME'] . $relative;
}

//! Create an absolute url for static content
function surl($relative)
{
    return (dirname($_SERVER['SCRIPT_NAME']) != '/'? dirname($_SERVER['SCRIPT_NAME']):'') . $relative;
}

//! Linkify issues
function linkify_issues($text)
{
    return preg_replace_callback('/\bissue\s+#(?P<id>\d+)\b/',
    function($matches)
    {
        if (!($i = Issue::open($matches['id'])))
            return $matches[0]; // Unknown issue;
        return (string)UrlFactory::craft('issue.view', $i)->anchor($matches[0])
            ->attr('title', $i->title);
    }
    ,$text);
}

//! Linkify url
function linkify_url($text)
{
    return preg_replace_callback('/\b(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@$?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?/i',
    function($matches)
    {
        return tag('a target="_blank"', $matches[0])->attr('href', $matches[0]);
    }
    ,$text);
}

//! Escape issue description text blocks()
function esc_issue_block($text)
{
    $text =  linkify_url(linkify_issues(Output_HTMLTag::nl2br(esc_html($text), true)));
    
    return preg_replace_callback('#>[^<]+|^([^<]+)+#',function($matches)
    {
        return str_replace(' ', '&nbsp;', $matches[0]);
        return $matches[0];
    }
    ,$text);
}

?>
