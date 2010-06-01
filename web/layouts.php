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


// Enable XHTML Mode
Output_HTMLTag::$default_render_mode = 'xhtml';

// Layout "default"
$dl = new Layout_Default('default');
$dl->activate();


// Login "default"
$dl = new Layout('login');
$dl->activate();
$dl->get_document()->title = Config::get('site.title');
$dl->get_document()->add_favicon(surl('/favicon.png'));
$dl->get_document()->add_ref_css(surl('/static/css/login.css'));
$dl->get_document()->add_ref_js(surl('/static/js/jquery-1.4.2.min.js'));
$dl->get_document()->add_meta('noindex', array('name' => 'robots'));
etag('div id="wrapper"')->push_parent();
etag('div id="main"',
    $def_content = tag('div id="content"')
);
etag('script html_escape_off',
"
     $(document).ready(function(){
        $('.ui-login input:visible:first').focus();
    });
");
$dl->set_default_container($def_content);
$dl->deactivate();
?>
