<?php
/**
 * Text input field with a jquery UI datepicker (requires jquery 1.3.2)
 * NOTE: as of now, it just assumes that jquery is included, since there's
 * not a standard place for 1.3.2 to live. Ideally, I'd say it would be
 * good to have a few config options to turn on/off the js/css - if you
 * wanted to use your own build of jquery or UI for example.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @subpackage forms
 */
class JQueryDateField extends TextField {

	protected $_cfg = array(
		'showAnim' => 'fadeIn',
	);

	static $jquery_path = 'sstools/javascript/jquery.core-1.3.2.js';
	static function set_jquery_path($p) {
		self::$jquery_path = $p;
	}
	
	
	/**
	 * sets up the field
	 */
	function __construct($name, $title = null, $cfg = false, $value = "", $maxLength = null, $form = null){
		if ($cfg) $this->_cfg = $cfg;
		parent::__construct($name, $title, $value, $maxLength, $form);
	}
	
	
	/**
	 * returns htlm for templates
	 */
	function Field() {
		$attributes = array(
			'type' => 'text',
			'class' => 'text datepicker' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null,
			'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null 
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';

		Requirements::javascript(self::$jquery_path);
		Requirements::javascript('sapphire/javascript/jquery_improvements.js');
		Requirements::javascript(SSTOOLS_BASE.'/javascript/jquery-ui-1.7.2.custom.min.js');
		Requirements::css(SSTOOLS_BASE.'/css/smoothness/jquery-ui-1.7.2.custom.css');
		
		$cfg = json_encode($this->_cfg);
		Requirements::customScript("
			jQuery('#{$attributes['id']}').datepicker($cfg);
		");		
		
		return $this->createTag('input', $attributes);
	}
	
}
