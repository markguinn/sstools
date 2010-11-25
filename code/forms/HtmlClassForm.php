<?php
/**
 * this is just a simple wrapper for the sapphire form class that allows
 * you to add classes to the form
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @subpackage form
 * @created 2.12.10
 */

class HtmlClassForm extends Form {	
	protected $htmlClasses = array();
	
	
	/**
	 * same as parent constructor but you can pass an array of html class names as after the validor
	 * can pass classes as an array or a string - 'class1 class2 class3'
	 */
	function __construct($controller, $name, FieldSet $fields, FieldSet $actions, $validator = null, $classes = null) {
		parent::__construct($controller, $name, $fields, $actions, $validator);
		if ($classes) $this->setClasses($classes);
	}

	
	/**
	 * same as parent, but adds a class attribute
	 */
	function FormAttributes() {
		$classes = implode(' ', $this->htmlClasses);
		return parent::FormAttributes() . " class=\"$classes\"";
	}
	
	
	/**
	 * adds one class to the list
	 * @param string $class
	 */
	function addClass($class) {
		$this->htmlClasses[] = $class;
		return $this;
	}
	
	
	/**
	 * @return array
	 */
	function getClasses() {
		return $this->htmlClasses;
	}
	
	
	/**
	 * overwrites existing classes
	 * @param array $classes
	 */
	function setClasses($classes) {
		$this->htmlClasses = is_array($classes) ? $classes : array($classes);
		return $this;
	}


	/**
	 * clears all current classes
	 */
	function clearClasses() {
		return $this->setClasses(array());
	}
	
	
}