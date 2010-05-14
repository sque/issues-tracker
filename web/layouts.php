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

///////////////////////////////////
// Layout "default"
$dl = Layout::create('default')->activate();
$dl->get_document()->title = Config::get('site.title');
$dl->get_document()->add_ref_css(surl('/static/css/default.css'));
etag('div id="wrapper"')->push_parent();
etag('div id="header"',
    tag('div id="main-menu"')
);
etag('div id="main"',
    $def_content = tag('div id="content"'),
    $widgets = tag('div id="widgets"', 
        tag('div class="submenu-widget" id="submenu"')
    )
);
etag('div id="footer"', 
    tag('a', 'PHPlibs', array('href' => 'http://phplibs.kmfa.net')),' skeleton');
if (Config::get('site.google_analytics'))
etag('script type="text/javascript" html_escape_off',
" var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '" . Config::get('site.google_analytics') ."']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();");
$dl->set_default_container($def_content);

// Menu for default layout
$dl->menu = new SmartMenu(array('class' => 'menu'));
$dl->events()->connect('pre-flush',
create_function('$event', '$layout = $event->arguments["layout"];
    $layout->get_document()->get_body()->getElementById("main-menu")->append($layout->menu->render());'));
$dl->menu->create_link('Home', '/')->set_autoselect_mode('equal');
$dl->menu->create_link('Projects', '/p');
$dl->menu->create_link('Section 2', '/section2');
$dl->deactivate();

// SubMenu for default layout
$dl->submenu = new SmartMenu();
$dl->submenu_enabled = false;
$dl->submenu_title = '';
$dl->events()->connect('pre-flush',
create_function('$event', '$layout = $event->arguments["layout"];
    if ($layout->submenu_enabled)
        $layout->get_document()->get_body()->getElementById("submenu")->append(
            tag(\'span class="title"\', $layout->submenu_title),
            $layout->submenu->render()
        );'
));

?>
