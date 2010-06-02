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


class Layout_Default extends Layout
{
    private $mainmenu = null;
    
    private $submenu = null;
    
    public function get_mainmenu()
    {
        return $this->mainmenu;
    }

    public function get_submenu($title = 'Actions')
    {
        if ($this->submenu !== null)
            return $this->submenu;

        // SubMenu for default layout
        $sb = $this->submenu = new SmartMenu();
        $this->events()->connect('pre-flush', function($event) use($sb, $title)
        {
            $layout = $event->arguments["layout"];
            $layout->add_widget($title, tag('div', $sb->render()))->add_class('submenu');
        });
        return $sb;
    }
    
    public function add_widget($title, $body, $prepend = true)
    {
        $widgets = $this->get_document()->get_body()->getElementById("widgets");
        if (!$widgets)
            $widgets = tag('div id="widgets"')->appendTo(
                $this->get_document()->get_body()->getElementById("main"));
        $div =tag('div class="widget"', tag('span class="title"', $title), $body);
        if ($prepend)
            $widgets->prepend($div);
        else
            $widgets->append($div);
        return $div;
    }
    
    private function init_menu()
    {
        $this->mainmenu = new SmartMenu(array('class' => 'menu'));
        $this->events()->connect('pre-flush', function($event)
        {
            $layout = $event->arguments["layout"];
           
            $layout->get_document()->get_body()->getElementById("main-menu")
                ->append($layout->get_mainmenu()->render());
        });

        $this->mainmenu->create_link('Home', url('/'))->set_autoselect_mode('equal');
        $this->mainmenu->create_link('Projects', url('/p'));
        $this->mainmenu->create_link('Branches', url('/branch'));
    }
    
    protected function __init_layout()
    {   
        $this->activate();
        $doc = $this->get_document();    
        $doc->add_favicon(surl('/favicon.png'));
        $doc->title = Config::get('site.title');
        $doc->add_ref_css(surl('/static/css/default.css'));
        $doc->add_ref_js(surl('/static/js/jquery-1.4.2.min.js'));
        $doc->add_ref_js(surl('/static/js/widgets_bar.js'));
        $doc->add_ref_js(surl('/static/js/issues.js'));
        
        etag('div id="wrapper"')->push_parent();
        etag('div id="header"',
            tag('div id="main-menu"'),
            tag('div id="online-info"')
        );
        etag('div id="main"',
            $def_content = 
            tag('div id="content"')
        );
        etag('div id="footer"', 
            tag('div', 'all rights reserved to ',
                tag('a', 'Encode Group', array('target' => '_blank', 'href' => 'http://www.encodegroup.com'))
            ),
            tag('div', 'made with ',
                tag('a', 'PHPlibs', array('target' => '_blank', 'href' => 'http://phplibs.kmfa.net'))
            )
        );
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
        $this->set_default_container($def_content);
        
        // Online information
        if (Authn_Realm::has_identity())
        {   
            $online_info = $doc->get_body()->getElementById("online-info");
            tag('span class="user-info"',
                tag_user(Authn_Realm::get_identity()->id()),
                tag('a class="logout"', array('href' => ($_SERVER['REQUEST_URI'] .'/+logout')), 'Logout')
            )->appendTo($online_info);
        }

        // Search widgeet
        $this->init_menu();
        $this->deactivate();
    }
}
?>
