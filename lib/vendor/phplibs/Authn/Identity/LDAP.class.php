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


require_once( dirname(__FILE__) . '/../Identity.class.php');

class Authn_Identity_LDAP implements Authn_Identity
{
    private $user_attribs;

    private $id_attribute;
    
    public function __construct($user_attribs, $id_attribute)
    {
        $this->user_attribs = $user_attribs;
        $this->id_attribute = $id_attribute;
        
        // Check that there is an id attribute
        if (! $this->get_attribute($this->id_attribute))
            throw new RuntimeException("There is no attirubute with name \"{$this->id_attribute}\"!");
    }

    public function dn()
    {   
        return $this->get_attribute('distinguishedname');
    }

    public function principalName()
    {   
        return $this->get_attribute('userprincipalname');
    }
    
    public function sam_account_name()
    {
        return $this->get_attribute('samaccountname');
    }
    
    public function get_attribute($name)
    {
        if (!isset($this->user_attribs[$name]))
            return false;
        if ($this->user_attribs[$name]['count'] == 0)
            return false;
        return $this->user_attribs[$name][0];
    }
    
    //! Returns the DN of the user
    public function id()
    {
        return $this->get_attribute($this->id_attribute);
    }
}

?>
