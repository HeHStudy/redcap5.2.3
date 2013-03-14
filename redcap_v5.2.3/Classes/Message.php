<?php

class Message
{

    /*
    * PUBLIC PROPERTIES
    */


    // @var to string
    // @access public
    private $to;
		
	// @var toName string
    // @access public
    private $toName;
    
    // @var from string
    // @access public
    private $from;
	
	// @var fromName string
    // @access public
    private $fromName;
    
    // @var from string
    // @access public
    private $bcc;
    
    // @var subject string
    // @access public
    private $subject;

    // @var body string
    // @access public
    private $body;
    
    /*
    * PUBLIC FUNCITONS
    */
    
    function getTo()            { return $this->to; }
	function getBcc()           { return $this->bcc; }
    function getFrom() 
	{ 
		if (!strpos($this->from,'@')) $this->from = $this->from;
		return $this->from; 
	}
    function getSubject()       { return $this->subject; }
    function getBody()          { return $this->body; }

    function setTo($val)        { $this->to = $val; }
	function setToName($val) { $this->toName = $val; }
    function setBcc($val)       { $this->bcc = $val; } 	
    function setFrom($val)      { $this->from = $val; }
	function setFromName($val) { $this->fromName = $val; }
    function setSubject($val)   { $this->subject = $val; }
		
	/**
	 * Sets the content of this HTML email.
	 * @param string $val the HTML that makes up the email.
	 * @param boolean $onlyBody true if the $html parameter only contains the message body. If so,
	 * then html/body tags will be automatically added, and the message will be prepended with the
	 * standard REDCap notice.
	 */
    function setBody($val, $onlyBody=false) {
		if ($onlyBody) {
			global $lang;
			$this->body =
				"<html>\n" .
				"<body style=\"font-family:Arial;font-size:10pt;\">\n" .
				$lang['global_21'] . "<br /><br />\n" .
				$val .
				"</body>\n" .
				"</html>\n";
		}
		else $this->body = $val;	
	}
			
    // @return void
    // @access public
    function send() 
	{
		// Set email headers
	    $headers  = "MIME-Version: 1.0" . "\n";
		$headers .= "From: " . $this->getFrom() . "\n";
		if ($this->getBcc() != "") $headers .= "Bcc:"  . $this->getBcc() . "\n";
		$headers .= "Reply-To: " . $this->getFrom() . "\n";
		$headers .= "Return-Path: " . $this->getFrom() . "\n";
		$headers .= "Content-type: text/html; charset=utf-8\n";
		$headers .= "Content-Transfer-Encoding: base64\n";
		// Use base-64 encode and other methods for content and subject to deal with encoding issues
		$content = rtrim(chunk_split(base64_encode($this->getBody())));
		$subject = $this->getSubject();
		//if (function_exists('mb_detect_encoding') && mb_detect_encoding($subject) == "UTF-8") {
		$subject = '=?UTF-8?B?'.substr(base64_encode($subject), 0, 240).'?=';
		//}
		// Return boolean if sent or not
        return mail($this->getTo(), $subject, $content, $headers, "-f " . $this->getFrom());
    }
    
    // @return string
    // @access public
    function cr()
    {
        // define newline/carrige return character string
        $ret_val = "\r\n";
        
        return $ret_val;
    }
  
	/**
	 * Returns HTML suitable for displaying to the user if an email fails to send.
	 */
	function getSendError() 
	{
		global $lang;
		return  "<div style='font-family:Arial;font-size:12px;background-color:#F5F5F5;border:1px solid #C0C0C0;padding:10px;'>
			<div style='font-weight:bold;border-bottom:1px solid #aaaaaa;color:#800000;'>
			<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
			{$lang['control_center_243']}
			</div><br>
			{$lang['global_37']} <span style='color:#666;'>{$this->fromName} &#60;{$this->from}&#62;</span><br>
			{$lang['global_38']} <span style='color:#666;'>{$this->toName} &#60;{$this->to}&#62;</span><br>
			{$lang['control_center_28']} <span style='color:#666;'>{$this->subject}</span><br><br>
			{$this->body}<br>
			</div><br>";
	}
}
