<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Add system owned credentials for APIs
 */

namespace Fuel\Tasks;

class APICredentials
{
	
	/**
	 * Run the credential importer
	 * 
	 * @param string $json_file The JSON file that contains a list of the APIs and their credentials
	 */
	public static function run($json_file = null)
	{
		if (empty($json_file) || file_exists($json_file) === false) {
			exit('You must specify a valid JSON file that contains your credentials.'.PHP_EOL);
		}
		
		if (($json = json_decode(file_get_contents($json_file), true)) === null) {
			exit('The JSON file does not contain valid JSON text.'.PHP_EOL);
		}
		
		// Find the API version to use for importing the keys
		$version = 'V1';
		if (!empty($json[0]['version'])) {
			
			if (is_int($json[0]['version']) && \Module::exists('V'.$json[0]['version'])) {

				\Module::load('V'.$json[0]['version']);
				$version = 'V'.$json[0]['version'];
				
			} else {
				\Module::load($version);
			}
			
			array_shift($json);
			
		} else {
			\Module::load($version);
		}
		
		$error = false;
		
		foreach ($json as $entry) {
			
			// We need these keys for each entry.
			if (!array_key_exists('api', $entry) || !array_key_exists('credentials', $entry)) {
				
				echo \Cli::color('The JSON data is in the wrong format. Skipping.'.PHP_EOL, 'yellow');
				$error = true;
				continue;
				
			}
			
			if (!is_string($entry['api'])) {
				
				echo \Cli::color('The API name must be a string. Skipping.'.PHP_EOL, 'yellow');
				$error = true;
				continue;
				
			}
			
			// Make sure that we have credentials to add to the DB.
			if (empty($entry['credentials']) || !is_array($entry['credentials'])) {
				
				echo \Cli::color('The array of credentials for '.$entry['api'].' is empty. Skipping.'.PHP_EOL, 'yellow');
				$error = true;
				continue;
				
			}
			
			$response = call_user_func('\\'.$version.'\\Keyring::set_credentials', $entry['credentials'], $entry['api']);
			
			// Show and log the result
			if ($response === true) {
				
				$success_text = 'Successfully imported the credentials for API: '.$entry['api'];
				echo \Cli::color($success_text.PHP_EOL, 'green');
				\Log::logger('INFO', 'CLI:ADD_CREDENTIALS', $success_text, __METHOD__, array('api' => $entry['api']));
				
			} else {
				
				$error_text = 'Failed to import the credentials for API: '.$entry['api'];
				echo \Cli::color('Warning: '.$error_text.PHP_EOL, 'red');
				$error = true;
				\Log::logger('ERROR', 'CLI:ADD_CREDENTIALS', $error_text, __METHOD__, array('api' => $entry['api']));
				
			}
			
		}
			
		// Display the summary.
		if ($error === true) {
			echo \Cli::color(PHP_EOL.'Some credentials were not added to the database. See the error log for more details.'.PHP_EOL, 'red');
		} else {
			echo \Cli::color(PHP_EOL.'All credentials were successfully added to the database.'.PHP_EOL, 'green');
		}
	}
	
	/**
	 * Display helpful information about how to use this task.
	 */
	public static function help()
	{
		echo <<<HELP

            Description:
                This task adds new system owned credentials to the DB for static calls. If any credentials exist,
                this task will overwrite them. Back up your database before you proceed.
			
            Usage:
                php oil refine apicredentials /path/to/json/file

            JSON Format:
                [
                    {
                        "version": 1
                    },
                    {
                        "api": "your_api",
                        "credentials": {
                            "OAUTH_CONSUMER_KEY": "your key",
                            "OAUTH_CONSUMER_SECRET": "your secret",
                            "OAUTH_USER_ID": "your user id"
                        }
                    },
                    {
                        "api": "second_api",
                        "credentials": {
                            "OAUTH2_CLIENT_ID": "your client id",
                            "OAUTH2_CLIENT_SECRET": "your client secret",
                            "OAUTH2_USER_ID": "your user id"
                        }
                    }
			        
                    ...
                ]

HELP;
	}
}
