<?php
/**
 * These are a couple classes that link the dataobject classes to Silverstripe
 * They should generally be subclassed for arrangements, etc.
 * @author Mark GUinn
 */
 
 
class ViewableWrapper extends ViewableData 
{
	/**
	 * if using static publishing, this is the variable name	 
	 */
	protected $varName;
	
	/**
	 * should the __get method make properties "live" in static published documents?
	 * var name must be set in order for this to work and the variable it refers
	 * to should somehow be set up in getStaticInit() - e.g from the session or a
	 * database call or something.
	 * can also be an array of which properties are "live"
	 */
	protected $liveVars = true;
	
	/**
	 * normally live vars are escaped using htmlentities automatically, but any
	 * in this list will be outputted as-is
	 */	
	protected $liveVarsUnescaped = array();
	
	
	/**
	 * sets up the object
	 * @param object|array $srcdata
	 */
	function __construct($src=false) {
		if (is_object($src)){
			$this->failover = $src;
		} elseif (is_array($src)) {
			$this->failover = new ArrayData($src);
		}
		
		parent::__construct();
	}
	
	
	/**
	 * returns the source object
	 */
	function getRawObject(){
		return $this->failover;
	}
	
	
	/**
	 * Insures that any array or object is wrapped properly
	 */
	function __get($field){
		$val = $this->wrapObject( parent::__get($field) );
		
		if (isset($this->varName) && is_object($val) && $val instanceof ViewableWrapper){
			$val->setVar($this->varName . '_' . $field);
		}
		
		return $val;
	}
	
	
	/**
	 * If we're publishing, returns proper php
	 */
	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		$value = parent::obj($fieldName, $arguments, $forceReturnedObject, $cache, $cacheName);
		
		// if we're publishing and this variable is qualified,
		// output php code instead
		if (
			$this->failover 
			&& $this->liveVars 
			&& isset($this->varName) 
			&& LivePubHelper::is_publishing() 
			&& (!is_array($this->liveVars) || in_array($fieldName, $this->liveVars))
		) {
			$accessor = "get{$fieldName}";
			$php = '';

			// find out how we got the data
			if ($this->failover instanceof ArrayData){
				$php = '$' . $this->varName . '["' . $fieldName . '"]';
			} elseif (is_callable(array($this->failover, $fieldName))){
				// !@TODO respect arguments
				$php = '$' . $this->varName . '->' . $fieldName . '()';
			} elseif (is_callable(array($this->failover, $accessor))){
				// !@TODO respect arguments
				$php = '$' . $this->varName . '->' . $accessor . '()';
			} elseif (isset($this->failover, $fieldName)) {
				$php = '$' . $this->varName . '->' . $fieldName;
			}
			
			// return the appropriate php
			if ($php){
				if (is_object($value)){
					if ($value instanceof ViewableWrapper) {
						LivePubHelper::$init_code[] = '<?php $' . $value->getVar() . ' = ' . $php . ' ?>';
					}
					// !@TODO: only other option is DataObjectSet - check that this is handled right
				} else {										
					$value = in_array($fieldName, $this->liveVarsUnescaped)
						? '<?php echo ' . $php . '; ?>'
						: '<?php echo htmlentities(' . $php . '); ?>';
					if ($forceReturnedObject) $value = new HTMLText($value);
				}
			}
		}
		
		return $value;
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
/*
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
*/
	
	
	/**
	 * if an object isn't already wrapped in Silverstripe's stuff,
	 * wrap it appropriately either in a viewableWrapper or dataobject set
	 */
	protected function wrapObject($obj) {
		if (is_object($obj)) {
			// if it's an object, just check the type and wrap if needed
			if ($obj instanceof ViewableWrapper) {
				return $obj;
			} else {
				return new ViewableWrapper($obj);
			}
		} elseif (is_array($obj)) {
			// if it's an assoc array just wrap it, otherwise make a dataobjectset
			if (ArrayLib::is_associative($obj)){
				return new ViewableWrapper($obj);
			} else {				
				$set = new DataObjectSet();
		
				foreach ($obj as $i => $item) {
					$wrap = $this->wrapObject($item);
					$set->push($wrap);
				}
		
				return $set;		
			}
		} else {
			// it's a simple type, just return it
			return $obj;
		}
	}
	
	
	/**
	 * This is called by LivePubHelper to retrieve initialization
	 * code that gets added to the top of the cached page.
	 */	
	function getStaticInit() {
		return false;
	}

	function setVar($name) {
		$this->varName = $name;
	}
	
	function getVar() {
		return $this->varName;
	}
	
	function getLiveVars(){
		return $this->liveVars;
	}
	
	function setLiveVars($lv){
		$this->liveVars = $lv;
	}
	
}

