<?php
/**
 * Slight extension of the widget class allowing us to straight up
 * cache the html output at the widgetholder level, saving any template
 * processing or anything.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @created 4.29.10
 * @package sstools
 */
class CachedWidget extends Widget {
	
	protected $cacheID;
	protected $cacheSeconds = 86400; // 24hours
	protected $cacheTags = array('widget');

	
	/**
	 * functions to set/get the cache (not required unless you want to use your own)
	 */
	protected static $cache;
	
	static function get_cache(){
		if (!isset(self::$cache)) {
			self::$cache = SS_Cache::factory('cachedwidget');
		}
		
		return self::$cache;
	}
	
	static function set_cache($c){
		self::$cache = $c;
	}


	/**
	 * default cache id is just the class name
	 */
	function __construct($record = null, $isSingleton = false){
		parent::__construct($record, $isSingleton);
		
		if (!isset($this->cacheID)) {
			if ($this->hasMethod('getCacheID')) {
				$this->cacheID = $this->getCacheID();
			} else {
				$this->cacheID = get_class($this);
			}
		}
	}


	/**
	 * cache the widget at the html level
	 */
	function WidgetHolder(){
		// allow cache override
		if ($this->hasMethod('canUseCache') && !$this->canUseCache()){
			return parent::WidgetHolder();
		}
	
		$cache = Zend_Registry::get('cache');		
		
		if (!$html = $cache->load($this->cacheID)) {
			$html = parent::WidgetHolder();
			$cache->save($html, $this->cacheID, $this->cacheTags, $this->cacheSeconds);
		} elseif (Director::isDev()) {
			$html = "<!-- cached widget -->$html<!-- end cached widget -->";
		}

		return $html;
	}

}