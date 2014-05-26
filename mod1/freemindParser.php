<?php


class freemindParser {


	var $records; 				// array which contains the final results of the parsing process
	var $currentDepth = 0;		// indicates the current depth inside the xml - tree
	var $xmlParser;				// reference to the xml Parser
	var $levelParents;			// stores the current parents of the level

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
		$theCharset = $ereg_result[1] ? $ereg_result[1] : ($TYPO3_CONF_VARS['BE']['forceCharset'] ? $TYPO3_CONF_VARS['BE']['forceCharset'] : 'iso-8859-1');
		xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, $theCharset);


		xml_parse($this->xmlParser, $fileContent);
		xml_parser_free($this->xmlParser);


		return $this->records;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$parser: ...
	 * @param	[type]		$name: ...
	 * @param	[type]		$attribs: ...
	 * @return	[type]		...
	 */
	function tag_open($parser, $name, $attribs) {
		if ($name == 'NODE') {
			$this->currentDepth++;
			$this->records[] = array(
				'title' => $attribs['TEXT'],
				'parentRecord_mindmap' => isset($this->levelParents[$this->currentDepth - 1]) ? $this->levelParents[$this->currentDepth - 1] : 0
			);
			$crID = count($this->records) - 1;
			$this->levelParents[$this->currentDepth] = $crID;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$parser: ...
	 * @param	[type]		$name: ...
	 * @return	[type]		...
	 */
	function tag_close($parser, $name) {
		if ($name == 'NODE') {
			$this->currentDepth--;
		}
	}

}


?>
