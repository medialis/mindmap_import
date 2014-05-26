<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006  <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:mindmap_import/mod1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]


// importing the external parser files
require_once(t3lib_extMgm::extPath('mindmap_import').'mod1/freemindParser.php');
require_once(t3lib_extMgm::extPath('mindmap_import').'mod1/mindmanagerParser.php');

// including the class for generation of the new pagetree
//require_once(t3lib_extMgm::extPath('mindmap_import').'mod1/rootNodeTree.php');

// including the page insertion tree - class
//require_once(PATH_typo3.'db_new.php');

/**
 * Module 'Mindmap Import' for the 'mindmap_import' extension.
 *
 * @author	 Martin Baum<martin_baum@gmx.net>
 * @package	TYPO3
 * @subpackage	tx_mindmapimport
 */
class  tx_mindmapimport_module1 extends t3lib_SCbase {
				var $pageinfo;
				var $parser;				// reference to the parser object, which reads the content
				var $startID;				// uid of the element, under which the data shall be imported
				var $parentField;           // stores the pid field in the pagetree
				var $staticData;			// array which contains static data that will be inserted with every entry in the mindmap
				var $targetTable = 'pages';	// stores the table, where the data shall be imported to
				var $excludeRoot = false;	// delimites if the root entry of the import file shall be excluded from the import
				


				var $pageTree;
				/**
 * Initializes the Module
 *
 * @return	void
 */
				function init()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
					$this->doc->form='<form action="index.php" method="POST" enctype="multipart/form-data">';
					parent::init();


					//$this->pageTree = t3lib_div::makeInstance('SC_db_new');
					/*
					if (t3lib_div::_GP('clear_all_cache'))	{
						$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
					}
					*/
				}

				/**
				 * Main function of the module. Write the content to $this->content
				 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
				 *
				 * @return	[type]		...
				 */
				function main()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

					// Access check!
					// The page will show only if there is a valid page and if this page may be viewed by the user
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;



					/********************************
					 *
					 *
					 * 	Import logic
					 *
					 *
					 ********************************/
					switch(intval($this->CMD['import_target'])) {


						// import to the page tree
						case 1:
							$this->targetTable = 'pages';
							$this->parentField = 'pid';
							$this->startID = 0;
							break;

						//import to the DAM categories
						case 2:
							$this->targetTable = 'tx_dam_cat';
							$this->parentField = 'parent_id';
							$this->staticData = array (
								'pid' => $this->findMediaFolder()
							);
							break;

						// import to the tt_news categories
						case 3:
							$this->targetTable = 'tt_news_cat';
							$this->parentField = 'parent_categories';
							break;
					}


					// excluding the import of the root element
					if (isset($this->CMD['excludeRoot']) && $this->CMD['excludeRoot'] ==  'true' ) {
						$this->excludeRoot = true;
					}


					if (isset($this->CMD['startPoint'])) {
						$this->startID = intval($this->CMD['startPoint']);
					}


					
					
					
					
					/*************************************************
					*
					*
					*	Import of the file
					*
					*
					*
					**************************************************/
					if (isset($_FILES['upload_mindmap_file'])) {
						if (is_uploaded_file($_FILES['upload_mindmap_file']['tmp_name'])) {
							$filecontent = t3lib_div::getUrl(t3lib_div::upload_to_tempfile($_FILES['upload_mindmap_file']['tmp_name']));
							switch ($this->CMD['file_type']) {
								// import an Mindmanager File
								case 1:
									$this->parser = t3lib_div::makeInstance('mindmanagerParser');
									break;
								// import an freemind file
								case 2:
									$this->parser = t3lib_div::makeInstance('freemindParser');
									break;
							}
							$this->importFile($filecontent);
						} else {
							// failure happend
							//t3lib_div::syslog();
						}
					}

					
					
					
					
					
					
					
					/****************************************************
					*
					*	RENDER THE OUTPUT OF THE CODE
					*
					*
					*****************************************************/

					if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

							// Draw the header.
						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;
						$this->doc->form='<form action="" method="POST" enctype="multipart/form-data" >';

							// JavaScript
						$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL)	{
									document.location = URL;
								}
							</script>
						';
						$this->doc->postCode='
							<script language="javascript" type="text/javascript">
								script_ended = 1;
								if (top.fsMod) top.fsMod.recentIds["web"] = 0;
							</script>
						';

						$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],50);

						$this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header($LANG->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
						$this->content.=$this->doc->divider(5);




						// Render content:
						$this->moduleContent();


						// ShortCut
						if ($BE_USER->mayMakeShortcut())	{
							$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
						}

						$this->content.=$this->doc->spacer(10);
					} else {
							// If no access or if ID == zero

						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;

						$this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header($LANG->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->spacer(10);
					}
				}




				/**
				 * Prints out the module HTML
				 *
				 * @return	void
				 */
				function printContent()	{

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Generates the module content
				 *
				 * @return	void
				 */
				function moduleContent(){
					$content = '';
					// if nothing is submitted before - show import form
					if (isset($this->CMD['import_target'])) {
						$content = '';
						// display error message, if no file is submitted
						if (!$_FILES['upload_mindmap_file']['tmp_name'] != '') {
							$content = $this->renderErrorNotice();
						}
						else {
							switch($this->CMD['import_target']) {
							case 1:
								$content = $this->renderImportForm();
								break;
							case 2:
								$content = $this->renderSubmitNotice();
								break;
							case 3:
								$content = $this->renderSubmitNotice();
								break;
							}
						}

					}
					else {
						$content = $this->renderImportForm();
					}
					
					
					
					


//					$this->content .= $this->doc->section('Mindmap Import',$content,0,1);
					$this->content .= $content;

				}

				/**
				 * [Describe function...]
				 *
				 * @return	[type]		...
				 */
				function selectPageRootNode() {

				}

				/**
				 * [Describe function...]
				 *
				 * @return	int		...
				 */
				function findMediaFolder() {
					$SELECT = 'uid';
					$FROM = 'pages';
					$WHERE = 'type = LIKE %media% ';
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($SELECT, $FROM, $WHERE);
					
					while($data[] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					}
					return 0;
				}

				/**
				 * [Describe function...]
				 *
				 * @return	[type]		...
				 */
				function renderImportForm() {
					return '
							here you can upload your Mindmap file: <br />
							<form action="#" enctype="multipart/form-data" method="post">
								<input type="file" name="upload_mindmap_file" size="60"/>
								<br />
								<br />
								select the <b>target</b>, in which you want to import the datastructure<br />
								<select name="CMD[import_target]">
									<option value="1">Page Tree</option>
									<option value="2">DAM Categories</option>
									<option value="3">News Categories</option>
								</select>
								<br />
								<br />

								select the <b>filetype</b> you want to import <br/>
								<select name="CMD[file_type]">
									<option value="1">Mindmanager File</option>
									<option value="2">Freemind File</option>
								</select>
								<br />
								<br />
								please insert the <b>pid</b> here under which you can import your pagetree <br />
								<input name="CMD[startPoint]" type="text" size="3" maxsize="3"/>
								<br />
								<br />

								Exclude the root element ?
								<input type="checkbox" name="CMD[excludeRoot]" value="true" /><br /><br />
								<input type="submit" value="IMPORT!" name="CMD[submit]"/><br />
							</form>
						';
				}

				/**
				 * [Describe function...]
				 *
				 * @return	[type]		...
				 */
				function renderSubmitNotice() {
					return 'file successfully imported';
				}

				/**
				 * [Describe function...]
				 *
				 * @return	[type]		...
				 */
				function renderErrorNotice() {
					return 'error while importing';
				}


				/**
 				 * function is responsible for the importing of the array
				 * given from the current parser, into the target table
				 * if the flag "not import root element" is set, the root
				 * element of the tree to import is not set
				 *
				 * @param	string		$fileContent: content of the file that should be imported
				 * @param	[type]		$parentID: ...
				 * @return	void
				 */
				function importFile($fileContent) {
					$records =  $this->parser->parseFile($fileContent);
					if (count($records)) {
						$records[0][$this->parentField] = $this->startID;
						$pointer = 0;
						foreach($records as $dataset) {
							if (!$this->excludeRoot || !$pointer==0) {
								// uid of the parent array - dataset is the pid for the next import
								// if the fist node shall be excluded - pid of second level nodes is replaced by starting point
								if ($this->excludeRoot && $dataset['parentRecord_mindmap'] == 0) {
									$dataset[$this->parentField] = $this->startID;
								}
								else {
									if ($pointer == 0) {
										$dataset[$this->parentField] =$this->startID;
									}
									else {
										$dataset[$this->parentField] = $records[$dataset['parentRecord_mindmap']]['uid'];
									}
								}

								// if static Data is set for the imort - merge it with the text to import
								if (count($this->staticData)) {
									$dataset = array_merge($this->staticData, $dataset);
								}
								unset($dataset['parentRecord_mindmap']);
								$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->targetTable, $dataset);
								$records[$pointer]['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
								$records[$pointer][$this->parentField] = $dataset[$this->parentField];
							}
							$pointer++;
						}
					}
				}
			}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mindmap_import/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mindmap_import/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mindmapimport_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>