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

// Load configuration file
require_once dirname(__FILE__) . '/config.inc.php';

// Load static library for HTML
require_once dirname(__FILE__) . '/lib/vendor/phplibs/Output/html.lib.php';

// Load models 
require_once dirname(__FILE__) . '/lib/models.lib.php';

// Load urls
require_once(dirname(__FILE__) . '/lib/urls.lib.php');

// Diff lib
require_once dirname(__FILE__) . '/lib/diff.lib.php';

// Database connection
DB_Conn::connect(Config::get('db.host'), Config::get('db.user'), Config::get('db.pass'), Config::get('db.schema'), true);
DB_Conn::query('SET NAMES utf8;');
DB_Conn::query("SET time_zone='+0:00';");
DB_Conn::events()->connect('error',function($e)
{
    error_log( $e->arguments['message']); 
});

/*
$dbcache = new Cache_Apc('issue-tracker');
//$dbcache->delete_all();
DB_Model::set_model_cache($dbcache);
DB_ModelQueryCache::set_global_query_cache($dbcache);
*/
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

// Mailer
Mailer::set_mail_instance(Mail::factory((Config::get('mail.enabled')?'mail':'mock')));
Mailer::set_default_headers(array('From' => Config::get('mail.default_from')));

// Setup authentication
if (Config::get('site.authn.type') == 'db')
{
    $auth = new Authn_Backend_DB(array(
        'model_user' => 'User',
        'field_username' => 'username',
        'field_password' => 'password',
        'hash_function' => 'sha1',
        'where_conditions' => array('enabled = 1')
    ));
    
    $authz = new Authz_Role_FeederDatabase(array(
        'role_query' => User::open_querY()->where('username = ?'),
        'role_name_field' => 'username',
        'parents_query' => Membership::open_query()->where('username = ?'),
        'parent_name_field' => 'groupname',
        'parent_name_filter_func' => function($name)
        {
            return '@' . $name;
        }
    ));
}
else if (Config::get('site.authn.type') == 'ldap')
{
    $auth = new Authn_Backend_LDAP(array(
        'url' => Config::get('site.authn.ldap_url'),
        'baseDN' => Config::get('site.authn.ldap_basedn'),
        'default_domain' => Config::get('site.authn.ldap_default_domain'),
        'force_protocol_version' => Config::get('site.authn.ldap_force_protocol'),
        'id_attribute' => Config::get('site.authn.ldap_id_attribute')
    ));
    
    $authz = new AuthzRoleFeederDatabaseLoose(array(
        'parents_query' => Membership::open_query()->where('username = ?'),
        'parent_name_field' => 'groupname',
        'parent_name_filter_func' => function($name)
        {
            return '@' . $name;
        }
    ));
}
Authn_Realm::set_backend($auth);

// Setup authorization
Authz::set_resource_list($list = new Authz_ResourceList());
Authz::set_role_feeder($authz);

// Standard authorization
$list->add_resource('project');
$list->add_resource('issue', 'project');
$list->add_resource('userprofile');
$list->add_resource('branch');

Authz::allow('userprofile', null, 'view');
Authz::allow('userprofile', '@admin', 'edit');

Authz::allow('project', null, 'view');
Authz::allow('project', null, 'list');
Authz::allow('project', null, 'post-issue');
Authz::allow('project', '@admin', 'create');
Authz::allow('project', '@admin', 'edit');

Authz::allow('issue', '@dev', 'change-status');
Authz::allow('issue', null, 'comment');
Authz::allow('issue', null, 'edit');

Authz::deny('branch', null, 'view');
Authz::allow(array('branch', 'private'), null, 'view');
Authz::allow(array('branch', 'pub'), '@dev', 'view');

?>
