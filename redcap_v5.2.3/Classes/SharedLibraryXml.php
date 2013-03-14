<?php


 
class SharedLibraryXml  
{
	private $parser = null;
	private $xmlDocument = null;
	private $fieldHandler = null;
	private $currFieldArray = null;
	private $currFieldElement = null;
	private $currActionElement = null;
	private $currActionArray = null;
	private $currImageElement = null;
	private $instrumentType = null;
	private $skip = false;

	//constructor
	// - can optionally pass in xml doc and handler here
	function SharedLibraryXml($xml = null, $fieldHandler = null) {
		$this->parser = xml_parser_create('UTF-8');
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		$this->xmlDocument = $xml;
		$this->fieldHandler = $fieldHandler;
	}

	//adds the xml document to be used in parsing
	function setXml($data) {
		$this->instrumentType = null;//reset for new xml doc
		$this->xmlDocument = $data;
	}

	//sets the handler for the field elements in the library xml
	function setFieldHandler($handler) {
		if(!is_callable($handler)) {
		    throw new Exception('SharedLibraryXml: supplied handler ('.$handler.') does not exist');
		}
		$this->fieldHandler = $handler;
	}

	//convenience method to get the type of instrument described in the xml (crf or survey)
	function getInstrumentType() {
		if(!$this->xmlDocument) {
		    throw new Exception('SharedLibraryXml: xml document must be set before calling getInstrumentType');
		}
		//use cached value if available
		if(!$this->instrumentType && $this->xmlDocument) {
		    $simpleXml = new SimpleXMLElement($this->xmlDocument);
		    $this->instrumentType = $simpleXml->Metadata[0]['type'];
		}
		return $this->instrumentType;
	}

	//parse the xml.  Calling this method results in a call to the field handler for each
	//Field element in the xml
	function parse() {
		if(!$this->xmlDocument || trim($this->xmlDocument) == '') {
		    throw new Exception('ERROR: Could not find XML document. This likely occurred from an error communicating with the REDCap Shared Library server at '.SHARED_LIB_PATH);
		}
		if(!$this->fieldHandler) {
		    throw new Exception('SharedLibraryXml: field handler must be set before parsing');
		}
		$return_code = xml_parse($this->parser, $this->xmlDocument);
		return $return_code;
	}

	//internal parsing method to handle opening tags
	private function tag_open($parser, $tag, $attributes) {
		if($tag == 'StructuredValue') {
			//ignore structure value and its contents (not used in import/export)
		    $this->skip = true;
		}else if(($tag == 'Trigger' || $tag == 'Action')) {
			$this->currActionElement = $tag;
		}else if($tag == 'ImageAttachment') {
			$this->currImageElement = $tag;
			$this->currFieldArray['ImageAttachment'] == array();
		}else if(is_array($this->currFieldArray) && $tag != 'RawValue') {
			//for raw value and trigger/action pairs, use the parent tag as the identifier
			$this->currFieldElement = $tag;
		}else if($tag == 'Field') {
			$this->currFieldArray = array();
		}else if($tag == 'Event') {
			//the Event element is purely a mechanism to allow multiple actions in the xml
			//for the data returned to the caller, the triggers and actions will exist under 
			//ActionExists array element
			$this->currFieldArray['ActionExists'] = array();
		    $this->currActionArray = array();
		}
	}

	//internal parsing method to handle element text.  All of the element text
	//is stuffed into an array that is sent to the handler when the Field element
	//is closed
	private function cdata($parser, $text) {
		if(($this->currActionElement == 'Trigger' || $this->currActionElement == 'Action')) {
			//when entity references exist, cdata can be called more than once
			//although there shouldn't be any in the Trigger or Action elements
			if(!isset($this->currActionArray[$this->currActionElement])) {
				$this->currActionArray[$this->currActionElement] = $text;
			}else {
			    $this->currActionArray[$this->currActionElement] .= $text;
			}
		}else if($this->currImageElement == 'ImageAttachment') {
			if(!isset($this->currFieldArray['ImageAttachment'][$this->currFieldElement])) {
				$this->currFieldArray['ImageAttachment'][$this->currFieldElement] = $text;
			}else {
			    $this->currFieldArray['ImageAttachment'][$this->currFieldElement] .= $text;
			}
		}else if(is_array($this->currFieldArray) && trim($text) != '' && !$this->skip) {
			//when entity references exist, cdata can be called more than once
			//it is very possible that a few will exist in the xml doc
			if(!isset($this->currFieldArray[$this->currFieldElement])) {
				$this->currFieldArray[$this->currFieldElement] = $text;
			}else {
			    $this->currFieldArray[$this->currFieldElement] .= $text;
			}
		}
	}

	//internal parsing method to handle end tags.  When a Field element is closed, the
	//current field data (in currFieldArray) is sent to the field handler for processing.
	//Then, the current field data array is reset for the next Field to be processed.
	private function tag_close($parser, $tag) {
		if($tag == 'Field') {
			call_user_func($this->fieldHandler,$this->currFieldArray);
			$this->currFieldArray = null;
		}else if($tag == 'StructuredValue') {
		    $this->skip = false;
		}else if($tag == 'Trigger' || $tag == 'Action') {
			//echo '<br>ending '.$tag;
		    $this->currActionElement = '';
		}else if($tag == 'Event') {
			//echo 'ending ActionExists';
		    $this->currFieldArray['ActionExists'][] = $this->currActionArray;
		    $this->currActionArray = null;
		}else if($tag == 'ImageAttachment') {
			$this->currImageElement = '';
		}
	}

	function createXML($type, $fields) {
		$dom = new DOMDocument("1.0","UTF-8");
		$root = $dom->createElement("SharedInstrument");
		$dom->appendChild($root);
	
		$metadata = $dom->createElement("Metadata");
		$typeAttribute = $dom->createAttribute("type");
		$typeAttribute->appendChild($dom->createTextNode($type));
		$metadata->appendChild($typeAttribute);
		$root->appendChild($metadata);
		
		foreach($fields as $field) {
			$fieldNode = $dom->createElement("Field");
			$metadata->appendChild($fieldNode);
		    foreach($field as $key=>$val) {
		    	if($key == 'ElementEnum') {
		    		//echo '<br>enum detected';
		    	    $enumNode = $dom->createElement("ElementEnum");
		    	    $fieldNode->appendChild($enumNode);
		    	    $rawNode = $dom->createElement("RawValue");
		    	    $enumNode->appendChild($rawNode);
		    	    $rawNode->appendChild($dom->createTextNode($val));
		    	    if(trim($val) != '' && $fields['ElementType'] != 'calc') {
				    	$structuredNode = $dom->createElement("StructuredValue");
				    	$enumNode->appendChild($structuredNode);
						$valuePairs = explode('\n',$val);
						foreach($valuePairs as $valuePairStr) {
							$valuePair = explode(',',$valuePairStr);
							$valueNode = $dom->createElement("Value");
							$structuredNode->appendChild($valueNode);
							$valueAttr = $dom->createAttribute("value");
							$valueNode->appendChild($valueAttr);
							$valuePair[0] = trim($valuePair[0]);
							$valuePair[1] = trim($valuePair[1]);
							if ($valuePair[1] == "") $valuePair[1] = $valuePair[0]; // In case using old "only number" enumerating (e.g. 1 | 2 | 3...)
							$valueAttr->appendChild($dom->createTextNode($valuePair[0]));
							$valueNode->appendChild($dom->createTextNode($valuePair[1]));
						}
		    	    }
		    	}else if($key == 'ActionExists') {
		    	    $actionExistsNode = $dom->createElement("ActionExists");
		    	    $fieldNode->appendChild($actionExistsNode);
		    	    foreach($val as $actionVal) {
						$eventNode = $dom->createElement("Event");
						$actionExistsNode->appendChild($eventNode);
						$triggerNode = $dom->createElement("Trigger");
						$eventNode->appendChild($triggerNode);
						$triggerNode->appendChild($dom->createTextNode($actionVal['Trigger']));
						$actionNode = $dom->createElement("Action");
						$eventNode->appendChild($actionNode);
						$actionNode->appendChild($dom->createTextNode($actionVal['Action']));
		    	    }
		    	}else if($key == 'ImageAttachment') {
		    		$imageNode = $dom->createElement("ImageAttachment");
		    		$fieldNode->appendChild($imageNode);
		    		foreach($val as $imageKey=>$imageVal) {
		    			$imageChildNode = $dom->createElement($imageKey);
		    			$imageNode->appendChild($imageChildNode);
		    			$imageChildNode->appendChild($dom->createTextNode($imageVal));
		    		}
		    	}else {
		    		//echo '<br>adding '.$key;
					if($key != null && trim($key) != '') {
						$newNode = $dom->createElement($key);
						if($val != null && trim($val) != '') {
							$newNode->appendChild($dom->createTextNode($val));
						}
						$fieldNode->appendChild($newNode);
					}
		    	}
		    }
		}

		libxml_use_internal_errors(true);		
		
		// Get the XSD file from the Vanderbilt server and write its contents to a temp file first
		$xsd = http_get(SHARED_LIB_SCHEMA);
		$tempFile = APP_PATH_TEMP . date('YmdHis') . '_p'.PROJECT_ID.'_SharedLibrary.xsd';
		$fp = fopen($tempFile, 'w');
		if ($fp !== false && fwrite($fp, $xsd) !== false) fclose($fp);
		
		// Validate the XML schema against the XSD file
		if ($dom->schemaValidate($tempFile)) 
		{
			unlink($tempFile);
		    return $dom->saveXML();
		} 
		else 
		{
			unlink($tempFile);
			error_log('an error occurred sharing a project to the shared library');
			error_log('XML Dump: '.$dom->saveXML());
		    $message = 'Invalid XML: ';
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$message .= '<br>';
				$message .= $error->message.'(code:'.$error->code.')';
				if($error->file) {
				    $message .= ' in ' . $error->file;
				}
				$message .= ' on line ' . $error->line;
			}
			error_log('Error Message: '.$message);
			throw new Exception($message);
		}
	}
	
	function uploadToLibrary() {
	    
	}
}
