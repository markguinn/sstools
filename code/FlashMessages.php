<?php
/**
 * Utility class for displaying flash messages, both in an ajax and page context
 *
 * @author Mark Guinn <markguinn@gmail.com>
 * @package sstools
 */

define('FLASH_VAR', 'flash_msgs');

class FlashMessages {

	/**
	 * adds a new flash message to the 
	 */
	static function add($msg, $type='info') {
		$_SESSION[FLASH_VAR][] = array(
			'Message' => $msg,
			'Type' => $type,
			'JS_Message' => addslashes($msg),
			'JS_Type' => addslashes($type),
		);
	}
	
	
	/**
	 * clears the flash messages
	 */
	static function clear() {
		unset($_SESSION[FLASH_VAR]);
	}
	
	
	/**
	 * returns the flash messages as a dataobjectset
	 */
	static function get() {
		if (isset($_REQUEST['test_flash'])) {
			self::add('This is a good message','good');
			self::add('This is a bad message','bad');
			self::add('This is a neutral message','info');
			self::add('This is a good message','good');			
		}
		
		if (isset($_SESSION[FLASH_VAR])) {
			$msgs = new DataObjectSet();
			
			foreach ($_SESSION[FLASH_VAR] as $msg) {
				$msgs->push(new ArrayData($msg));
			}

			self::clear();
			return $msgs;
		}

		return null;
	}
	
	
	/**
	 * returns the flash messages as an array of arrays
	 */
	static function get_array() {
		if (isset($_REQUEST['test_flash'])) {
			self::add('This is a good message','good');
			self::add('This is a bad message','bad');
			self::add('This is a neutral message','info');
			self::add('This is a good message','good');			
		}
		
		if (isset($_SESSION[FLASH_VAR])) {
			$msgs = array_merge(array(), $_SESSION[FLASH_VAR]);
			self::clear();
			return $msgs;
		}

		return null;
	}
	
	
	/**
	 * if this is called from the init() method of the controller,
	 * will set the appropriate variables for templates to accesss
	 * alternatively the controller could have a FlashMessages() method
	 * that returns FlashMessages::get()
	 */
	static function init($controller) {
		$msgs = self::get();
		if ($msgs) $controller->FlashMessages = $msgs;
	}
	
}