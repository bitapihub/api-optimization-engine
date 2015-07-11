<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Keyring manager for API keys
 */

namespace V1;

class Keyring
{
	/**
	 * Return the credentials for the call.
	 * 
	 * @return mixed The array of credentials or an empty array if we couldn't find them, or false if the supplied
	 * 					credentials were in an invalid format
	 */
	public static function get_credentials()
	{
		if (\V1\APIRequest::is_static()) {
			
			return static::decode_credentials(\V1\Model\APIs::get_api());
			
		} else {
			
			$account_data = \V1\Model\Account::get_account();
			
			/**
			 * Check for stored credentials
			 */
			$formatted_credentials = static::decode_credentials($account_data);
			
			/**
			 * Credentials provided through the request
			*/
			if (is_array($posted_credentials = \V1\APIRequest::get('auth', false)) && !empty($posted_credentials)) {
				
				foreach ($posted_credentials as $variable => $value) {
					
					// Bad format
					if (!is_string($value)) {
						return false;
					}
					
					$formatted_credentials[\Str::upper($variable)] = $value;
					
				}
				
				// Do they want the credentials stored in the DB?
				if ($account_data['store_credentials'] === 1) {
					
					// Store their credentials encrypted.
					$credentials[\V1\APIRequest::get('api')] = $formatted_credentials;
					$existing_credentials = static::decode_credentials($account_data);
					$credentials = array_replace_recursive($existing_credentials, $credentials);
					$credentials_encrypted = \Crypt::encode(json_encode($credentials));
					
					\V1\Model\AccountsMetaData::set_credentials($account_data['id'], $credentials_encrypted);
					
				}
				
			}
			
			return $formatted_credentials;
			
		}
	}
	
	/**
	 * Set the new credentials in the DB.
	 * 
	 * @param array $credentials	The array of new credentials for the API
	 * @param string $api_name		The name of the API to set the credentials for or empty for the current API
	 * 
	 * @return bool True on success or false on fail
	 */
	public static function set_credentials(array $credentials, $api_name = null)
	{
		// Specific API
		if (!empty($api_name)) {
			
			if (($api_id = \V1\Model\APIs::get_api_id($api_name, true)) === false) {
				return false;
			}
			
			$credentials_encrypted = \Crypt::encode(json_encode($credentials));
			\V1\Model\APIsMetaData::set_credentials($credentials_encrypted, $api_id);
			return true;
			
		}
			
		// Figure out what to set based on the current request
		if (\V1\APIRequest::is_static() === true) {
		
			$credentials_encrypted = \Crypt::encode(json_encode($credentials));
			return \V1\Model\APIsMetaData::set_credentials($credentials_encrypted);
			
		} else {
			
			$account_data = \V1\Model\Account::get_account();
			
			// Only store them their credentials if the account holder wants us to.
			if ($account_data['store_credentials'] === 1) {

				$credentials[\V1\APIRequest::get('api')] = $credentials;
					
				if (!empty($account_data['credentials'])) {
				
					$account_data['credentials'] = json_decode(\Crypt::decode($account_data['credentials']), true);
					$account_data['credentials'][\V1\APIRequest::get('api')] = $credentials[\V1\APIRequest::get('api')];
					$credentials = $account_data['credentials'];
				
				}
				
				$credentials_encrypted = \Crypt::encode(json_encode($credentials));
				return \V1\Model\AccountsMetaData::set_credentials($account_data['id'], $credentials_encrypted);
				
			}
			
			return true;
			
		}
	}
	
	/**
	 * Decode the credentials from the DB data array
	 * 
	 * @param array $array The array of data from the DB for the account or API to find the credentials for
	 * @return array The array of decoded credentials, or an empty array if none exist
	 */
	protected static function decode_credentials(array $array)
	{
		if (
			isset($array['credentials']) &&
			is_array($credentials = json_decode(\Crypt::decode($array['credentials']), true))
		) {
			
			if (\V1\APIRequest::is_static()) {
				
				// Bit API Hub credentials for the API
				return $credentials;
				
			} else {
				
				// Get the credentials for the specific API
				if (!empty($credentials[\V1\APIRequest::get('api')])) {
					return $credentials[\V1\APIRequest::get('api')];
				}
				
			}
			
		}
		
		// No credentials
		return array();
	}
}
