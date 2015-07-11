<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the accounts table
 */

namespace V1\Model;

class Account extends \Orm\Model_Soft
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
    	'consumer_key'	=> array(

    		'data_type'					=> 'varchar',
    		'character_maximum_length'	=> 36
    		
    	),
    	'consumer_secret'	=> array(

    		'data_type'					=> 'varchar',
    		'character_maximum_length'	=> 122
    		
    	),
    	'access_level'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 1
    		
    	),
    	'max_calls'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11
		
		),
    	'reset_usage'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
    		'default'	=> null,
		
		),
    	'free_account_on'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11
		
		),
    	'can_run_inactive'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'acl_type'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'link_back'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'js_calls_allowed'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'store_credentials'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
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
    protected static $_table_name = 'accounts';
    
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
     * @var array $_has_many Map the table to the remote table
     */
    protected static $_has_many = array(
    
    	'apis' => array(
    
    		'key_from'	=> 'id',
    		'key_to'	=> 'account_id',
    		'model_to'	=> '\\V1\\Model\\APIs',
    
    	),
    	
    	'metadata' => array(
    	
    		'key_from'	=> 'id',
    		'key_to'	=> 'account_id',
    		'model_to'	=> '\\V1\\Model\\AccountsMetaData',
    	
    	)
    
    );
    
    /**
     * Set the class up
     */
    public static function _init()
    {
    	// Load the configuration file.
    	\Config::load('tiers', true);
    }
    
    /**
     * Return a list of account_data
     * 
     * @param $consumer_key string The consumer key for the account
     * @return array The array of account data from the DB, or an empty array if no row exists
     */
    public static function get_account($consumer_key = null)
    {
    	// Use the consumer key from the session if we didn't pass it as a parameter.
    	if (empty($consumer_key)) {
    		$consumer_key = \Session::get('consumer_key', null);
    	}
    	
    	$result = static::query()
    				->where('consumer_key', '=', $consumer_key)
    				->get_one();
    	
    	if (!empty($result)) {
    		
    		// Default
    		$return = array();
    		
    		// Turn the \Account object into an array.
    		foreach ($result as $key => $val) {
    			
    			// Don't store the objects.
    			if (!in_array($key, array('apis', 'metadata'))) {
    				$return[$key] = $val;
    			}
    			
    		}
    		
    		// Grab the metadata.
    		foreach ($result->metadata as $metadata) {
    			$return[$metadata->key] = $metadata->value;
    		}
    		
    		return $return;
    		
    	} else {
    		return array();
    	}
    }
    
    /**
     * Set the account free (free tier)
     * 
     * @return boolean True if successful, false if it fails
     */
    public static function be_free_my_brother()
    {
    	// Get the cached row
    	$account_data = static::get_account();
    	
    	// No data
    	if (empty($account_data)) {
    		return false;
    	}
    	
    	// Pull the row so that we have an object to use for Orm manipulation.
    	$account_row = static::find($account_data['id']);
    	$account_row->free_account_on	= 0;
    	$account_row->access_level		= 1;
    	$account_row->max_calls			= \Config::get('tiers.free.max_calls', 10000);
    	
    	$saved = $account_row->save();
    	
    	\V1\Usage::delete_usage();
    	
    	return $saved;
    }
    
    /**
     * Update the list of IPs used to access the account.
     * 
     * @param array $ips_used The array of IPs that have access the account
     * @return boolean True if the account was updated, or false if it was not.
     */
    public static function set_used_ips($ips_used)
    {
    	// Track the server IPs contacting the account, but don't track client IPs from JS calls.
    	if (\Session::get('public', true) === false) {
    		return true;
    	}
    	
    	$account_data = static::get_account();
    	
    	// If we already have the entry...
    	$account = static::find($account_data['id']);
    	
    	foreach ($account->metadata as $metadata) {
    		
    		if ($metadata->key == 'ips_used') {
    			
    			$metadata->value = json_encode($ips_used);
    			return $metadata->save();
    			
    		}
    		
    	}
    	
    	// Create a new list of IPs.
    	$new_meta = new \V1\Model\AccountsMetaData;
    	
    	$new_meta->account_id	= $account_data['id'];
    	$new_meta->key			= 'ips_used';
    	$new_meta->value		= json_encode($ips_used);
    	
    	return $new_meta->save();
    }
    
    /**
     * Set the reset_usage value in the DB
     * 
     * @param string $value The value to set in the DB - Can be null
     * @return boolean True if the update succeeded, or false if it didn't
     */
    public static function set_reset_usage($value = null)
    {
    	$account_data = static::get_account();
    	
    	$account_obj = static::find($account_data['id']);
    	$account_obj->reset_usage = $value;
    	return $account_obj->save();
    }
}
