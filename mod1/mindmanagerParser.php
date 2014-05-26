<?php



class mindmanagerParser {


	var $currentDepth = 0;					// stores the depth of the parsing process in the xml tree
	var $xmlParser;							// stores a reference to the xml Parser object
	var $levelParents;						// stroes the path to the current node
	var $records;


	function parseFile($fileContent) {
		global $TYPO3_CONF_VARS;
		$this->xmlParser = xml_parser_create();
		xml_set_object($this->xmlParser, $this);
		xml_set_element_handler($this->xmlParser, 'tag_open', 'tag_close');




		// setting the php version
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 1);
		xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 1);
		// setting the charset -  taken from the xml2array function in t3lib_div
		$ereg_result = array();
		ereg('^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"',substr($fileContent,0,200),$ereg_result);
		$theCharset = 'utf-8';
		xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'iso-8859-1');


		xml_parse($this->xmlParser, $fileContent);
		xml_parser_free($this->xmlParser);

		// return the parser files
		return $this->records;
	}

	/**
	 * function for an opening tag - see xml_parser documentation for this 
	 *
	 * @param	object		$parser: required reference to the parser object
	 * @param	string		$name: name of the element thats parsed by the parser object
	 * @param	array		$attribs: array with all attributes of the currently parsed tag
	 * @return	void		
	 */
	function tag_open($parser, $name, $attribs) {
		switch ($name) {
			case 'AP:TOPIC':
				$this->currentDepth++;
				$this->records[] = array(
					'title' => '',
					'parentRecord_mindmap' => $this->levelParents[$this->currentDepth - 1]
				);
				
				$this->levelParents[$this->currentDepth] = (count($this->records) - 1);
				break;
			// adding the text, read from the current level
			case 'AP:TEXT':
				$this->records[$this->levelParents[$this->currentDepth]]['title'] = $attribs['PLAINTEXT'];
				break;
		}
	}

	/**
	 * handler for the event, when the parser hits the closing tag
	 *
	 * @param	object		$parser: required parser ferfernce
	 * @param	parser		$name: name of the closing tag
	 * @return	void		
	 */
	function tag_close($parser, $name) {
		switch($name) {
			case 'AP:TOPIC':
				$this->currentDepth--;
				break;
		}
	}


}


?>
