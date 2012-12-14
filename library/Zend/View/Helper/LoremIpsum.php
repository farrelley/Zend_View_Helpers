<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Cycle.php 20096 2010-01-06 02:05:09Z bkarwin $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

require_once 'Zend/Http/Client.php';
require_once 'Zend/Json.php';

/**
 * Helper for adding filler text (Lorem Ipsum)
 *
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_LoremIpsum extends Zend_View_Helper_Abstract
{
	/**
	 * Lipson JSON URI
	 */
	CONST URI = "http://www.lipsum.com/feed/json";
	
	/**
	 * Default Count
	 */
	CONST COUNT = 5;
	
	/**
	 * Default staring with Lorem Ipsum....
	 */
	CONST START = "yes"; 
	
	/**
	 * Default List Type
	 */
	CONST LIST_TYPE = "ul";
	
	/**
	 * Zend_Http_Client
	 */
	protected $_localHttpClient;
	
	/**
	 * Setup http client
	 * @return void
	 */
	public function __construct() 
	{
		$this->_localHttpClient = new Zend_Http_Client();
		$this->_localHttpClient->setUri(self::URI);
	}
	
	/**
	 * View helper.  Route to correct helper via options.
	 * default to paragraph
	 * @return string
	 */
	public function loremIpsum(array $options) 
	{
		
		/**
		 * Adicionando validacao para valores evitar index not found
		 */
		
		$options['type'] = isset($options['type'])?$options['type']:null;
		$options['count'] = isset($options['count'])?$options['count']:null;
		$options['start'] = isset($options['start'])?$options['start']:null;
		
		switch($options['type']) {
			case "words":
				return $this->_words($options);
				break;
			case "bytes":
				return $this->_bytes($options);
				break;
			case "lists":
				return $this->_lists($options);
				break;
			case "paragraph":		
			default:
				return $this->_paragraph($options);
		}
	}
	
	/**
	 * Validate count to make sure it's set. 
	 * @return string
	 */
	protected function _validateCount($count)
	{
		if (is_numeric($count) && isset($count)) {
			return $count;
		} else {
			return self::COUNT;
		}
	}
	
	/**
	 * Validate Start to make sure it's set
	 * @return string
	 */
	protected function _validateStart($start)
	{
		if (isset($start) && ("yes" === $start || "no" === $start)) {
			return $start;
		} else {
			return self::START;
		}
	}
	
	/**
	 * Validate list type to make sure it's set
	 * @return string
	 */
	protected function _validateListType($type) 
	{
		if (isset($type) && in_array($type, array('ul', 'ol'))) {
			return $type;
		} else {
			return self::LIST_TYPE;
		}
	}
	
	/**
	 * Generate Paragraphs
	 * @param array $options
	 * @return string 
	 */
	protected function _paragraph(array $options)
	{
		$this->_localHttpClient->resetParameters(true)->setParameterPost(array(
			'what' => 'paras',
			'amount' => $this->_validateCount($options['count']),
			'start' => $this->_validateStart($options['start']),
		));
		$response = $this->_localHttpClient->request(Zend_Http_Client::POST);
		$jsonData = str_replace("\n", "</p><p>", $response->getBody());
		$data = Zend_Json::decode($jsonData);
		return "<p>" . $data['feed']['lipsum'] . "</p>";
	}
	
	/**
	 * Generate words
	 * @param array $options
	 * @return string
	 */
	protected function _words(array $options)
	{
		$this->_localHttpClient->resetParameters(true)->setParameterPost(array(
			'what' => 'words',
			'amount' => $this->_validateCount($options['count']),
			'start' => $this->_validateStart($options['start']),
		));
		$response = $this->_localHttpClient->request(Zend_Http_Client::POST);
		$data = str_replace("\n", "", $response->getBody());
		$data = Zend_Json::decode($data);
		return $data['feed']['lipsum'];
	}
	
	/**
	 * Generate words with the specified bytes
	 * @param array $options
	 */
	protected function _bytes(array $options)
	{
		$this->_localHttpClient->resetParameters(true)->setParameterPost(array(
			'what' => 'bytes',
			'amount' => $this->_validateCount($options['count']),
			'start' => $this->_validateStart($options['start']),
		));
		$response = $this->_localHttpClient->request(Zend_Http_Client::POST);
		$data = str_replace("\n", "", $response->getBody());
		$data = Zend_Json::decode($data);
		return $data['feed']['lipsum'];
	}
	
	/**
	 * Gernerate UL or OL lists
	 * @param array $options
	 * @return string
	 */
	protected function _lists(array $options)
	{
		$this->_localHttpClient->resetParameters(true)->setParameterPost(array(
			'what' => 'lists',
			'amount' => $this->_validateCount($options['count']),
			'start' => $this->_validateStart($options['start']),
		));
		
		$listType = $this->_validateListType($options['list']);
		
		$response = $this->_localHttpClient->request(Zend_Http_Client::POST);
		$data = str_replace("\n", "_LIST_", $response->getBody());
		$data = Zend_Json::decode($data);
		
		$ulLists = array();
		
		$lists = explode("_LIST_", $data['feed']['lipsum']);
		foreach ($lists AS $list) {
			$items = explode(".", $list);
			$listItems = array();
			foreach ($items as $item) {
				if(strlen(trim($item)) > 0) {
					$listItems[] = "<li>" . trim($item) . "</li>";
				}
			}
			$lis = implode("\n", $listItems);
			$ulLists[] = "<" . $listType . ">" . $lis . "</" . $listType . ">";
		}
		
		return implode("\n", $ulLists);
	}	
}
