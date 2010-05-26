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


//! Implementation of Authz_Role for Authz_Role_FeederInstance
class Authz_Role_Instance implements Authz_Role
{
    //! The name of the role
    private $name;
    
    //! Array of Authz_Role_Instance parents.
    private $parents = array();
    
    //! Construct a new role
    /**
     * @param $name The name of the role.
     * @param $parents The parents of the instance.
     */
    public function __construct($name, $parents = null)
    {
        $this->name = $name;
        
        if (is_string($parents))
            $this->parents[] = $parents;
        if (is_array($parents))
            $this->parents = $parents;
    }

    public function get_name()
    {
        return $this->name;
    }
    
    public function get_parents()
    {
        return $this->parents;
    }
    
    public function has_parent($parent)
    {
        return in_array($parent, $this->parents);
    }
}

?>
