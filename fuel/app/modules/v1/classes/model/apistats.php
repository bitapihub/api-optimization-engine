<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  The ORM model for the api_stats table
 */

namespace V1\Model;

class APIStats extends \Orm\Model
{
    /**
     *  @var array $_properties The array of column names
     *  @access protected
     */
    protected static $_properties = array(
    	
    	'id'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
    	'apis_id'	=> array(
    	
    		'data_type' => 'int',
    		'character_maximum_length' => 11,
    	
    	),
    	'code'	=> array(
    	
    		'data_type' => 'int',
    		'character_maximum_length' => 3,
    	
    	),
    	'call'	=> array(
    	
    		'data_type' => 'varchar',
    		'character_maximum_length' => 150,
    	
    	),
    	'is_static'	=> array(
    	
    		'data_type' => 'tinyint',
    		'character_maximum_length' => 1,
    		'default' => 0,
    	
    	),
		'count'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
		'created_at'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
		'updated_at'	=> array(
		
			'data_type' => 'int',
			'character_maximum_length' => 11,
		
		),
    	
    );
    
    /**
     *  @var string $_table_name The table name
     *  @access protected
     */
    protected static $_table_name = 'api_stats';
    
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
	
		'api' => array(
	
			'key_from'	=> 'apis_id',
			'key_to'	=> 'id',
			'model_to'	=> '\\V1\\Model\\APIs'
	
		)
	
	);
    
    /**
     * Set an API statistic in the DB
     * 
     * @param int $code		The HTTP status code from the request or cache
     * @param string $uri	The dynamic call URL or the static call name
     * @param string $api	The API to set the stats for
     * 
     * @return bool True if the stat was added/updated successfully, or false if it wasn't.
     */
    public static function set_stat($code, $uri, $api = null, $is_static = null)
    {
    	// Save queries if we don't need the stats.
    	if (\Config::get('engine.track_usage_stats', false) === false) {
    		return true;
    	}
    	
    	$api_name	= ($api === null) ? \V1\APIRequest::get('api') : $api;
    	$is_static	= ($is_static === null) ? \V1\APIRequest::is_static() : $is_static;
    	
    	$api = \V1\Model\APIs::get_api($api_name);
    	
    	// Do we have a stat entry for this timeframe?
    	$existing_api_stats_obj = static::query()
    		->where('apis_id', '=', $api['id'])
    		->where('code', '=', (int) $code)
    		->where('call', '=', $uri)
    		->where('created_at', '>', time() - ((int) \Config::get('api_stats_increment', 30) * 60))
    		->get_one();
    	
    	// If we have a row, update it.
    	if (!empty($existing_api_stats_obj)) {
    		
    		$existing_api_stats_obj->count++;
    		return $existing_api_stats_obj->save();
    		
    	}
    	
    	// Add the new entry.
    	$api_stats_object = new static;
    	
    	$api_stats_object->apis_id		= $api['id'];
    	$api_stats_object->code			= $code;
    	$api_stats_object->count		= 1;
    	$api_stats_object->call			= $uri;
    	$api_stats_object->is_static	= intval($is_static);
    	
    	return $api_stats_object->save();
    }
}
