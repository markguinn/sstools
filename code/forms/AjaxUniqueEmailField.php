<?php
/**
 * Basically an AjaxUniqueTextField with Email validation added.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @subpackage formfields
 */

class AjaxUniqueEmailField extends AjaxUniqueTextField {

	/**
	 * combines the validate function from EmailField w/ the parent validator
	 */
	function validate($validator){
		if (!parent::validate($validator)) {
			return false;
		}
		
		$this->value = trim($this->value);
		if($this->value && !ereg('^([a-zA-Z0-9_+\.\-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$', $this->value)){
 			$validator->validationError(
 				$this->name,
				_t('EmailField.VALIDATION', "Please enter an email address."),
				"validation"
			);
			return false;
		} else{
			return true;
		}
	}

}