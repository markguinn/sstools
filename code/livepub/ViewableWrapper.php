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
			$src->class = get_class($src);
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

			// if this variable isn't live, none of it's children should be
			if (!$this->isLiveVar($field)){
				$val->setLiveVars(false);
			}
		}
		
		return $val;
	}
	

	/**
	 * Is this variable live in published mode?
	 * @param string $field
	 * @return bool
	 */
	function isLiveVar($fieldName){
		return $this->liveVars && (!is_array($this->liveVars) || in_array($fieldName, $this->liveVars));
	}


	/**
	 * If we're publishing, returns proper php
	 */
	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		$value = parent::obj($fieldName, $arguments, $forceReturnedObject, $cache, $cacheName);

		// if we're publishing and this variable is qualified, output php code instead
		if (
			$this->failover 
			&& $this->liveVars
			&& isset($this->varName)
			&& LivePubHelper::is_publishing() 
			&& $this->isLiveVar($fieldName)
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
	 * returns the given field as a casted date object
	 */
	function AsDate($field) {
		$d = $this->$field;
		return DBField::create('Date', is_numeric($d) ? date('Y-m-d H:i:s', $d) : $d);
	}


	/**
	 * returns the given field as a casted date object
	 */
	function AsCurrency($field) {
		return DBField::create('Currency', $this->$field);
	}


	/**
	 * returns the given field as a uniformly formatted phone #
	 * !todo - does this need to work differently for internationals?
	 */
	function AsPhone($field) {
		$str = preg_replace('/[^0-9]/', '', $this->$field);
		$out = '';

		switch (strlen($str)) {
			case 11:
			case 12:
				$str = ltrim($str, '0');
				$out .= '+' . substr($str, 0, -10) . ' ';
			case 10:
				$out .= '(' . substr($str, -10, 3) . ') ';
			case 7:
				$out .= substr($str, -7, 3) . '-' . substr($str, -4);
			break;

			default:
				$out = $str;
		}

		return $out;
	}


	function DebugMe() {
		Debug::dump($this);
	}
	
	
	/**
	 * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object) for a field
	 * on this object. MODIFIED TO LEAVE FAILOVER ALONE (so it doesn't have to inherit Object).
	 *
	 * @param string $field
	 * @return string
	 */
	public function castingHelper($field) {
		if($this->hasMethod('db') && $fieldSpec = $this->db($field)) {
			return $fieldSpec;
		}

		$specs = Object::combined_static(get_class($this), 'casting');
		if(isset($specs[$field])) return $specs[$field];

		//if($this->failover) return $this->failover->castingHelper($field);
	}

	/**
	 * This is called by LivePubHelper to retrieve initialization
	 * code that gets added to the top of the cached page.
	 */	
	function getStaticInit() {
		return false;
	}


	/**
	 * Set the variable name used for nested wrappers
	 * in published mode.
	 * @param string $name
	 * @return ViewableWrapper (chainable)
	 */
	function setVar($name) {
		$this->varName = $name;
		return $this;
	}


	/**
	 * @return string
	 */
	function getVar() {
		return $this->varName;
	}


	/**
	 * @param  bool|array $lv
	 * @return ViewableWrapper (chainable)
	 */
	function setLiveVars($lv){
		$this->liveVars = $lv;
		return $this;
	}


	/**
	 * @return bool|array
	 */
	function getLiveVars(){
		return $this->liveVars;
	}


	/**
	 * Sets which variables should NOT be escaped.
	 * @NOTE this only affects published mode
	 * @param  array $vars
	 * @return ViewableWrapper (chainable)
	 */
	function setUnescapedVars(array $vars){
		$this->liveVarsUnescaped = $vars;
		return $this;
	}


	/**
	 * @return array
	 */
	function getUnescapedVars(){
		return $this->liveVarsUnescaped;
	}

}

