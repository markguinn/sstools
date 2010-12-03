<?php
/**
 * Helper class to facilitate dynamic content inside staticpublisher produced php files.
 *
 * TO INSTALL:
 *  1. Follow basic staticpublisher instructions at http://doc.silverstripe.org/staticpublisher
 *      - NOTE: you have to use 'php' not 'html' caching
 *      - Also, note that it has only been tested with filesystempublisher
 *  2. Comment out or remove cache management stuff in cms/code/staticpublisher/CachedPHPPage.tmpl (only **CONTENT** is required)
 *  3. In cms/code/staticpublisher/FilesystemPublisher.php, publishPages function:
 *		LivePubHelper::init_pub();
 *		foreach($urls as $url => $path) {
 *			LivePubHelper::clear_init_code();
 *			...
 *		}
 *		LivePubHelper::stop_pub();
 *     In the generatePHPCacheFile function:
 *		return str_replace(
 *				array('**MAX_AGE**', '**LAST_MODIFIED**', '**CONTENT**'),
 * //				array((int)$age, $lastModified, $content),
 *				array((int)$age, $lastModified, LivePubHelper::get_init_code() . $content),
 *				$template);
 *  4. Wrap anything that you don't want cached (is_ajax, session, etc)
 *  5. If you want the default template hooks, add this to mysite/_config.php
 *      Object::add_extension("Page_Controller", "LivePubHelper_ControllerHooks");
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 8.5.10
 */
class LivePubHelper {

	protected static $is_publishing = false;

	protected static $silverstripe_db_included = false;



	/**
	 * @var $vars array - each key is a variable name and value is php code to initialize it
	 */	
	public static $vars = array();
	
	/**
	 * @var $init_code array - each entry is a separate block of code, reset for each page
	 */
	public static $init_code = array();
	
	/**
	 * @var $base_init_code string - constant code that is added for all pages
	 */
	public static $base_init_code = '<?php
		$isAjax = (isset($_REQUEST["ajax"]) || (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"));
	?>';

	/**
	 * @var $context string - php|html - tells eval_php whether to wrap code in <?php ?> tag
	 */
 	public static $context = 'html';

	/**
	 * @var $template_path array - where to look for php templates. initially contains /templates/php in project and theme
	 */
	public static $template_path = array();

	/**
	 * this is only to be called when static publishing is starting
	 */
	static function init_pub() {
		global $project;		

		self::$is_publishing = true;

		// if we've set up a global static configuration, add that in
		$file = BASE_PATH . '/' . $project . '/_config_static.php';
		if (file_exists($file)) {
			self::$base_init_code .= "\n<?php include_once('$file') ?>\n";
		}
	}


	/**
	 * called when publishing is done
	 */
	static function stop_pub() {
		self::$is_publishing = false;
	}
	

	/**
	 * resets the init code to the base config file
	 */
	static function clear_init_code() {
		self::$init_code = array();
		self::$vars = array();
		self::$silverstripe_db_included = false;
		if (self::$base_init_code) array_unshift(self::$init_code, self::$base_init_code);
	}
	
	
	/**
	 * Outputs the static config code if needed. This should be placed in a function in Page_Controller and called from template.
	 */
	static function get_init_code() {
		if (self::is_publishing()) {
			$code = "";

			// if objects have set up initialization code, add that in
			foreach (self::$init_code as $block){
				$block = trim($block);
				if (strlen($block) == 0) continue;
				if (substr($block, 0, 5) != '<?php') $code .= '<?php ';
				$code .= $block;
				if (substr($block, -2) != '?>') $code .= ' ?>';
				$code .= "\n";
			}
			
			// if there are variables to initialize, add that in
			if (count(self::$vars) > 0) {
				$code .= "\n<?php\n";
				foreach (self::$vars as $var => $valCode) {
					if ($valCode === true) continue; // this means it was already set in the initialization
					$code .= '$' . $var . ' = ' . $valCode . ";\n";
				}
				$code .= "\n?>\n";
			}
			
			return $code;
		}
	}
	

	/**
	 * returns true if we are currently publishing
	 * @return boolean
	 */
	static function is_publishing() {
		// this will break if you ever turned off $echo_progress on purpose
		//return class_exists('StaticPublisher') && StaticPublisher::$echo_progress;
		return self::$is_publishing;
	}


	/**
	 * evaluates the given php code unless we're currently publishing, in which case it
	 * returns php code that will echo the return value of the eval'd code.
	 *
	 * @param string $code
	 * @return string
	 */
	static function eval_php($code) {
		if (self::is_publishing()) {
			return self::$context=='html'
				? '<?php echo eval(\'' . addcslashes($code, "'") . '\'); ?>'
				: 'eval(\'' . addcslashes($code, "'") . '\')';
		} else {
			return eval($code);
		}
	}
	
	
	/**
	 * evaluates the given php code unless we're currently publishing, in which case it
	 * adds the php code to the initialization code.
	 * NOTE: this will not return or output the result
	 *
	 * @param string $code
	 * @return none
	 */
	static function exec_php($code, $alwaysExec=false) {
		if (self::is_publishing()) {
			self::$init_code[] = $code;
			if ($alwaysExec) eval($code);
		} else {
			eval($code);
		}
	}
	

	/**
	 * loads a php template from the templates/php folder (allowing for themes)
	 * returns either the result of executing the code, or the code itself,
	 * depending on whether we're staticpublishing or not
	 *
	 * @param string $filename
	 * @return string
	 */
	static function include_php($filename) {
		global $project;

		// set up default template paths if needed
		if (count(self::$template_path) == 0) {
			self::$template_path[] = BASE_PATH . '/' . THEMES_DIR . '/' . SSViewer::current_theme() . '/templates/php';
			self::$template_path[] = BASE_PATH . '/' . $project . '/templates/php';
		}
		
		// check all the possible paths we've accumulated		
		$tpl = false;
		foreach (self::$template_path as $path){
			$checkPath = $path . '/' . $filename . '.php';
			
			if (file_exists($checkPath)) {
				$tpl = $checkPath;
				break;
			}
		}

		if (!$tpl) {
			throw new Exception("Unable to locate PHP template: $filename (paths=".implode(':', self::$template_path).")");
		}
		
		// load it up
		if (self::is_publishing()) {
			//return file_get_contents($tpl);
			return '<?php include "' . $tpl . '"; ?>';
		} else {
			ob_start();
			include $tpl;
			$html = ob_get_contents();
			ob_end_clean();
			//return '<!-- php template -->' . $html . '<!-- end php template -->';
			return $html;
		}
	}
	
	
	/**
	 * factory method to create a new wrapper object. if we're
	 * static publishing and an appropriate helper class is 
	 * available it will use that instead. A helper class shouldn't
	 * be needed very often but would be used if you wanted a totally
	 * different class for publishing vs normal mode
	 *
	 * @param object|array $srcdata
	 * @param string $class - what class to wrap it in
	 * @param boolean $add_init_code [optional] - if true, the classes default static init code will be added automatically
	 * @return ViewableWrapper
	 */
	static function wrap($object, $class = 'ViewableWrapper', $add_init_code=true) {
		if (self::is_publishing()) {
			$class2 = "{$class}_LivePub";
			if (class_exists($class2, true)) $class = $class2;

			$obj = new $class($object);
			if ($add_init_code && ($init = $obj->getStaticInit())) self::$init_code[] = $init;
		} else {
			$obj = new $class($object);
		}

		return $obj;
	}
	
	
	/**
	 * if this is called, the published version of the page
	 * will include and initialize the DB::query stub (see DBWrapper.php)
	 * and connect to the main silverstripe database.
	 * This allows limited use of DB::query() in both a live
	 * and published context.	 
	 */
	static function require_silverstripe_db() {
		if (self::is_publishing() && !self::$silverstripe_db_included) {
			self::$init_code[] = '
				require_once "'.dirname(__FILE__).'/classes/LivePubDB.php";
				DB::init();
			';
		}
	}
	
	
}



/**
 * add some methods to controllers for accessing this functionality in templates
 */
class LivePubHelper_ControllerHooks extends Extension {


	/**
	 * allows you to include php templates that work even when static publishing is enabled 
	 */
	function IncludePHP($tpl){
		return LivePubHelper::include_php($tpl);
	}


	/**
	 * returns a viewable wrapper around the session
	 */
	function WrappedSession(){
		LivePubHelper::$init_code[] = 'if (!session_id()) session_start();';
		$obj = new LivePubWrapper($_SESSION);
		$obj->setVar('_SESSION');
		return $obj;
	}


	/**
	 * returns a viewable wrapper around the request
	 */
	function WrappedRequest(){
		$obj = new LivePubWrapper($_REQUEST);
		$obj->setVar('_REQUEST');
		return $obj;
	}


	/**
	 * are we currently publishing?
	 */
	function LPH_IsPublishing(){
		return LivePubHelper::is_publishing();
	}
	
	function LPH_NotPublishing(){
		return !LivePubHelper::is_publishing();
	}


	/**
	 * if we're publishing, outputs an if statement
	 * $func should be something on the controller that
	 * is livepub sensitive (either uses eval_php or
	 * some other method to return code)
	 */
	function LPH_If($func){
		if (LivePubHelper::is_publishing()) {
			LivePubHelper::$context = 'php';
 			$str = $this->getOwner()->$func();
 			LivePubHelper::$context = 'html';
 			return '<?php if (' . $str . '): ?>';
		}
	}
	
	
	/**
	 * is this an ajax request? NOTE: this will not actually function as an IF
	 * statement in a live silverstripe template. You have to wrap them together
	 * like this for it to work in both live and published modes:
	 * <% if LPH_NotPublishing && isAjax %><% else %>$LPH_IfNotAjax
	 * $LPH_EndIf<% end_if %>
	 */
	function LPH_IfAjax(){
		if (LivePubHelper::is_publishing()) {
			return '<?php if ($isAjax): ?>';
		}
	}

	function LPH_IfNotAjax(){
		if (LivePubHelper::is_publishing()) {
			return '<?php if (!$isAjax): ?>';
		}
	}
	
	
	/**
	 * output generic php closing the above functions
	 */
	function LPH_Else(){
		if (LivePubHelper::is_publishing()) {
			return '<?php else: ?>';
		}
	}
	
	function LPH_EndIf(){
		if (LivePubHelper::is_publishing()) {
			return '<?php endif; ?>';
		}
	}

}
