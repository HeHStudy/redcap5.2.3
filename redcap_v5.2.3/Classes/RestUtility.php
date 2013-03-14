<?php

class RestUtility
{
	/**
	 * 	@name		processRequest
	 *  @desc		processes an incoming api request
	 *  @return		an object of type RestRequest
	**/
	public static function processRequest()
	{
		$requestMethod 	= strtolower($_SERVER['REQUEST_METHOD']);
		$returnObject	= new RestRequest();
		$data			= array();
		
		# make sure only valid request methods were used
		switch ($requestMethod)
		{
			case 'post':
				$data = $_POST;
				break;
			default:
				die(RestUtility::sendResponse(501));
				break;
		}

		# check user rights
		$token = substr($data['token'], 0, 32);
		$sql = "SELECT u.project_id, u.group_id, u.api_export, u.api_import, u.expiration
				FROM redcap_user_rights u, redcap_user_information i
				WHERE u.api_token = '$token' 
				AND i.username = u.username
				AND i.user_suspended_time is null
				LIMIT 1";
		$userRights = db_query($sql);
		
		if (db_num_rows($userRights) < 1) {
			die(RestUtility::sendResponse(403));	# invalid token
		}
		/*elseif (db_result($userRights, 0, 1) == 0) {
			die(RestUtility::sendResponse(403));
		}*/
		
		# determine if user's rights for this project have expired
		$expiration = db_result($userRights, 0, 4);
		if ($expiration != "" && str_replace("-", "", $expiration) <= date('Ymd')) {
			die(RestUtility::sendResponse(403, "You do not have API rights because your privileges have expired for this project as of $expiration."));
		}

		// get the project id
		$data['projectid'] = db_result($userRights, 0, 0);
		
		// get the group id
		$data['dataAccessGroupId'] = db_result($userRights, 0, 1);

		# get access rights
		$data['api_export'] = db_result($userRights, 0, 2);
		$data['api_import'] = db_result($userRights, 0, 3);
		
		// store the method
		$returnObject->setMethod($requestMethod);
		
		// set the raw data, so we can access it later if needed
		$returnObject->setRequestVars($data);
		
		//if importing, need to save the data being uploaded
		if(isset($data['data']))
		{
			switch ($data['format'])
			{
				case 'json':
					$content = json_decode(html_entity_decode($data['data'], ENT_QUOTES), TRUE);
					if ($content == '') die(RestUtility::sendResponse(400, 'The data being imported is not formatted correctly'));
					$returnObject->setData($content, true);
					break;
				case 'xml':
					$content = Xml::decode(html_entity_decode($data['data'], ENT_QUOTES));
					if ($content == '') die(RestUtility::sendResponse(400, 'The data being imported is not formatted correctly'));
					$returnObject->setData($content);
					break;
				case 'csv':
					$returnObject->setData($data['data']);
					break;
			}
		}
		
		return $returnObject;
	}
	
	/**
	 * 	@name		processRequest
	 *  @desc		processes an incoming api request
	 *  @param 		status - integer - status code
	 *  @param		body - string - message body
	 *  @param		contentFormat - string - the format of the content being passed in
	 *  @return		string - page output
	**/
	public static function sendResponse($status = 200, $body = '', $contentFormat = '')
	{
		global $returnFormat;
		
		# set return format as same as content format if not provided
		if ($contentFormat == '') $contentFormat = $returnFormat;

		# set the content type
		switch ($contentFormat)
		{
			case 'json':
				$contentType = 'application/json';
				break;
			case 'csv':
				$contentType = 'text/html';
				break;
			case 'xml':
				$contentType = 'text/xml';
				break;
		}

		// set the status
		$statusHeader = 'HTTP/1.1 ' . $status . ' ' . RestUtility::getStatusCodeMessage($status);
		header($statusHeader);
		
		// set the content type
		header('Content-type: ' . $contentType . '; charset=utf-8');
		
		if ($status != 200)
		{
			if ($body == '')
			{
				switch($status)
				{
					case 400:
						$body = 'There were errors with your request.';
						break;
					case 401:
						$body = 'The API token was missing or incorrect';
						break;
					case 403:
						$body = 'You do not have permissions to use the API';
						break;
					case 404:
						$body = 'The requested URI ' . $_SERVER['REQUEST_URI'] . ' was not found.';
						break;
					case 500:
						$body = 'The server encountered an error processing your request.';
						break;
					case 501:
						$body = 'The requested method is not implemented.';
						break;
				}
			}

			if ($returnFormat == 'json') {
				if (substr($body, 0, 8) != '{"error"') {
					$body = '{"error": "'.$body.'"}';
				}
			}
			elseif ($returnFormat == 'csv') {
				# do nothing
			}
			else {
				$output = '<?xml version="1.0" encoding="UTF-8" ?>';
				if (substr($body, 0, 7) != "<error>")
					$output .= "<hash><error>$body</error></hash>";
				else
					$output .= "<hash>$body</hash>";
	
				$body = $output;
			}
		}

		echo $body;
		exit;
	}
	
	public static function sendFile($status = 200, $filepath, $filename, $contentType)
	{
		// set the status
		$statusHeader = 'HTTP/1.1 ' . $status . ' ' . RestUtility::getStatusCodeMessage($status);
		header($statusHeader);
		
		// set the content type
		header('Content-type: ' . $contentType . '; name="' . $filename . '"');
		
		ob_clean();
    	flush();
		readfile($filepath);
		
		exit;
	}
	
	public static function sendFileContents($status = 200, $contents, $filename, $contentType)
	{
		// set the status
		$statusHeader = 'HTTP/1.1 ' . $status . ' ' . RestUtility::getStatusCodeMessage($status);
		header($statusHeader);
		
		// set the content type
		header('Content-type: ' . $contentType . '; name="' . $filename . '"');
		
		ob_clean();
    	flush();
		print $contents;
		
		exit;
	}

	/**
	 * 	@name		getStatusCodeMessage
	 *  @desc		processes an incoming api request
	 *  @param		status - integer - the status code
	 *  @return		string - expanded status code 
	**/
	public static function getStatusCodeMessage($status)
	{
		$codes = Array(
		    100 => 'Continue',
		    101 => 'Switching Protocols',
		    200 => 'OK',
		    201 => 'Created',
		    202 => 'Accepted',
		    203 => 'Non-Authoritative Information',
		    204 => 'No Content',
		    205 => 'Reset Content',
		    206 => 'Partial Content',
		    300 => 'Multiple Choices',
		    301 => 'Moved Permanently',
		    302 => 'Found',
		    303 => 'See Other',
		    304 => 'Not Modified',
		    305 => 'Use Proxy',
		    306 => '(Unused)',
		    307 => 'Temporary Redirect',
		    400 => 'Bad Request',
		    401 => 'Unauthorized',
		    402 => 'Payment Required',
		    403 => 'Forbidden',
		    404 => 'Not Found',
		    405 => 'Method Not Allowed',
		    406 => 'Not Acceptable',
		    407 => 'Proxy Authentication Required',
		    408 => 'Request Timeout',
		    409 => 'Conflict',
		    410 => 'Gone',
		    411 => 'Length Required',
		    412 => 'Precondition Failed',
		    413 => 'Request Entity Too Large',
		    414 => 'Request-URI Too Long',
		    415 => 'Unsupported Media Type',
		    416 => 'Requested Range Not Satisfiable',
		    417 => 'Expectation Failed',
		    500 => 'Internal Server Error',
		    501 => 'Not Implemented',
		    502 => 'Bad Gateway',
		    503 => 'Service Unavailable',
		    504 => 'Gateway Timeout',
		    505 => 'HTTP Version Not Supported'
		);

		return (isset($codes[$status])) ? $codes[$status] : '';
	}
}

class RestRequest
{
	private $requestVars;
	private $data;
	private $httpAccept;
	private $method;
	private $queryString;
	
	public function __construct()
	{
		$this->requestVars		= array();
		$this->data				= '';
		$this->httpAccept		= 'text/xml';
		$this->method			= 'get';
		$this->queryString		= array();
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($value)
	{
		$this->data = $value;
	}
	
	public function getHttpAccept()
	{
		return $this->httpAccept;
	}
	
	public function setHttpAccept($value)
	{
		return $this->httpAccept = $value;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function setMethod($value)
	{
		$this->method = $value;
	}

	public function getQueryString()
	{
		return $this->queryString;
	}

	public function setQueryString($value)
	{
		$this->queryString = $value;
	}

	public function getRequestVars()
	{
		return $this->requestVars;
	}

	public function setRequestVars($value)
	{
		$this->requestVars = $value;
	}
}
