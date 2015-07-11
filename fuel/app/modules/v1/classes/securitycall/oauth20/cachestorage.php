<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  Redis storage object for fkooman's OAuth 2.0 client
 *  Original source code may be found at @link https://github.com/fkooman/php-oauth-client
 */

namespace V1\SecurityCall\OAuth20;

use \fkooman\OAuth\Client\Context as Context;
use \fkooman\OAuth\Client\AccessToken as AccessToken;
use \fkooman\OAuth\Client\RefreshToken as RefreshToken;
use \fkooman\OAuth\Client\State as State;

class CacheStorage implements \fkooman\OAuth\Client\StorageInterface
{
	private $cacheId = null;
	
	public function __construct($cacheId)
	{
		$this->cacheId = $cacheId;
	}
	
    public function getAccessToken($clientConfigId, Context $context)
    {
        try {
        	$access_token_cache = \Cache::get('php-oauth-client.access_token.'.$this->cacheId);
        } catch (\CacheNotFoundException $e) {
        	return false;
        }
        
        foreach ($access_token_cache as $t) {
            $token = unserialize($t);
            if ($clientConfigId !== $token->getClientConfigId()) {
                continue;
            }
            if ($context->getUserId() !== $token->getUserId()) {
                continue;
            }
            if (!$token->getScope()->hasScope($context->getScope())) {
                continue;
            }

            return $token;
        }

        return false;
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
    	try {
    		$access_token_cache = \Cache::get('php-oauth-client.access_token.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		$access_token_cache = array();
    	}

        array_push($access_token_cache, serialize($accessToken));
        \Cache::set('php-oauth-client.access_token.'.$this->cacheId, $access_token_cache, null);

        return true;
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        try {
        	$access_token_cache = \Cache::get('php-oauth-client.access_token.'.$this->cacheId);
        } catch (\CacheNotFoundException $e) {
        	return false;
        }

        foreach ($access_token_cache as $k => $t) {
            $token = unserialize($t);
            if ($accessToken->getAccessToken() !== $token->getAccessToken()) {
                continue;
            }
            unset($access_token_cache[$k]);
            \Cache::set('php-oauth-client.access_token.'.$this->cacheId, $access_token_cache, null);

            return true;
        }

        return false;
    }

    public function getRefreshToken($clientConfigId, Context $context)
    {
    	try {
    		$refresh_token_cache = \Cache::get('php-oauth-client.refresh_token.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		return false;
    	}

        foreach ($refresh_token_cache as $t) {
            $token = unserialize($t);
            if ($clientConfigId !== $token->getClientConfigId()) {
                continue;
            }
            if ($context->getUserId() !== $token->getUserId()) {
                continue;
            }
            if (!$token->getScope()->hasScope($context->getScope())) {
                continue;
            }

            return $token;
        }

        return false;
    }

    public function storeRefreshToken(RefreshToken $refreshToken)
    {
    	try {
    		$refresh_token_cache = \Cache::get('php-oauth-client.refresh_token.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		$refresh_token_cache = array();
    	}

        array_push($refresh_token_cache, serialize($refreshToken));
        \Cache::set('php-oauth-client.refresh_token.'.$this->cacheId, $refresh_token_cache, null);

        return true;
    }

    public function deleteRefreshToken(RefreshToken $refreshToken)
    {
    	try {
    		$refresh_token_cache = \Cache::get('php-oauth-client.refresh_token.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		return false;
    	}

        foreach ($refresh_token_cache as $k => $t) {
            $token = unserialize($t);
            if ($refreshToken->getRefreshToken() !== $token->getRefreshToken()) {
                continue;
            }
            unset($refresh_token_cache[$k]);
            \Cache::set('php-oauth-client.refresh_token.'.$this->cacheId, $refresh_token_cache, null);

            return true;
        }

        return false;
    }

    public function getState($clientConfigId, $state)
    {
    	try {
    		$state_cache = \Cache::get('php-oauth-client.state.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		return false;
    	}
    	
        foreach ($state_cache as $s) {
            $sessionState = unserialize($s);

            if ($clientConfigId !== $sessionState->getClientConfigId()) {
                continue;
            }
            if ($state !== $sessionState->getState()) {
                continue;
            }
            
            return $sessionState;
        }
        
        return false;
    }

    public function storeState(State $state)
    {
    	try {
    		$state_cache = \Cache::get('php-oauth-client.state.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		$state_cache = array();
    	}

        array_push($state_cache, serialize($state));
        \Cache::set('php-oauth-client.state.'.$this->cacheId, $state_cache, null);
        
        return true;
    }

    public function deleteStateForContext($clientConfigId, Context $context)
    {
    	try {
    		$state_cache = \Cache::get('php-oauth-client.state.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		return false;
    	}
    	
        foreach ($state_cache as $k => $s) {
            $sessionState = unserialize($s);
            if ($clientConfigId !== $sessionState->getClientConfigId()) {
                continue;
            }
            if ($context->getUserId() !== $sessionState->getUserId()) {
                continue;
            }
            unset($state_cache[$k]);
            \Cache::set('php-oauth-client.state.'.$this->cacheId, $state_cache, null);

            return true;
        }

        return false;
    }

    public function deleteState(State $state)
    {
    	try {
    		$state_cache = \Cache::get('php-oauth-client.state.'.$this->cacheId);
    	} catch (\CacheNotFoundException $e) {
    		return false;
    	}

        foreach ($state_cache as $k => $s) {
            $sessionState = unserialize($s);
            if ($state->getState() !== $sessionState->getState()) {
                continue;
            }
            unset($state_cache[$k]);
            \Cache::set('php-oauth-client.state.'.$this->cacheId, $state_cache, null);

            return true;
        }

        return false;
    }
}
