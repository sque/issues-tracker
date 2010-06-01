<?php


//! Implementation of Database Role Feeder
class AuthzRoleFeederDatabaseLoose implements Authz_Role_Feeder
{
    //! Options of database connection
    protected $options;
    
    //! Construct database role feeder
    /**
     * @param $options An associative array with options
     *  - @b role_query [@b Mandatory]: A DB_ModelQuery object for role querying
     *  - @b role_name_field [@b Mandatory]: The field that holds the role name.
     *  - @b parents_query [Default = null]: The DB_ModelQuery object for role's parent querying.
     *  - @b parent_name_field [Default = null]: The field that holds the parent name.
     *  - @b parent_name_filter_func [Default = null]: A filter function to pass the parents name.
     *  - @b role_class [Default = Authz_Role_Database]: The class for creating role objects.
     *  .
     */
    public function __construct($options)
    {
        $def_options = array(
            'role_query' => null,
            'role_name_field' => null,
            'parents_query' => null,
            'parent_name_field' => null,
            'parent_name_filter_func' => null,
            'role_class' => 'Authz_Role_Database'
        );

        $this->options = array_merge($def_options, $options);
    }
    
    public function get_options()
    {
        return $this->options;
    }
    
    public function has_role($name)
    {   
        return true;
    }
    
    public function get_role($name)
    {
        if (!$this->has_role($name))
            return false;
        
        return new $this->options['role_class']($name, $this->options);
    }
}
?>
