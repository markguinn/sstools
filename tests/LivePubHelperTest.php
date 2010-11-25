<?php
/**
 * Tests for LivePubHelper and LivePubWrapper.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @subpackage tests
 * @date 9.26.10
 */
class LivePubHelperTest extends SapphireTest {

	/**
	 * tests that the is_publishing method is working correctly
	 */
	function testIsPublishing(){
		$this->assertFalse( LivePubHelper::is_publishing() );
		
		LivePubHelper::init_pub();
		$this->assertTrue( LivePubHelper::is_publishing() );

		LivePubHelper::stop_pub();
		$this->assertFalse( LivePubHelper::is_publishing() );
	}
	
	
	/**
	 * tests the functionality related to the init code
	 */
	function testInitCode(){
		LivePubHelper::$vars = array();
		LivePubHelper::init_pub();
		
		// after this is called, there should be one entry - the base_init_code
		LivePubHelper::clear_init_code();
		$this->assertEquals( count(LivePubHelper::$init_code), 1 );
		$this->assertEquals( LivePubHelper::get_init_code(), LivePubHelper::$base_init_code."\n" );
		
		// try a few things to make sure the php tags are added appropriately
		LivePubHelper::$init_code = array('test');
		$this->assertEquals( "<?php test ?>\n", LivePubHelper::get_init_code() );
		LivePubHelper::$init_code = array('<?php test');
		$this->assertEquals( "<?php test ?>\n", LivePubHelper::get_init_code() );
		LivePubHelper::$init_code = array('test ?>');
		$this->assertEquals( "<?php test ?>\n", LivePubHelper::get_init_code() );
		LivePubHelper::$init_code = array('<?php test ?>');
		$this->assertEquals( "<?php test ?>\n", LivePubHelper::get_init_code() );
		LivePubHelper::$init_code = array();
		$this->assertEquals( "", LivePubHelper::get_init_code() );

		// test variables
		LivePubHelper::$vars = array(
			'a' => "'b'",
			'b' => true,
			'c' => "4",
		);
		$this->assertEquals( "\n<?php\n\$a = 'b';\n\$c = 4;\n\n?>\n", LivePubHelper::get_init_code() );
		
		LivePubHelper::stop_pub();
	}


	/**
	 * checks eval_php
	 */
	function testEvalPhp(){
		// when not publishing, should just return the value
		$s = LivePubHelper::eval_php('return 5;');
		$this->assertEquals( $s, 5 );
	
		// when publishing should return code
		LivePubHelper::init_pub();

		LivePubHelper::$context = 'php';
		$s = LivePubHelper::eval_php('return 5;');
		$this->assertEquals( $s, "eval('return 5;')" );

		LivePubHelper::$context = 'html';
		$s = LivePubHelper::eval_php('return 5;');
		$this->assertEquals( $s, "<?php echo eval('return 5;'); ?>" );

		LivePubHelper::stop_pub();
	}
	

	/**
	 * checks exec_php
	 */
	function testExecPhp(){
		LivePubHelper::$init_code = array();
		
		// not publishing
		LivePubHelper::exec_php('$a = 5;');
		$this->assertEquals( count(LivePubHelper::$init_code), 0 );
			
		// publishing
		LivePubHelper::init_pub();
		LivePubHelper::exec_php('$a = 5;');
		$this->assertEquals( count(LivePubHelper::$init_code), 1 );
		LivePubHelper::stop_pub();
	}
	
	
	function testIncludePhp(){
	}
	
	
	function testWrapper(){
	}
	
}
