<?php
/**
 * Standard basic search form which does a simple LIKE search on
 * one or more fields, returning the actual full page object.
 * Made to be a simple way to search other types of objects
 *
 * If multilingual content is enabled through the {@link Translatable} extension,
 * only pages the currently set language on the holder for this searchform are found.
 * The language is set through a hidden field in the form, which is prepoluated
 * with {@link Translatable::get_current_locale()} when then form is constructed.
 * 
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 */
class AltSearchForm extends Form {
	
	/**
	 * @var int $pageLength How many results are shown per page.
	 * Relies on pagination being implemented in the search results template.
	 */
	protected $pageLength = 10;
	
	
	/**
	 * @var string $extraFilter - additional SQL for the where clause, if present
	 */
	protected $extraFilter = "";
	
	
	/**
	 * @var string $resultClass - allows you to get the results as a different class
	 */
	protected $resultClass = "Page";
	
	
	/**
	 * @var string $sortBy
	 */
	protected $sortBy = "LastEdited DESC";
	
	
	/**
	 * @var array $searchFields
	 */
	protected $searchFields = array("Title","Content");
	
	
	/**
	 * @var array $callbacks - functions that get called to filter the results
	 */
	protected $callbacks = array();
	
	
	/**
	 * 
	 * @param Controller $controller
	 * @param string $name The name of the form (used in URL addressing)
	 * @param FieldSet $fields Optional, defaults to a single field named "Search". Search logic needs to be customized
	 *  if fields are added to the form.
	 * @param FieldSet $actions Optional, defaults to a single field named "Go".
	 * @param boolean $showInSearchTurnOn DEPRECATED 2.3
	 */
	function __construct($controller, $name, $fields = null, $actions = null, $extraFilter = "", $resultClass = "Page") {
		if(!$fields) {
			$fields = new FieldSet(
				new TextField('Search', _t('SearchForm.SEARCH', 'Search')
			));
		}
		
		if(singleton('SiteTree')->hasExtension('Translatable')) {
			$fields->push(new HiddenField('locale', 'locale', Translatable::get_current_locale()));
		}
		
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("getResults", _t('SearchForm.GO', 'Go'))
			);
		}
				
		parent::__construct($controller, $name, $fields, $actions);
		$this->setFormMethod('get');
		$this->disableSecurityToken();
		$this->extraFilter = $extraFilter;
		$this->resultClass = $resultClass;
	}


	public function forTemplate() {
		return $this->renderWith(array(
			'SearchForm',
			'Form'
		));
	}


	/**
	 * Return dataObjectSet of the results using $_REQUEST to get info from form.
	 * @return DataObjectSet
	 */
	public function getResults() {
		$data = $_REQUEST;
		
		// set language (if present)
		if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
			$origLocale = Translatable::get_current_locale();
			Translatable::set_current_locale($data['locale']);
		}

		// do it	
		$results = $this->searchEngine($data['Search']);
		
		// filter by permission
		if($results) foreach($results as $result) {
			if(!$result->canView()) $results->remove($result);
		}
		
		// filter by callback if asked
		foreach ($this->callbacks as $func) {
			$results = call_user_func($func, $results);
		}		
		
		// reset locale
		if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
			Translatable::set_current_locale($origLocale);
		}
		
		return $results;
	}


	/**
	 * The core search engine, used by this class and its subclasses to do fun stuff.
	 * Searches both SiteTree and File.
	 * 
	 * @param string $keywords Keywords as a string.
	 */
	public function searchEngine($keywords, $totalOnly=false) {
	 	$SQL_keywords = Convert::raw2sql($keywords);	
		$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		$limit = $start . ", " . (int) $this->pageLength;
		
		$query = singleton($this->resultClass)->extendedSQL("", $this->sortBy, $limit);

		// build the query
		$ors = array();
		foreach ($this->searchFields as $f) $ors[] = "$f LIKE '%{$SQL_keywords}%'";
		$query->where[] = "(" . implode(' OR ', $ors) . ")";
		if ($this->extraFilter) $query->where[] = $this->extraFilter;

		// Get records
		$totalCount = $query->unlimitedRowCount();
		if ($totalOnly) return $totalCount;
		
		if ($totalCount > 0){
			$result = $query->execute();
			$doSet = singleton($this->resultClass)->buildDataObjectSet($result);
			$doSet->setPageLimits($start, $this->pageLength, $totalCount);
		} else {
			$doSet = new DataObjectSet();
		}
		
		return $doSet;
	}
	
	
	/**
	 * Get the search query for display in a "You searched for ..." sentence.
	 * 
	 * @param array $data
	 * @return string
	 */
	public function getSearchQuery() {
		return Convert::raw2xml($_REQUEST['Search']);
	}
	
	/**
	 * Set the maximum number of records shown on each page.
	 * 
	 * @param int $length
	 */
	public function setPageLength($length) {
		$this->pageLength = $length;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getPageLength() {
		return $this->pageLength;
	}


	public function setSortBy($sortBy) {
		$this->sortBy = $sortBy;
		return $this;
	}
	
	
	public function setExtraFilter($f) {
		$this->extraFilter = $f;
		return $this;
	}
	
	
	public function setResultClass($c) {
		$this->resultClass = $c;
		return $this;
	}
	
	
	public function setSearchFields($f) {
		$this->searchFields = is_array($f) ? $f : array($f);
		return $this;
	}


	public function addCallbackFilter($func) {
		$this->callbacks[] = $func;
		return $this;
	}
}

