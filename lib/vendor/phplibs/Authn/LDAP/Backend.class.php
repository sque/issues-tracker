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


require_once( dirname(__FILE__) . '/../Backend.class.php');
//require_once( dirname(__FILE__) . '/Identity.class.php');


class Auth_LDAP_Backend implements Auth_Backend
{
    //! The normalized options of this instance.
    private $options = array();

    //! Get the options of this instance.
    public function get_options()
    {   return $this->options;  }

    public function __construct($options = array())
    {
        if (! isset(
        $options['url'],
        $options['baseDN'])
        )   throw new InvalidArgumentException('Missing mandatory options for Auth_DB_Backend!');

        // Merge with default options and save
        $this->options = array_merge(array(
            'username' => false,
            'password' => false,
            'force_protocol_version' => null),
        $options);
    }

    public function get_last_error()
    {   }

    public function authenticate($username, $password)
    {   if (($conn = ldap_connect($this->options['url'])) == false)
    return false;

    if ($this->options['force_protocol_version'])
    if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $this->options['force_protocol_version']))
    return false;

    if (!ldap_bind($conn, $username, $password))
    return false;

    return true;
    }
}
