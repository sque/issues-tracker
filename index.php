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


require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/web/layouts.php';

// DO NOT EDIT THIS FILE TO CHANGE DEFAULT PAGE
/**
 * This file is here to act as url router. To edit actual web pages
 * check /web folder. If you want a different global url behaviour
 * then you should it here.
 */

function force_login()
{
    if (substr($_SERVER['REQUEST_URI'], -1) == '/')
        Net_HTTP_Response::redirect($_SERVER['REQUEST_URI'] . '+login');
    else
        Net_HTTP_Response::redirect($_SERVER['REQUEST_URI'] . '/+login');
}

// Special handling for special urls
Stupid::add_rule(create_function('', 'require(\'web/login.php\');'),
    array('type' => 'url_path', 'chunk[-1]' => '/\+login/')
);
Stupid::add_rule(create_function('', 'require(\'web/login.php\');'),
    array('type' => 'url_path', 'chunk[-1]' => '/\+logout/')
);
Stupid::add_rule('force_login',
    array('type' => 'func', 'func' => create_function('', 'return !Authn_Realm::has_identity();'))
);
Stupid::add_rule(function(){    require(__DIR__ . '/web/user.php');   },
    array('type' => 'url_path', 'chunk[1]' => '/^~.+$/')
);
Stupid::add_rule(function(){    require(__DIR__ . '/web/user.php');   },
    array('type' => 'url_path', 'chunk[1]' => '/^@.+$/')
);

Stupid::add_rule(create_function('', 'require(\'web/home.php\');'),
    array('type' => 'url_path', 'path' => '/^\/?$/')
);

// Include all sub directories under /web
function is_valid_sub($sub)
{
    return is_file(dirname(__FILE__) . "/web/sub/$sub.php");
}

function include_sub($sub)
{
    require dirname(__FILE__)  . "/web/sub/$sub.php";
}

function not_found()
{
    require dirname(__FILE__)  . "/web/not_found.php";
}

Stupid::add_rule('include_sub',
    array('type' => 'url_path', 'chunk[1]' => '/^([\w]+)$/'),
    array('type' => 'func', 'func' => 'is_valid_sub')
);

Stupid::set_default_action(create_function('', 'require(dirname(__FILE__) . "/web/not_found.php");'));
Stupid::chain_reaction();

?>
