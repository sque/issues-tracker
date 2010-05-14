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


//! Global space to hold configuration information
class Config
{
    protected static $options = array();

    //! Check if an option exists
    public static function exists($name)
    {	if (!isset(self::$options[$name]))
            return false;
        return true;
    }

    //! Return null if the option is not found or the value of it
    public static function get($name)
    {	if (!self::exists($name))
            return NULL;
        return self::$options[$name];
    }

    //! Get all options of configuration
    public static function get_all()
    {
        return self::$options;
    }
    
    //! Add a new option. Option must not exists
    public static function add($name, $value)
    {	if (self::exists($name))
            return NULL;
        return self::$options[$name] = $value;
    }

    //! Change an option. It will be created if needed
    public static function set($name, $value)
    {
        return self::$options[$name] = $value;
    }
};
?>
