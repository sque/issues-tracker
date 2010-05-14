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
require_once( dirname(__FILE__) . '/../Identity/DB.class.php');

//! Implementation for database backend
/**
 * Authentication based on DB_Record implementation.
 * The database models must first be declared before using this class.
 */
class Auth_Backend_DB implements Auth_Backend
{
    //! The normalized options of this instance.
    private $options = array();

    //! The model query object that will be used for authentication.
    private $model_query = array();

    //! Get the options of this instance.
    public function get_options()
    {   return $this->options;  }

    //! Create an instance of this backend
    /**
     * @param $options An associative array of options.
     *  - @b model_user [@b *] The name of the already created model for users.
     *  - @b field_username [@b *] The field that is the username.
     *  - @b field_password [@b *] The field that is the password.
     *  - @b where_condtions Array of extra conditions on select.
     *  - @b hash_function The hash function to be used on password, or NULL for plain.
     *  .
     *  [@b *] mandatory field.
     * @throws InvalidArgumentException If one of the mandatory fields is missing.
     */
    public function __construct($options = array())
    {
        if (! isset(
            $options['model_user'],
            $options['field_username'],
            $options['field_password'])
        )   throw new InvalidArgumentException('Missing mandatory options for Auth_DB_Backend!');

        // Merge with default options and save
        $this->options = array_merge(array(
            'where_conditions' => array(),
            'hash_function' => NULL),
            $options);
        
        // Create model query
        $this->model_query = DB_Record::open_query($this->options['model_user'], 'open_query')
            ->where($options['field_username'] . ' = ?');

        // Append where conditions
        foreach($this->options['where_conditions'] as $condition)
            $this->model_query->where($condition);
    }
    
    public function authenticate($username, $password)
    {
        // Get user
        $records = $this->model_query->execute($username);
        //var_dump($records);
        if (count($records) !== 1)
            return false;

        // Hash-salt function
        if ($this->options['hash_function'] !== NULL)
            $password = call_user_func($this->options['hash_function'], $password);

        // Check password
        if ($password !== $records[0]->{$this->options['field_password']})
            return false;

        // Succesfull
        return new Auth_Identity_DB($records[0]->{$this->options['field_username']}, $this, $records[0]);
    }

    //! Reset the password of an identity
    /**
     * @param $id The username of the identity.
     * @param $new_password The new effective password of identity after reset.
     * @return - @b true if the password was reset.
     *  - @b false on any error.
     */
    public function reset_password($id, $new_password)
    {   $records = DB_Record::open_query($this->options['model_user'], 'open_query')
            ->where($this->options['field_username'] . ' = ?')
            ->execute($id);
            
        if ((!$records) || (count($records) !== 1))
            return false;
        $user = $records[0];

        // Hash-salt function
        if ($this->options['hash_function'] !== NULL)
            $new_password = call_user_func($this->options['hash_function'], $new_password);

        $user->{$this->options['field_password']} = $new_password;
        return $user->save();
    }
}
