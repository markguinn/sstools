<?php
/**
 * These are a couple classes that link the dataobject classes to Silverstripe
 * They should generally be subclassed for arrangements, etc.
 * @author Mark GUinn
 */
 
 
class LivePubWrapper extends ViewableData 
{
	protected $_srcdata;
	
	/**
	 * if using static publishing, this is the variable name	 
	 */
	protected $_varName;
	
	/**
	 * should the __get method make properties "live" in static published documents?
	 * var name must be set in order for this to work and the variable it refers
	 * to should somehow be set up in getStaticInit() - e.g from the session or a
	 * database call or something.
	 * can also be an array of which properties are "live"
	 */
	protected $_liveStaticVars = true;
	
	/**
	 * normally live statics are escaped using htmlentities automatically, but any
	 * in this list will be outputted as-is
	 */	
	protected $_liveStaticUnescaped = array();
	
	/**
	 * sets up the object
	 * @param object|array $srcdata
	 */
	function __construct($srcdata) {
		$this->_srcdata = $srcdata;
	}
	
	
	/**
	 * returns the source object
	 */
	function getRawObject(){
		return $this->_srcdata;
	}
	
	
	/**
	 * If we're not currently publishing, this just takes any
	 * array or object and wraps it in a ViewableData object so
	 * it can be used from a template. Even nested arrays and
	 * such will be handled appropriately.
	 *
	 * If we're publishing, it adds live php references to the
	 * variables. You would want to either add initialization
	 * code manually via LivePubHelper::exec_php or $init_code
	 * or define the getStaticInit() function on a subclass.
	 * You must also use setVar to specify the name of the variable
	 * you set up. (could also be something like '_SESSION' to
	 * access the session.
	 */
	protected $_get_cache = array();
	function __get($field) {
		$fn = "get$field";		
		if (
			$this->_liveStaticVars 
			&& isset($this->_varName) 
			&& LivePubHelper::is_publishing() 
			&& !method_exists($this, $field) 
			&& !method_exists($this, $fn)
			&& (!is_array($this->_liveStaticVars) || in_array($field, $this->_liveStaticVars))
		) {
			try {
				$newvar = $this->_varName . '_' . $field;
				if (is_object($this->_srcdata)) {
					if (is_callable(array($this->_srcdata, $fn))){
						$val = $this->_srcdata->$fn();
						$php = '$' . $this->_varName . '->' . $fn . '()';
					} else {
						$val = $this->_srcdata->$field;
						$php = '$' . $this->_varName . '->' . $field;
					} 
				} else {
					$val = $this->_srcdata[$field];
					$php = '$' . $this->_varName . '["' . $field . '"]';
				}
				
				$val = $this->wrapObject($val);
				if (is_object($val) && $val instanceof LivePubWrapper) {
					$val->setVar($newvar);
					return '<?php $' . $newvar . ' = ' . $php . ' ?>';
				} else {										
					return in_array($field, $this->_liveStaticUnescaped)
						? '<?php echo ' . $php . '; ?>'
						: '<?php echo htmlentities(' . $php . '); ?>';
				}
			} catch (Exception $e) {
				return parent::__get($field);
			}
		} else {
			if (!isset($this->_get_cache[$field])) {
				try {
					// allow some speed shortcuts using _
					if (strpos($field, '_') !== false) {
						$pieces = explode('_', $field);
						$cur = $this->_srcdata;
						$key = "";
						foreach ($pieces as $field2) {
							$key .= "_$field2";
							if (isset($this->_get_cache[$key])) {
								$cur = $this->_get_cache[$key];
							} elseif (is_object($cur)) {
								$fn = "get$field2";
								if (is_callable(array($cur, $fn))) {
									$cur = $cur->$fn();
								} else {
									$cur = $cur->$field2;
								}
							} elseif (is_array($cur)) {
								$cur = $cur[$field2];
							} else {
								$val = $cur;
								break;
							}
						}
						
						$val = $cur;
					} else {
						// get the value
						if (is_object($this->_srcdata)) {
							$val = is_callable(array($this->_srcdata, $fn)) ? $this->_srcdata->$fn() : $this->_srcdata->$field;
						} else {
							$val = $this->_srcdata[$field];
						}
					}
					
					$this->_get_cache[$field] = $this->wrapObject($val);				
				} catch (Exception $e) {
					$this->_get_cache[$field] = parent::__get($field);
				}
			}
	
			return $this->_get_cache[$field];
		}
	}
	
	
	/**
	 * if an object isn't already wrapped in Silverstripe's stuff,
	 * wrap it appropriately either in a viewableWrapper or dataobject set
	 */
	protected function wrapObject($obj) {
		if (is_object($obj)) {
			if ($obj instanceof ViewableData) {
				return $obj;
			} else {
				return new LivePubWrapper($obj);
			}
		} elseif (is_array($obj)) {
			return $this->wrapArray($obj);
		} else {
			return $obj;
		}
	}
	
	
	/**
	 * transform an array into a DataobjectSet
	 */
	protected function wrapArray($arr) {
		$set = new DataObjectSet();

		foreach ($arr as $obj) {
			$set->push($this->wrapObject($obj));
		}

		return $set;		
	}
		

	/**
	 * This is called by LivePubHelper to retrieve initialization
	 * code that gets added to the top of the cached page.
	 */	
	function getStaticInit() {
		return false;
	}

	function setVar($name) {
		$this->_varName = $name;
	}
	
	function getVar() {
		return $this->_varName;
	}

}

