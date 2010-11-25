<?php
/**
 * Overrides the basic DataObjectSet paging so that the links are part of the url path instead
 * of in a GET var.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @date 9.7.10
 */
class UrlPagingDataObjectSet extends DataObjectSet 
{
	protected $pagingBaseUrl;
	
	
	/**
	 * sets the base url. If you give it '/blogs' the urls will be '/blogs/1', '/blogs/2', '/blogs/3', etc
	 * @param string $url
	 */
	function setPagingBaseUrl($url){
		if (substr($url, -1) != '/') $url .= '/';
		$this->pagingBaseUrl = $url;
		return $this;
	}
	
	function getPagingBaseUrl(){
		return $this->pagingBaseUrl;
	}



	/**
	 * Return a datafeed of page-links, good for use in search results, etc.
	 * $maxPages will put an upper limit on the number of pages to return.  It will
	 * show the pages surrounding the current page, so you can still get to the deeper pages.
	 * @param int $maxPages The maximum number of pages to return
	 * @return DataObjectSet
	 */
	public function Pages($maxPages = 0){
		$ret = parent::Pages($maxPages);
		
		foreach ($ret as $page){
			if ($page->Link) $page->Link = $this->pagingBaseUrl . $page->PageNum;
		}
		
		return $ret;
	}


	/*
	 * Display a summarized pagination which limits the number of pages shown
	 * "around" the currently active page for visual balance.
	 * In case more paginated pages have to be displayed, only 
	 * 
	 * Example: 25 pages total, currently on page 6, context of 4 pages
	 * [prev] [1] ... [4] [5] [[6]] [7] [8] ... [25] [next]
	 * 
	 * Example template usage:
	 * <code>
	 * <% if MyPages.MoreThanOnePage %>
	 * 	<% if MyPages.NotFirstPage %>
	 * 		<a class="prev" href="$MyPages.PrevLink">Prev</a>
	 * 	<% end_if %>
	 *  <% control MyPages.PaginationSummary(4) %>
	 * 		<% if CurrentBool %>
	 * 			$PageNum
	 * 		<% else %>
	 * 			<% if Link %>
	 * 				<a href="$Link">$PageNum</a>
	 * 			<% else %>
	 * 				...
	 * 			<% end_if %>
	 * 		<% end_if %>
	 * 	<% end_control %>
	 * 	<% if MyPages.NotLastPage %>
	 * 		<a class="next" href="$MyPages.NextLink">Next</a>
	 * 	<% end_if %>
	 * <% end_if %>
	 * </code>
	 * 
	 * @param integer $context Number of pages to display "around" the current page. Number should be even,
	 * 	because its halved to either side of the current page.
	 * @return 	DataObjectSet
	 */
	public function PaginationSummary($context = 4) {
		$ret = parent::PaginationSummary($context);
		
		foreach ($ret as $page){
			if ($page->Link) $page->Link = $this->pagingBaseUrl . $page->PageNum;
		}
		
		return $ret;
	}
	

	/**
	 * Returns the URL of the previous page.
	 * @return string
	 */
	public function PrevLink() {
		if($this->pageStart - $this->pageLength >= 0) {
			return $this->pagingBaseUrl . ($this->pageStart / $this->pageLength);
		}
	}
	
	/**
	 * Returns the URL of the next page.
	 * @return string
	 */
	public function NextLink() {
		if($this->pageStart + $this->pageLength < $this->totalSize) {
			return $this->pagingBaseUrl . ($this->pageStart / $this->pageLength + 2);
		}
	}



}
