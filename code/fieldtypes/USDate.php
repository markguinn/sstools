<?php
/**
 * quickfix while waiting for internationalization
 *
 * @author Mark Guinn <mark@adaircreative.com> 
 * @package sstools
 */
class USDate extends Date {
	
	function setValue($value) {
		// @todo This needs tidy up (what if you only specify a month and a year, for example?)
		if(is_array($value)) {
			if(!empty($value['Day']) && !empty($value['Month']) && !empty($value['Year'])) {
				$this->value = $value['Year'] . '-' . $value['Month'] . '-' . $value['Day'];
				return;
			}
		}
		
		if(is_numeric($value)) {
			$this->value = date('Y-m-d', $value);
		} elseif(is_string($value)) {
			$this->value = date('Y-m-d', strtotime($value));
		}
	}

	/**
	 * Returns the date in the format dd/mm/yy 
	 */	 
	function Nice() {
		if($this->value) return date('m/d/Y', strtotime($this->value));
	}
	
}
