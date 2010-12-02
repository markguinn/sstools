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
		LivePubHelper::$template_path = array(dirname(__FILE__));
		
		// not publishing
		$str = LivePubHelper::include_php('TestPhpTemplate');
		$this->assertEquals($str, 'Hello World');
		
		// publishing
		LivePubHelper::init_pub();
		$str = LivePubHelper::include_php('TestPhpTemplate');
		$this->assertEquals($str, '<?php include "' . dirname(__FILE__) . '/TestPhpTemplate.php"; ?>');
		LivePubHelper::stop_pub();
		
		// !TODO - check for exception on template not found
	}
	
	
	/**
	 * checks that ViewableWrapper works in normal mode
	 */
	function testWrapperUnpublished(){
		if (LivePubHelper::is_publishing()) LivePubHelper::stop_pub();
		
		// test an array
		$o = $this->getArrayWrapper();
		$this->assertEquals($o->a, 'b');
		$this->assertEquals($o->b, 5);
		$this->assertNotNull($o->c);
		$this->assertTrue($o->c instanceof ViewableWrapper);
		$this->assertEquals($o->c->e, 'f');
		$this->assertNotNull($o->d);
		$this->assertTrue($o->d instanceof ViewableWrapper);
		$this->assertEquals($o->d->h, 'i');
		$this->assertTrue($o->e instanceof DataObjectSet);
		$this->assertEquals(
			$this->getTestTemplate()->process($o),
			'A=b, B=5, C.E=f, D.H=i, E=12'
		);
		
		// test an object
		$o = $this->getObjectWrapper();
		$this->assertEquals($o->a, 'b');
		$this->assertEquals($o->b, 5);
		$this->assertNotNull($o->c);
		$this->assertTrue($o->c instanceof ViewableWrapper);
		$this->assertEquals($o->c->e, 'f');
		$this->assertNotNull($o->d);
		$this->assertTrue($o->d instanceof ViewableWrapper);
		$this->assertEquals($o->d->h, 'i');
		$this->assertTrue($o->e instanceof DataObjectSet);
		$this->assertEquals(
			$this->getTestTemplate()->process($o),
			'A=b, B=5, C.E=f, D.H=i, E=12'
		);
	}
	
	
	/**
	 * checks that ViewableWrapper works in published mode
	 */
	function testWrapperPublished(){
		LivePubHelper::init_pub();
		
		// test an array
		$o = $this->getArrayWrapper();
		$o->setVar('myvar');
		$this->assertEquals($o->a, 'b');
		$this->assertEquals($o->b, 5);
		$this->assertNotNull($o->c);
		$this->assertTrue($o->c instanceof ViewableWrapper);
		$this->assertEquals($o->c->e, 'f');
		$this->assertNotNull($o->d);
		$this->assertTrue($o->d instanceof ViewableWrapper);
		$this->assertEquals($o->d->h, 'i');
		$this->assertTrue($o->e instanceof DataObjectSet);
		$this->assertEquals(
			$this->getTestTemplate()->process($o),
			'A=<?php echo htmlentities($myvar["a"]); ?>, B=<?php echo htmlentities($myvar["b"]); ?>, C.E=<?php echo htmlentities($myvar_c["e"]); ?>, D.H=<?php echo htmlentities($myvar_d["h"]); ?>, E=12'
		);
		
		// test an object
		$o = $this->getObjectWrapper();
		$o->setVar('myvar');
		$this->assertEquals($o->a, 'b');
		$this->assertEquals($o->b, 5);
		$this->assertNotNull($o->c);
		$this->assertTrue($o->c instanceof ViewableWrapper);
		$this->assertEquals($o->c->e, 'f');
		$this->assertNotNull($o->d);
		$this->assertTrue($o->d instanceof ViewableWrapper);
		$this->assertEquals($o->d->h, 'i');
		$this->assertTrue($o->e instanceof DataObjectSet);
		$this->assertEquals(
			$this->getTestTemplate()->process($o),
			'A=<?php echo htmlentities($myvar->a); ?>, B=<?php echo htmlentities($myvar->b); ?>, C.E=<?php echo htmlentities($myvar_c["e"]); ?>, D.H=<?php echo htmlentities($myvar_d->h); ?>, E=12'
		);
		
		LivePubHelper::stop_pub();
	}
	
	
	protected function getArrayWrapper(){
		$o2 = new stdClass();
		$o2->h = 'i';
		return LivePubHelper::wrap(array(
			'a' => 'b',
			'b' => 5,
			'c' => array('e'=>'f'),
			'd' => $o2,
			'e' => array(array('a'=>1),array('a'=>2)),
		));
	}


	protected function getObjectWrapper(){
		$o = new stdClass();
		$o->a = 'b';
		$o->b = 5;
		$o->c = array('e'=>'f');
		$o2 = new stdClass();
		$o2->h = 'i';
		$o->d = $o2;
		$o->e = array(array('a'=>1),array('a'=>2));
		return LivePubHelper::wrap($o);
	}
	
	
	protected function getTestTemplate(){
		return SSViewer::fromString('A=$a, B=$b, C.E=$c.e, D.H=$d.h, E=<% control e %>$a<% end_control %>');
	}
	
	

}
