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


require_once dirname(__FILE__) . '/lib/vendor/phplibs/ClassLoader.class.php';
require_once dirname(__FILE__) . '/lib/tools.lib.php';
/**
 * Here you can write code that will be executed at the begining of each page instance
 */

// Autoloader for local and phplibs classes
$phplibs_loader = new ClassLoader(
    array(
    dirname(__FILE__) . '/lib/vendor/phplibs',
    dirname(__FILE__) . '/lib/local'
));
$phplibs_loader->set_file_extension('.class.php');
$phplibs_loader->register();

// Start code profiling
Profile::checkpoint('document.start');

// Load static library for HTML
require_once dirname(__FILE__) . '/lib/vendor/phplibs/Output/html.lib.php';

// Load models 
require_once dirname(__FILE__) . '/lib/models.lib.php';

// Load urls
require_once(dirname(__FILE__) . '/lib/urls.lib.php');

// Load configuration file
require_once dirname(__FILE__) . '/config.inc.php';

// Database connection
DB_Conn::connect(Config::get('db.host'), Config::get('db.user'), Config::get('db.pass'), Config::get('db.schema'), 'error_log', true);
DB_Conn::query('SET NAMES utf8;');
DB_Conn::query("SET time_zone='+0:00';");

// PHP TimeZone
date_default_timezone_set(Config::get('site.timezone'));

// PHP Session
session_start();
if (!isset($_SESSION['initialized']))
{
    // Prevent session fixation with invalid ids
    $_SESSION['initialized'] = true;
    session_regenerate_id();
}

// Setup authentication
$auth = new Auth_Backend_DB(array(
    'model_user' => 'User',
    'field_username' => 'username',
    'field_password' => 'password',
    'hash_function' => 'sha1',
    'where_conditions' => array('enabled = 1')
));
Auth_Realm::set_backend($auth);

?>
