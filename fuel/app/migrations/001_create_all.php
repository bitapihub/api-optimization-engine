<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  Initial migration
 */

namespace Fuel\Migrations;

class Create_All
{
	function up()
	{
		\DBUtil::create_table('accounts', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'consumer_key' => array('type' => 'varchar', 'constraint' => 36),
			'consumer_secret' => array('type' => 'varchar', 'constraint' => 122),
			'access_level' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'max_calls' => array('type' => 'int', 'constraint' => 1, 'default' => 0),
			'reset_usage' => array('type' => 'int', 'constraint' => 11),
			'free_account_on' => array('type' => 'int', 'constraint' => 11),
			'can_run_inactive' => array('type' => 'int', 'constraint' => 1, 'default' => 0),
			'acl_type' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'link_back' => array('type' => 'int', 'constraint' => 1, 'default' => 0),
			'js_calls_allowed' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'store_credentials' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'deleted_at' => array('type' => 'varchar', 'constraint' => 11, 'default' => null, 'null' => true),
		), array('id'));
		
		\DBUtil::create_table('accounts_metadata', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'account_id' => array('type' => 'int', 'constraint' => 11),
			'key' => array('type' => 'varchar', 'constraint' => 20),
			'value' => array('type' => 'text'),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'deleted_at' => array('type' => 'varchar', 'constraint' => 11, 'default' => null, 'null' => true),
		), array('id'));
		
		\DBUtil::create_table('apis', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'account_id' => array('type' => 'int', 'constraint' => 11),
			'name' => array('type' => 'varchar', 'constraint' => 50),
			'min_access_level' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'active_level' => array('type' => 'int', 'constraint' => 1),
			'private' => array('type' => 'int', 'constraint' => 1),
			'secret' => array('type' => 'varchar', 'constraint' => 122),
			'force_validation' => array('type' => 'int', 'constraint' => 1),
			'allow_custom_dynamic' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'deleted_at' => array('type' => 'varchar', 'constraint' => 11, 'default' => null, 'null' => true),
		), array('id'));
		
		\DBUtil::create_table('apis_metadata', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'apis_id' => array('type' => 'int', 'constraint' => 11),
			'key' => array('type' => 'varchar', 'constraint' => 20),
			'value' => array('type' => 'text'),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'deleted_at' => array('type' => 'varchar', 'constraint' => 11, 'default' => null, 'null' => true),
		), array('id'));
		
		\DBUtil::create_table('api_stats', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'apis_id' => array('type' => 'int', 'constraint' => 11),
			'code' => array('type' => 'int', 'constraint' => 3),
			'call' => array('type' => 'varchar', 'constraint' => 150),
			'is_static' => array('type' => 'int', 'constraint' => 1),
			'count' => array('type' => 'int', 'constraint' => 11),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
		), array('id'));
		
		\DBUtil::create_table('data_calls', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'account_id' => array('type' => 'int', 'constraint' => 11),
			'name' => array('type' => 'varchar', 'constraint' => 50),
			'call_script' => array('type' => 'text'),
			'active_level' => array('type' => 'int', 'constraint' => 1),
			'min_access_level' => array('type' => 'int', 'constraint' => 1, 'default' => 1),
			'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			'deleted_at' => array('type' => 'varchar', 'constraint' => 11, 'default' => null, 'null' => true),
		), array('id'));
	}

	function down()
	{
		\DBUtil::drop_table('accounts');
		\DBUtil::drop_table('accounts_metadata');
		\DBUtil::drop_table('apis');
		\DBUtil::drop_table('apis_metadata');
		\DBUtil::drop_table('api_stats');
		\DBUtil::drop_table('data_calls');
	}
}
