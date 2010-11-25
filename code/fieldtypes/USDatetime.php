<?php
/**
 * quickfix while waiting for internationalization
 *
 * @author Mark Guinn <mark@adaircreative.com> 
 * @package sstools
 */
class USDatetime extends SS_Datetime {
	
	function setValue($value) {
		if(is_numeric($value)) {
			$this->value = date('Y-m-d H:i:s', $value);
		} elseif(is_string($value)) {
			$this->value = date('Y-m-d H:i:s', strtotime($value));
		}
	}

	function Nice() {
		return date('m/d/Y g:ia', strtotime($this->value));
	}
	function Nice24() {
		return date('m/d/Y H:i', strtotime($this->value));
	}
	function Date() {
		return date('m/d/Y', strtotime($this->value));
	}

}

