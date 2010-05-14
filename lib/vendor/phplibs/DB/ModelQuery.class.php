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


require_once(dirname(__FILE__) . '/./Conn.class.php');
require_once(dirname(__FILE__) . '/./Model.class.php');
require_once(dirname(__FILE__) . '/./ModelQueryCache.class.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');

//! Execute SQL queries on models
/**
 * This is an sql-like interface to query on models.
 * You can insert,update,select,delete with any user-defined option
 * but only on the same model.
 * @author sque
 *
 */
class DB_ModelQuery
{
	//! Query type
	protected $query_type = NULL;
	
	//! Pointer to model
	protected $model = NULL;
	
	//! SELECT retrieve fields
	protected $select_fields = NULL;
	
	//! UPDATE set fields
	protected $set_fields = array();
	
	//! INSERT fields
	protected $insert_fields = array();
	
	//! All the insert values
	protected $insert_values = array();
	
	//! Limit of affected records
	protected $limit = NULL;
	
	//! Order of output data (on select only)
	protected $order_by = NULL;

    //! Left join table
    protected $ljoin = NULL;
	
	//! WHERE conditions
	protected $conditions = array();
	
	//! Hash populated by the user instructions
	protected $sql_hash = NULL;
	
	//! The final sql string
	protected $sql_query = NULL;
	
	//! Data wrapper callback
	protected $data_wrapper_callback = NULL;
	
	//! Query cache hints
	protected $cache_hints = NULL;
	
	//! Query cache
	protected $query_cache;
	
	//! Execute parameters
	protected $exec_params = array();
	
	//! Use DBRecord::query() factory to create DB_ModelQuery objects
	/**
	 * @see DBRecord::query() on how to create objects of this class.
	 * @param $model Pass model object
	 * @param $data_wrapper_callback A callback to wrap data after execution
	 */
	final public function __construct($model, $data_wrapper_callback = NULL)
	{	
		// Save pointer of the model
		$this->model = & $model;
		$this->data_wrapper_callback = $data_wrapper_callback;
		$this->query_cache = DB_ModelQueryCache::open($model);
		$this->reset();		
	}
	
	//! Reset query so that it can be used again
	public function & reset()
	{	// Reset all values to default
		$this->query_type = NULL;
		$this->select_fields = array();
		$this->set_fields = array();
		$this->insert_fields = array();
		$this->insert_values = array();
		$this->limit = NULL;
		$this->order_by = NULL;
		$this->ljoin = NULL;
		$this->conditions = array();
		$this->sql_hash = 'HASH:' . $this->model->table() .':';
		$this->sql_query = NULL;
		$this->cache_hints = NULL;

		return $this; 
	}
	
	//! Check if statement is alterable
	/**
	 * Alterable means that there can be more options on the query. 
	 * @return @b TRUE if query is alterable, @b FALSE if the query is closed for changes. 
	 */
	public function is_alterable()
	{	return ($this->sql_query === NULL);	}
	
	//! Check if it i alterable otherwise throw exception
	private function assure_alterable()
	{	if (!$this->is_alterable())
			throw new RuntimeException('This DB_ModelQuery instance is no longer alterable!');
	}
	
	//! Start a deletion on model
	public function & delete()
	{	$this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'delete';
		$this->sql_hash .= ':delete:';
		return $this; 
	}
	
	//! Start an update on model
	public function & update()
	{	$this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'update';
		$this->sql_hash .= ':update:';
		return $this; 
	}
	
	//! Start a selection query on model
	public function & select($fields)
	{	$this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'select';
		$this->select_fields = $fields;
		$this->sql_hash .= ':select:' . implode(':', $fields);
		return $this;
	}
	
	//Start an insertation query on model
	public function & insert($fields)
	{	$this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'insert';
		$this->insert_fields = $fields;
		$this->sql_hash .= ':insert:' . implode(':', $fields);
		return $this;
	}
	
	//! Define values of insert command as an array
	public function & values_array($values)
	{	$this->assure_alterable();
		if (count($values) != count($this->insert_fields))
			throw new InvalidArgumentException('The quantity of values, must be exactly ' .
				'the same with the fields defined with insert()');
		$this->insert_values[] = $values;
		$this->sql_hash .= ':' . implode(':', $values);
		return $this;
	}
	
	//! Define values of insert command as arguments
	public function & values()
	{	$args = func_get_args();
		return $this->values_array($args);
	}
	
	//! Set a field value
	public function & set($field, $value = NULL)
	{	$this->assure_alterable();
		$this->set_fields[] = array(
			'field' => $field,
			'value' => $value
		);
		$this->sql_hash .= ':set:' . $field . ':' . $value;
		return $this;
	}

	//! Where is the expression
	public function & where($exp, $bool_op = 'AND')
	{	$this->assure_alterable();
		$this->conditions[] = array(
			'expression' => $exp,
			'bool_op' => $bool_op,
			'op' => NULL,
			'lvalue' => NULL,
			'ltype' => NULL,
			'rvalue' => NULL,
			'rtype' => NULL,
			'require_argument' => false,
		);
		$this->sql_hash .= ':where:' . $bool_op . ':' . $exp;
		return $this;
	}

	//! Declare left join table (for extra criteria only)
	public function & left_join($model_name, $primary_field, $joined_field)
	{   $this->assure_alterable();

	    // Check if there is already a type command
		if ($this->query_type !== 'select')
			throw new RuntimeException('You cannot declare left_join on DB_ModelQuery that is not of SELECT type!');

		$this->ljoin = array(
		    'model_name' => $model_name,
		    'join_local_field' => $primary_field,
		    'join_foreign_field' => $joined_field
		);
		$this->sql_hash .= ':ljoin:' . $model_name . ':' . $primary_field . ':' . $joined_field;
	    return $this;
	}

	//! Limit the query
	public function & limit($length, $offset = NULL)
	{	$this->assure_alterable();
		$this->limit = array('length' => $length, 'offset' => $offset);
		$this->sql_hash .= ':limit:' . $length . ':' . $offset;
		return $this;
	}
	
	//! Select order by
	public function & order_by($field, $order = 'ASC')
	{	$this->assure_alterable();
		$this->order_by = array('field' => $field, 'order' => $order);
		$this->sql_hash .= ':order:' . $field . ':' . $order;
		return $this;
	}

	//! Set the callback wrapper function
	public function & set_data_wrapper($callback)
	{   $this->assure_alterable();
	    $this->data_wrapper_callback = $callback;
	    return $this;
	}
	
	//! Push an execute parameter
	public function & push_exec_param($value)
	{	$this->exec_params[] = $value;
		return $this;
	}
	
	//! Get the type of query
	public function type()
	{	return $this->query_type;	} 
	
	//! Get query hash
	public function hash()
	{	return $this->sql_hash;		}

	//! Analyze WHERE side value
	private function analyze_where_value(& $cond, $side, $string)
	{   $matched = preg_match_all(
	        '/^[\s]*' . // Pre-field space
	        '(' .
	            '\?' .                          // prepared statement wildcard
	            '|\'[^\']+\'' .                 // literal string value
	            '|[\d]+' .                      // literal decimal value
	            '|((p|l)\.)?([\w]+)' .          // column reference
	        ')' . 
	        '[\s]*/', // Post-field space
	        $string,
	        $matches
	    );

	    if ($matched != 1)
		    throw new InvalidArgumentException("Invalid WHERE expression '{$cond['expression']}' was given.");

	    if ($matches[1][0] === '?')
	    {   $cond['require_argument'] = true;
	        $cond[$side] = '?';
	    }
	    else if(! empty($matches[4][0]))
	    {   $table_shorthand = (empty($matches[3][0])?'p':$matches[3][0]);

	        if ($table_shorthand === 'p')
    	        $sqlfield = $this->model->field_info($matches[4][0], 'sqlfield');
    	    else if ($table_shorthand === 'l')
    	    {   if ($this->ljoin === NULL)
    	            throw new RuntimeException("You cannot use \"l\" shorthand in WHERE when there is no LEFT JOIN!");
    	        $sqlfield = $this->ljoin['model']->field_info($matches[4][0], 'sqlfield');
    	    }
    	        	    
            if ($sqlfield === NULL)
			    throw new RuntimeException("There is no field with name {$matches[4][0]} in model {$this->model->name()}");

            //! Construct valid sql query
            $cond[$side] = (($this->ljoin !== NULL)?$table_shorthand . '.':'') . '`' . $sqlfield . '`';
	    }
	    else
	    {   $cond[$side] = $matches[1][0];  }
	    
	}
	
	//! Analyze WHERE conditions and return where statement
	private function analyze_where_conditions()
	{	$query = '';
		if (count($this->conditions) > 0)
		{	$query = ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond)
			{	$matched = 
					preg_match_all('/^[\s]*([\w\.]+|\?|\'[^\']+\')[\s]*' .
						'([=<>]+|like|between|in)' .
						'[\s]*([\w\.]+|\?|\'[^\']+\')[\s]*$/',
						$cond['expression'], $matches);

				if ($matched != 1)
					throw new InvalidArgumentException("Invalid WHERE expression '{$cond['expression']}' was given.");

                // Operator
				$cond['op'] = $matches[2][0];
                $cond['require_argument'] = false;

                // L-value
                $this->analyze_where_value($cond, 'lvalue', $matches[1][0]);
                
                // R-value
                $this->analyze_where_value($cond, 'rvalue', $matches[3][0]);

				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op'];
				$query .= " {$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
					
			}
			unset($cond);
		}
		return $query;
	}

	//! Generate SELECT query
	private function analyze_select_query()
	{	$query = 'SELECT';
		foreach($this->select_fields as $field)
		{	if (strcasecmp($field, 'count(*)') === 0)
			{	$fields[] = 'count(*)';
				continue;
			}
			$fields[] = (($this->ljoin !== NULL)?'p.':'') . "`" . $this->model->field_info($field, 'sqlfield') . "`";
		}

		$query .= ' ' . implode(', ', $fields);
		$query .= ' FROM `' . $this->model->table() . '`' . (($this->ljoin !== NULL)?' p':'');

        // Left join
        if ($this->ljoin !== NULL)
        {   if (! isset($this->ljoin['model']))
            {
                $lmodel_name = $this->ljoin['model_name'];
                $this->ljoin['model'] = call_user_func(array($lmodel_name, 'model'));
            }
            $lfield = $this->ljoin['model']->field_info($this->ljoin['join_foreign_field'], 'sqlfield');
            $pfield = $this->model->field_info($this->ljoin['join_local_field'], 'sqlfield');   
            $query .= " LEFT JOIN `{$this->ljoin['model']->table()}` l ON l.`{$lfield}` = p.`{$pfield}`";
        }
        
		// Conditions
		$query .= $this->analyze_where_conditions();
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . 
			    (($this->ljoin !== NULL)?' p.':'') .
			    $this->model->field_info($this->order_by['field'], 'sqlfield') .
				' ' . $this->order_by['order'];
		// Limit
		if ($this->limit !== NULL)
		{	if ($this->limit['offset'] !== NULL)
				$query .= " LIMIT {$this->limit['offset']},{$this->limit['length']}";
			else
				$query .= " LIMIT {$this->limit['length']}";
		}
		return $query;
	}
	
	//! Generate UPDATE query
	private function analyze_update_query()
	{	$query = 'UPDATE `' . $this->model->table() . '` SET';
	
		if (count($this->set_fields) === 0)
			throw new InvalidArgumentException("Cannot execute update() command without using set()");
			
		foreach($this->set_fields as $params)
		{
			$set_query = "`" . $this->model->field_info($params['field'], 'sqlfield') . "` = ";
			if ($params['value'] === NULL)
				$set_query .= '?';
			else
				$set_query .= "'" . DB_Conn::escape_string($params['value']) . "'"; 
			$fields[] = $set_query;
		}
		$query .= ' ' . implode(', ', $fields);
		$query .= $this->analyze_where_conditions();
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . $this->model->field_info($this->order_by['field'], 'sqlfield') .
				' ' . $this->order_by['order'];
		// Limit
		if ($this->limit !== NULL)
			$query .= " LIMIT {$this->limit['length']}";
		return $query;
	}
	
	//! Generate INSERT query
	private function analyze_insert_query()
	{	$query = 'INSERT INTO ' . $this->model->table();
	
		if (count($this->insert_fields) === 0)
			throw new InvalidArgumentException("Cannot execute insert() with no fields!");
			
		foreach($this->insert_fields as $field)
			$fields[] = "`" . $this->model->field_info($field, 'sqlfield') . "`";

		$query .= ' (' . implode(', ', $fields) . ') VALUES';
		if (count($this->insert_values) === 0)
			throw new InvalidArgumentException("Cannot insert() with no values, use values() to define them.");

		foreach($this->insert_values as $values_series)
		{	$values = array();
			foreach($values_series as $value)
				if ($value === NULL)
					$values[] = '?';
				else
					$values[] = "'" . DB_Conn::escape_string($value) . "'";
			$query .= ' (' . implode(', ', $values) . ')'; 
		}
		return $query;
	}
	
	//! Analyze DELETE query
	private function analyze_delete_query()
	{	$query = 'DELETE FROM ' . $this->model->table();
		$query .= $this->analyze_where_conditions();
		
		// Order by
		if ($this->order_by !== NULL)
			$query .= ' ORDER BY ' . 
				$this->model->field_info($this->order_by['fielld'], 'sqlfield') .
				' ' . $this->order_by['order'];
		
		// Limit
		if ($this->limit !== NULL)
			$query .= " LIMIT {$this->limit['length']}";
		return $query;
	}


	//! Get cache hint for caching query results
	public function cache_hints()
	{   // Return if it is already generated
	    if ($this->cache_hints !== NULL)
	        return $this->cache_hints;

        // Check that it is no longer altera ble
	    if ($this->sql_query === NULL)
	        return NULL;
	        
	    // Initialize array
	    $this->cache_hints = array(
	        'cachable' => ($this->query_type === 'select'),
	        'invalidate_on' => array()
	    );

        // Left joins are not cachable
	    if ($this->ljoin !== NULL)
	        $this->cache_hints['cachable'] = false;

	    return $this->cache_hints;
	}
	
	//! Create the sql command for this query
	/**
	 * Executing sql() will make query non-alterable and fixed,
	 * however you can use execute() multiple times.
	 * @return The string with SQL command.
	 */
	public function sql()
	{	// Check if sql has been already crafted
		if ($this->sql_query !== NULL)
			return $this->sql_query;
		
		// Check model cache
		$cache = $this->model->fetch_cache($this->sql_hash, $succ);
		if ($succ)
		{	$this->sql_query = $cache['query'];
		    $this->cache_hints = $cache['cache_hints'];
			return $this->sql_query;
		}
		
		if ($this->query_type === 'select')
			$this->sql_query = $this->analyze_select_query();
		else if ($this->query_type === 'update')
			$this->sql_query = $this->analyze_update_query();
		else if ($this->query_type === 'delete')
			$this->sql_query = $this->analyze_delete_query();
		else if ($this->query_type === 'insert')
			$this->sql_query = $this->analyze_insert_query();
		else
			throw new RuntimeException('Query is not finished to be exported.' .
				' You have to use at least one of the main commands insert()/update()/delete()/select(). ');

        // Cache hints
        $this->cache_hints();
        
		// Save in cache
		$this->model->push_cache($this->sql_hash, 
		    array(
		        'query' => $this->sql_query,
		        'cache_hints' => $this->cache_hints
		    )
		);
        
		return $this->sql_query;
	}
	
	//! Force preparation of statement
	/**
	 * Prepare this statement if it is not yet. Otherwise don't do nothing.
	 * @note Statements are prepared automatically at execution time.
	 * @return NULL
	 */
	public function prepare()
	{	if (!DB_Conn::is_key_used($this->sql_hash))
			return DB_Conn::prepare($this->sql_hash, $this->sql());
	}
	
	//! Execute statement and return the results
	public function execute()
	{	// Merge pushed parameters with functions
		$params = func_get_args();		
		$params = array_merge($this->exec_params, $params);
		
		// Prepare query
		$this->prepare();

		// Check cache if select
		if ($this->query_type === 'select')
		{
			$data = $this->query_cache->fetch_results($this, $params, $succ);
			if ($succ)
				return $data;
		}

		// Execute query
		if ($this->query_type === 'select')
			$data = DB_Conn::execute_fetch_all($this->sql_hash, $params);
		else
			$data = DB_Conn::execute($this->sql_hash, $params);

		// User wrapper
		if ($this->data_wrapper_callback !== NULL)
		{	$data = call_user_func($this->data_wrapper_callback, $data, $this->model);		}

		// Cache it
		$this->query_cache->process_query($this, $params, $data);
		
		// Return data
		return $data;
	}
}

?>
