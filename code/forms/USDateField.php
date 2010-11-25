<?php
/**
 * In leiu of localization being added, this can just be swapped out in US (MDY) environments
 * 
 * @package sstools
 */
class USDateField extends DateField {
	
	function setValue($val) {
		if(is_string($val) && preg_match('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/', $val)) {
			$this->value = preg_replace('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/','\\2/\\3/\\1', $val);
		} else {
			$this->value = $val;
		}
	}
	
	function dataValue() {
		if(is_array($this->value)) {
			if(isset($this->value['Year']) && isset($this->value['Month']) && isset($this->value['Day'])) {
				return $this->value['Year'] . '-' . $this->value['Month'] . '-' . $this->value['Day'];
			} else {
				user_error("Bad DateField value " . var_export($this->value,true), E_USER_WARNING);
			}
		} elseif(!empty($this->value)) {
			return date('Y-m-d', strtotime($this->value));
		} else {
			return null;
		}
	}
	
}

