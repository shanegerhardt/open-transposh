<?php

namespace Tests\Core;

use BetterTransposh\Core\Logger;
use BetterTransposh\Core\Parser;
use WP_UnitTestCase;

/**
 * Test class for parser.
 * Generated by PHPUnit on 2010-02-09 at 00:58:18.
 */
class Parser_Tests extends WP_UnitTestCase {

	/**
	 * @var    Parser
	 * @access protected
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp(): void {
		$GLOBALS['logger']              = Logger::getInstance( true );
		$GLOBALS['logger']->show_caller = true;
		$GLOBALS['logger']->set_debug_level( 5 );
		$GLOBALS['logger']->eolprint = true;
		$GLOBALS['logger']->printout = true;
		$GLOBALS['logger']->set_log_file( "/tmp/phpunit.log" );
		$this->object = new Parser;
	}

	/**
	 * @todo Implement testIs_white_space().
	 */
	public function testIs_white_space() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_a_to_z_character().
	 */
	public function testIs_a_to_z_character() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_digit().
	 */
	public function testIs_digit() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_html_entity().
	 */
	public function testIs_html_entity() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_entity_breaker().
	 */
	public function testIs_entity_breaker() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_entity_letter().
	 */
	public function testIs_entity_letter() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_sentence_breaker().
	 */
	public function testIs_sentence_breaker() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testIs_number().
	 */
	public function testIs_number() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testTag_phrase().
	 */
	public function testTag_phrase() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testParsetext().
	 */
	public function testParsetext() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testTranslate_tagging().
	 */
	public function testTranslate_tagging() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @todo Implement testCreate_edit_span().
	 */
	public function testCreate_edit_span() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	function fetch_translation( $original_text, $lang ) {
		//echo "fetch for: <b>$original_text</b><br/>";
		tp_logger( "fetch for: $original_text, returning z-$original_text-z" );

		return array( 0, "z-$original_text-z" );
	}

	function rewrite( $original_text ) {
		echo "rewrite for: <b>$original_text</b><br/>";

		return $original_text;
	}

	/**
	 * @todo Implement testFix_html().
	 */
	public function testFix_html() {
		// Remove the following lines when you implement this test.
		$parse                       = $this->object;
		$parse->fetch_translate_func = array( &$this, 'fetch_translation' );
		//$parse->prefetch_translate_func = array(&$this->database, 'prefetch_translations');
		$parse->url_rewrite_func  = array( &$this, 'rewrite' );
		$parse->dir_rtl           = true;
		$parse->lang              = 'he';
		$parse->default_lang      = false;
		$parse->is_edit_mode      = false;
		$parse->is_auto_translate = false;
		$parse->allow_ad          = false;
		//$this->expectOutputString('blah');
		//echo $parse->fix_html('<html><body>hello, world</body></html>');
		$this->assertEquals( '<html lang="he" dir="rtl"></html>', $parse->fix_html( '<html></html>' ) );
		$this->assertEquals( '<html dir="rtl" lang="he"></html>', $parse->fix_html( '<html dir="rtl" lang="he"></html>' ) );
		$this->assertEquals( '<html lang="he" dir="rtl"><body>z-hello-z, z-world-z</body></html>', $parse->fix_html( '<html><body>hello, world</body></html>' ) );
		$this->assertEquals( '<html lang="he" dir="rtl"><body>z-hello-z, z-world-z, z-hello world-z</body></html>', $parse->fix_html( '<html><body>hello, world, hello world</body></html>' ) );
		$this->assertEquals( '<html lang="he" dir="rtl"><body><a title="z-hello-z, z-world-z, z-hello world-z">z-hi-z</a></body></html>', $parse->fix_html( '<html><body><a title="hello, world, hello world">hi</a></body></html>' ) );

		// $this->assertEquals('<html lang="he" dir="rtl"><body>z-hello, world-z</body></html>', $parse->fix_html('<html><body>&transposh;hello, world&transposh;</body></html>'));


		$parse->is_edit_mode = true;
		$this->assertEquals( '<html lang="he" dir="rtl"><body><span class ="tr_" id="tr_0" data-source="0" data-orig="hello">z-hello-z</span>, <span class ="tr_" id="tr_1" data-source="0" data-orig="world">z-world-z</span>, <span class ="tr_" id="tr_2" data-source="0" data-orig="hello world">z-hello world-z</span></body></html>', $parse->fix_html( '<html><body>hello, world, hello world</body></html>' ) );
		//$this->assertEquals('<html lang="he" dir="rtl"><body><span class ="tr_" id="tr_2" data-token="aGVsbG8," data-source="0" data-orig="hello">z-hello-z</span>, <span class ="tr_" id="tr_1" data-token="d29ybGQ," data-source="0" data-orig="world">z-world-z</span>, <span class ="tr_" id="tr_0" data-token="aGVsbG8gd29ybGQ," data-source="0" data-orig="hello world">z-hello world-z</span></body></html>', $parse->fix_html('<html><body>hello, world, hello world</body></html>'));
		$this->assertEquals( '<html lang="he" dir="rtl"><body><span class ="tr_" id="tr_3" data-source="0" data-orig="hello">z-hello-z</span>, <span class ="tr_" id="tr_4" data-source="0" data-orig="world">z-world-z</span>,<a title="z-hi-z" href="b"><span class ="tr_" id="tr_5" data-source="0" data-orig="ho">z-ho-z</span></a><span class ="tr_" id="tr_7" data-source="0" data-orig="hi" data-hidden="y" data-trans="z-hi-z"></span> <span class ="tr_" id="tr_6" data-source="0" data-orig="hello world">z-hello world-z</span></body></html>', $parse->fix_html( '<html><body>hello, world,<a title="hi" href="b">ho</a> hello world</body></html>' ) );
		//$this->assertEquals('<html lang="he" dir="rtl"><body><span class ="tr_" id="tr_4" data-token="aGVsbG8," data-source="0" data-orig="hello">z-hello-z</span>, <span class ="tr_" id="tr_3" data-token="d29ybGQ," data-source="0" data-orig="world">z-world-z</span>,<a title="z-hi-z" href="b"><span class ="tr_" id="tr_5" data-token="aG8," data-source="0" data-orig="ho">z-ho-z</span></a><span class ="tr_" id="tr_7" data-token="aGk," data-source="0" data-orig="hi" data-hidden="y" data-trans="z-hi-z"></span> <span class ="tr_" id="tr_6" data-token="aGVsbG8gd29ybGQ," data-source="0" data-orig="hello world">z-hello world-z</span></body></html>', $parse->fix_html('<html><body>hello, world,<a title="hi" href="b">ho</a> hello world</body></html>'));
	}

	public function testADReplace_html() {
		// Remove the following lines when you implement this test.
		$parse                       = $this->object;
		$parse->fetch_translate_func = array( &$this, 'fetch_translation' );
		//$parse->prefetch_translate_func = array(&$this->database, 'prefetch_translations');
		$parse->url_rewrite_func  = array( &$this, 'rewrite' );
		$parse->dir_rtl           = true;
		$parse->lang              = 'he';
		$parse->default_lang      = false;
		$parse->is_edit_mode      = false;
		$parse->is_auto_translate = false;
		$parse->allow_ad          = true;
		$testhtml                 = '<html><ins class="adsbygoogle" data-ad-format="auto" data-ad-slot="7652439345" data-ad-client="ca-pub-7523823497771676" style="display:block"></ins></html>';
		$testoutput               = '<html lang="he" dir="rtl"><ins class="adsbygoogle" data-ad-format="auto" data-ad-slot="7652439345" data-ad-client="ca-pub-7523823497771676" style="display:block"></ins></html>';
		for ( $i = 0; $i < 100; $i ++ ) {
			echo $i . "\n";
			$this->assertEquals( $parse->fix_html( $testhtml ), $testoutput );
		}
	}

	/**
	 * @todo Implement testGet_phrases_list().
	 */
	public function testGet_phrases_list() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	private function anonArraytoKnown( $array ) {
		foreach ( $array as $key => $value ) {
			$return[ $value ] = $value;
		}

		return $return;
	}

	private function runtestCut( $string, $array ) {
		$this->assertEquals( $this->anonArraytoKnown( $array ), $this->object->get_phrases_list( $string ) );
	}

	/**
	 * @todo Implement testGet_phrases_list().
	 */
	public function testParsing() {
		$this->runtestCut( "a, b", array( 'a', 'b' ) );
		$this->runtestCut( "hello , world", array( 'hello', 'world' ) );
		$this->runtestCut( "here at 42nd, street", array( 'here at 42nd', 'street' ) );
		//$this->runtestCut("42nd, street", array('42nd', 'street'));
		//$this->runtestCut("2b or not 2b", array('2b or not 2b'));
		$this->runtestCut( "again, again, and again", array( 'again', 'and again' ) );
		//   $this->testCut("again, again again, again    again, and again", array('again','again again','and again'));
		$this->runtestCut( "there are 100 bottles of bear on the wall", array(
			'there are',
			'bottles of bear on the wall'
		) );
		$this->runtestCut( "there are 100.5 bottles of bear on the wall", array(
			'there are',
			'bottles of bear on the wall'
		) );
		$this->runtestCut( "1) do this", array( 'do this' ) );
		$this->runtestCut( "a $100", array( 'a' ) );
		$this->runtestCut( "b 100$", array( 'b' ) );
		$this->runtestCut( "b100$", array( 'b100$' ) );
		$this->runtestCut( "b100$,", array( 'b100$' ) );
		$this->runtestCut( "a 1", array( 'a' ) );
		$this->runtestCut( "a (1920-30)", array( 'a' ) );
		$this->runtestCut( "some <b>html</b>is here,", array( 'some', 'html', 'is here' ) );
	}

}


