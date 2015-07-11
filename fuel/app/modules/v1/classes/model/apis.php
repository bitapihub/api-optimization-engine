<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the apis table
 */

namespace V1\Model;

class APIs extends \Orm\Model_Soft
{
    /**
     *  @var array $_properties The array of column names
     *  @access protected
     */
    protected static $_properties = array(
    	
    	'id'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11
		
		),
    	'account_id'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11
		
		),
    	'name'	=> array(

    		'data_type'					=> 'varchar',
    		'character_maximum_length'	=> 50
    		
    	),
    	'min_access_level'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'active_level'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'private'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'secret'	=> array(

    		'data_type'					=> 'varchar',
    		'character_maximum_length'	=> 122
    		
    	),
    	'force_validation'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'allow_custom_dynamic'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1,
    		'default'	=> 1
		
		),
		'created_at'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
		'updated_at'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
		'deleted_at'	=> array(
		
			'data_type' => 'varchar',
			'character_maximum_length' => 11,
			'default'	=> null,
		
		),
    	
    );
    
    /**
     *  @var string $_table_name The table name
     *  @access protected
     */
    protected static $_table_name = 'apis';
    
    /**
     *  @var string $_connection The name of the DB connection as defined in the db.php config file
     *  @access protected
     */
    protected static $_connection = 'default';
    
    /**
     * @var array $_observers The observers to use when interacting with this model
     * @access protected
     */
    protected static $_observers = array(
	    'Orm\\Observer_Typing',
		'Orm\\Observer_CreatedAt',
		'Orm\\Observer_UpdatedAt',
    );
    
    /**
     * @var array $_belongs_to Map the table to the remote table
     */
    protected static $_belongs_to = array(
	
		'account' => array(
	
			'key_from'	=> 'account_id',
			'key_to'	=> 'id',
			'model_to'	=> '\\V1\\Model\\Account'
	
		)
	
	);

	/**
	 * @var array	has_many relationships (Polyamorous)
	 */
	protected static $_has_many = array(
		
		'metadata' => array(
			
			'model_to' => '\\V1\\Model\\APIsMetaData',
			'key_from' => 'id',
			'key_to'   => 'apis_id',
			
		),
		'apistats' => array(
				
			'model_to' => '\\V1\\Model\\APIStats',
			'key_from' => 'id',
			'key_to'   => 'apis_id',
				
		),
		
	);
    
    /**
     * Get the row of API data from the DB.
     * 
     * @param string $api The name of the API to pull data for or null to grab the posted API
     * @return array The array of data for the API from the DB, or an empty array
     */
    public static function get_api($api = null)
    {
    	$api			= empty($api) ? \V1\APIRequest::get('api') : $api;
    	$account_data	= \V1\Model\Account::get_account();
    	
    	// Select the chosen one.
    	$api_data = static::query()
    		->where('name', '=', $api)
    		->and_where_open()
	    		->where('private', '=', 0)
	    		->or_where_open()
	    			->where('private', '=', 1)
	    			->where('account_id', '=', $account_data['id'])
	    		->or_where_close()
    		->and_where_close()
    		->get_one();
    	
    	if (!empty($api_data)) {
    		
    		// Default
    		$return = array();
    		
    		// Turn the object into an array
    		foreach ($api_data as $key => $value) {
    			
    			// Don't add the metadata object
    			if (!in_array($key, array('apistats', 'metadata'))) {
    				$return[$key] = $value;
    			}
    			
    		}
    		
    		// Grab the metadata and stick it on the return array, too.
    		foreach ($api_data->metadata as $metadata) {
    			$return[$metadata->key] = $metadata->value;
    		}
    		
    		// Spit out all of that data.
    		return $return;
    		
    	} else {
    		return array();
    	}
    }
    
    /**
     * Get the ID for a given API name
     * 
     * @param string $api_name	The name of the API
     * @param bool $public_only	Set to true to exclude account owned APIs
     * 
     * @return mixed The API ID or false on fail
     */
    public static function get_api_id($api_name, $public_only = false)
    {
    	// Select the chosen one.
    	$api_data = static::query()
	    	->where('name', '=', $api_name)
		    	->and_where_open()
		    	->where('private', '=', 0);
			    
    	if ($public_only === false) {
    		
    		$account_data = \V1\Model\Account::get_account();
    		
    		$api_data->or_where_open()
	    		->where('private', '=', 1)
	    		->where('account_id', '=', $account_data['id'])
    		->or_where_close();
    		
    	}
		    	
		$row = $api_data->and_where_close()
			->get_one();
		
		if (!empty($row['id'])) {
			return $row['id'];
		}
		
		return false;
    }
    
    /**
     * Generate a new secret string for the API in the database.
     * 
     * @param int $api_id The ID of the API for which to generate a secret string
     * @return boolean|string The newly generated ID or false on fail
     */
    public static function set_api_secret($api_id)
    {
    	if (!is_int($api_id)) {
    		return false;
    	}
    	
    	$secret = \Utility::generate_random_id();
    	
    	$api = static::find($api_id);
    	$api->secret = \Crypt::encode($secret);
    	$api->save();
    	
    	return $secret;
    }
}
