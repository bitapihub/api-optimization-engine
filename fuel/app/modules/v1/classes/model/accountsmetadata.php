<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the apis table
 */

namespace V1\Model;

class AccountsMetaData extends \Orm\Model_Soft
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
    protected static $_table_name = 'accounts_metadata';
    
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
     * Set the new credentials for the remote API access on the given account
     * 
     * @param int $account			The ID of the account
     * @param string $credentials	The encrypted credentials to set
     * 
     * @return boolean True if it saved, or false if it didn't
     */
    public static function set_credentials($account, $credentials)
    {
    	$account_meta_obj = static::query()
    		->where('account_id', '=', $account)
    		->where('key', '=', 'credentials')
    		->get_one();
    	
    	if (empty($account_meta_obj)) {
    		
    		$account_meta_obj = new static;
    		$account_meta_obj->account_id = $account;
    		$account_meta_obj->key = 'credentials';
    		$account_meta_obj->value = $credentials;
    		
    		return $account_meta_obj->save();
    		
    	}
    	
    	$account_meta_obj->value = $credentials;
    	return $account_meta_obj->save();
    }
}
