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


require_once dirname(__FILE__) . '/Authz/ResourceList.class.php';
require_once dirname(__FILE__) . '/Authz/Role/FeederInstance.class.php';

//! Static authorization realm
/**
 * Authz was created to provide a standard and easy way to handle
 * authorization system without much work. You can archive the same
 * functionality with non-static interface using directly Authz_ResourceList()
 * and one Authz_Role_Feeder implementation.
 */
class Authz
{
    //! The role feeder that is used
    static private $role_feeder = null;

    //! The resource list that is used
    static private $resource_list = null;
    
    //! Prohibit instantiation of this class
    final private function __construct()
    {
    }
    
    //! Get the current Authz_ResourceList used by Authz
    static public function get_resource_list()
    {
        if (self::$resource_list === null)  
            self::$resource_list = new Authz_ResourceList();
        return self::$resource_list;
    }
    
    //! Set a new Authz_ResourceList for Authz to use.
    static public function set_resource_list(Authz_ResourceList $list)
    {
        self::$resource_list = $list;
    }
    
    //! Get the current Authz_Role_Feeder used by Authz
    static public function get_role_feeder()
    {
        return self::$role_feeder;
    }
    
    //! Set a new Authz_Role_Feeder for Authz to use.
    static public function set_role_feeder(Authz_Role_Feeder $feeder)
    {
        self::$role_feeder = $feeder;
    }
    
    //! Search and return a resource in current resource list.
    /**
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @return
     *  - @b Authz_Resource The found resource object.
     *  - @b false If the resource was not found.
     *  .
     */
    static public function get_resource($resource)
    {
        if (is_array($resource))
            $res = self::get_resource_list()->get_resource($resource[0], $resource[1]);
        else
            $res = self::get_resource_list()->get_resource($resource);
        return $res;
    }
    
    //! Shortcut to add an @b allow ACE in the ACL of a resource
    /**
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     */
    static public function allow($resource, $role, $action)
    {
        $res = self::get_resource($resource);
        if (!$res)
            throw new InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        return $res->get_acl()->allow($role, $action);
    }
    
    //! Shortcut to add an @b deny ACE in the ACL of a resource
    /**
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     */
    static public function deny($resource, $role, $action)
    {
        $res = self::get_resource($resource);
        if (!$res)
            throw new InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        return $res->get_acl()->deny($role, $action);
    }

    //! Search if an action by a role on a specific resource is permitted.
    /**
     * @param $resource
     *  - @b string The name of the resource class
     *  - @b array A tuple of name and instance id of a resource instance.
     *  .
     * @param $role
     *  - @b The name of the role.
     *  - @b null If you want to add wildcard role.
     *  .
     * @param $action The name of the action.
     * @return
     *  - @b true If the most effective ACE is permitting it.
     *  - @b false If the ACE denied it or there is no effective ACE.
     *  .
     */
    static public function is_allowed($resource, $role, $action)
    {   
        $res = self::get_resource($resource);

        if (!$res)
            throw new InvalidArgumentException('Cannot find resource with name "' . 
                (is_array($resource)?$resource[0]:$resource) . '"');

        if (($ace = $res->effective_ace($role, $action, self::get_role_feeder(), $depth)) === null)
            return false;

        return $ace->is_allowed();
    }
   
}

?>
