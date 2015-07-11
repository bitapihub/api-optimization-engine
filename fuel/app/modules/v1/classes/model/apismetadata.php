<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the apis table
 */

namespace V1\Model;

class APIsMetaData extends \Orm\Model_Soft
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
    	'apis_id'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11
		
		),
    	'key'	=> array(

    		'data_type'					=> 'varchar',
    		'character_maximum_length'	=> 20
    		
    	),
    	'value'	=> array(

    		'data_type'	=> 'text',
    		
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
    protected static $_table_name = 'apis_metadata';
    
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
	
		'apis' => array(
	
			'key_from'	=> 'apis_id',
			'key_to'	=> 'id',
			'model_to'	=> '\\V1\\Model\\APIs'
	
		)
	
	);
    
    /**
     * Set the new credentials for the remote API access on the given API
     * 
     * @param string $credentials	The encrypted credentials to set
     * @param int $api_id			The ID of the API row in the DB
     * 
     * @return boolean True if it saved, or false if it didn't
     */
    public static function set_credentials($credentials, $api_id = null)
    {
    	if (empty($api_id)) {
    		
    		$apis_data = \V1\Model\APIs::get_api();
    		$api_id = (int) $apis_data['id'];
    		
    	}
    	
    	$apis_meta_obj = static::query()
    		->where('apis_id', '=', $api_id)
    		->where('key', '=', 'credentials')
    		->get_one();
    	
    	if (empty($apis_meta_obj)) {
    		
    		$apis_meta_obj = new static;
    		$apis_meta_obj->apis_id	= $api_id;
    		$apis_meta_obj->key		= 'credentials';
    		$apis_meta_obj->value	= $credentials;
    		
    		return $apis_meta_obj->save();
    		
    	}
    	
    	$apis_meta_obj->value = $credentials;
    	return $apis_meta_obj->save();
    }
}
