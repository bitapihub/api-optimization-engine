<?php
/**
 *  Copyright 2015 Bit API Hub
 *  
 *  HTTP exception classes
 */

class HttpNotFoundException extends HttpException
{
	public function response()
	{
		return new \Response(\Utility::prepare_response(\Utility::format_error(404)), 404);
	}
}

class HttpNoAccessException extends HttpException
{
	public function response()
	{
		return new \Response(\Utility::prepare_response(\Utility::format_error(403)), 403);
	}
}

class HttpServerErrorException extends HttpException
{
	public function response()
	{
		return new \Response(\Utility::prepare_response(\Utility::format_error(500)), 500);
	}
}

class HttpBadRequestException extends HttpException
{
	public function response()
	{
		return new \Response(\Utility::prepare_response(\Utility::format_error(400)), 400);
	}
}
