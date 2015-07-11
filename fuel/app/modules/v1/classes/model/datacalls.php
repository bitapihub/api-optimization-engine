<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the data_calls table
 */

namespace V1\Model;

class DataCalls extends \Orm\Model_Soft
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
    	'call_script'	=> array(

    		'data_type'	=> 'text',
    		
    	),
    	'active_level'	=> array(
		
			'data_type' => 'tinyint',
			'character_maximum_length' => 1
		
		),
    	'min_access_level'	=> array(
		
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
    protected static $_table_name = 'data_calls';
    
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
     * Get the row of Data Call data from the DB.
     *
     * @param string $data_call The name of the Data Call to pull data for or null to grab the posted Data Call
     * @return array The array of data for the Data Call from the DB, or an empty array
     */
    public static function get_data_call($data_call = null)
    {
    	$data_call		= empty($data_call) ? \V1\APIRequest::get('data-call') : $data_call;
    	$account_data	= \V1\Model\Account::get_account();
    	 
    	// Select the chosen one.
    	$data_call_data = static::query()
	    	->where('name', '=', $data_call)
	    	->and_where_open()
	    		->where('account_id', '=', 0)
	    		->or_where('account_id', '=', $account_data['id'])
	    	->and_where_close()
	    	->get_one();
    	 
    	if (!empty($data_call_data)) {
    		
    		$return = array();
    		
    		// Turn the object into an array (You can't just cast it to an array unfortunately.)
    		foreach ($data_call_data as $key => $value) {
    			$return[$key] = $value;
    		}
    		
    		return $return;
    
    	} else {
    		return array();
    	}
    }
}
