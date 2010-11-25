<?php
/**
 * Text input field.
 * @package sstools
 * @subpackage forms
 */
class SliderField extends TextField {
	protected $slider_cfg = array();


	function __construct($name, $title = null, $cfg = array(), $value = "", $maxLength = null, $form = null){
		$this->slider_cfg = $cfg;
		parent::__construct($name, $title, $value, $maxLength, $form);
	}
	

	function Field() {
		$attributes = array(
			'type' => 'slider',
			'class' => 'slider' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null,
			'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null 
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';

/*		
	<link rel="stylesheet" href="./stylesheets/jslider.css" type="text/css">
	<link rel="stylesheet" href="./stylesheets/jslider.blue.css" type="text/css">
	<!--[if IE 6]>
    <link rel="stylesheet" href="./stylesheets/jslider.ie6.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./stylesheets/jslider.blue.ie6.css" type="text/css" media="screen">
	<![endif]-->

	<script type="text/javascript" src="../../../jsparty/jquery/jquery.js"></script>
	<script type="text/javascript" src="./javascripts/jquery.dependClass.js"></script>
	<script type="text/javascript" src="./javascripts/jquery.slider.js"></script>
*/
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery_improvements.js');
		Requirements::javascript(SSTOOLS_BASE.'/javascript/jquery.dependClass.js');
		Requirements::javascript(SSTOOLS_BASE.'/javascript/jquery.slider.js');
		Requirements::css(SSTOOLS_BASE.'/css/jslider.css');
		Requirements::css(SSTOOLS_BASE.'/css/jslider.blue.css');
		//Requirements::css('/stylesheets/jslider.ie6.css');
		//Requirements::css('/stylesheets/jslider.blue.ie6.css');
		
		$cfg = json_encode($this->slider_cfg);
		Requirements::customScript("
			jQuery('#{$attributes['id']}').slider($cfg);
		");		
		
		return $this->createTag('input', $attributes);
	}
	
}
