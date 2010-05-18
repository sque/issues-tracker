<?php

//! Factory to create urls
class UrlFactory
{

    //! An array with all resources
    static public $resources = array();
    
    //! Register a new url pattern
    static public function register($name, $params, $pattern)
    {
        if (isset(self::$resources[$name]))
            return false;
        self::$resources[$name] = new UrlFactoryResource($name, $params, $pattern);
    }
    
    //! Open a UrlFactoryResource
    static public function open($name)
    {   
        if (!isset(self::$resources[$name]))
            return false;
        return self::$resources[$name];
    }
    
    //! Craft a prepared url
    static public function craft($name)
    {   
        if (!isset(self::$resources[$name]))
            throw new RuntimeException("Cannot find url resource {$name} in UrlFactory.");
        
        $args = func_get_args();
        $args = array_slice($args, 1);
        return new Uri(url(call_user_func_array(array(self::$resources[$name], 'generate'), $args)));
    }

}
