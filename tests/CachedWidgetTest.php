<?php
/**
 * Tests for CachedWidget
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @subpackage tests
 * @date 11.25.10
 */
class CachedWidgetTest extends SapphireTest {

	function testWidgetOutput(){
		$widget = new CachedWidgetTest_Widget();
		$this->assertEquals($widget->getCacheID(), 'CachedWidgetTest_Widget');
		
		$cache = CachedWidget::get_cache();
		$this->assertNotNull($cache);
		
		$cache->remove($widget->getCacheID());
		$firstHTML = $widget->WidgetHolder();
		$secondHTML = $widget->WidgetHolder();
		$this->assertNotEquals(substr($firstHTML, 0, 22), '<!-- cached widget -->');
		$this->assertEquals("<!-- cached widget -->$firstHTML<!-- end cached widget -->", $secondHTML);
	}

}


/**
 * example widget used in testing
 */
class CachedWidgetTest_Widget extends CachedWidget implements TestOnly {

	static $title = 'Cached Test Widget';

	function Content(){
		return 'TESTWIDGET@' . time();
	}

}
