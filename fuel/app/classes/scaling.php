<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Scaling class to factor in multiple servers running the same scripts, while sharing the same resources
 */

class Scaling
{
	/**
	 * @var string $lock_prefix The cache prefix for lock entries
	 * @access protected
	 */
	protected static $lock_prefix = 'locks';
	
	/**
	 * Set a lock for an operation
	 *
	 * @param string $lock_id	The cache key used for locking
	 * @param int $expiration	The number of seconds that the lock is valid for (In case something goes wrong)
	 * @param int $max_wait		The maximum number of microseconds to allow a machine to wait while avoiding a race
	 * 							condition. (Default: 2 seconds (2000000 microseconds)
	 *
	 * @return boolean True if we have the lock, false if we don't.
	 */
	public static function set_lock($lock_id, $expiration, $max_wait = 2000000)
	{
		// Check if an instance is processing
		try {
			\Cache::get(static::$lock_prefix.'.'.$lock_id);
		} catch(\CacheNotFoundException $e) {
			
			// Set our unique ID
			$uniqueid = uniqid(mt_rand(0, mt_getrandmax()), true);
			\Cache::set(static::$lock_prefix.'.'.$lock_id, $uniqueid, $expiration);
	
			// Avoid a race condition where two machines enter the catch block. 
			usleep(mt_rand(0, 5000));
			
			// See if we got the lock, or if another instance did.
			if (\Cache::get(static::$lock_prefix.'.'.$lock_id) === $uniqueid) {
				return true;
			}
			 
		}
		 
		return false;
	}
	
	/**
	 * Removes a lock with the provided ID
	 * 
	 * @param string $lock_id The cache key for the lock
	 */
	public static function remove_lock($lock_id)
	{
		\Cache::delete(static::$lock_prefix.'.'.$lock_id);
	}
}
