<?php
/**
 * A standard unit test for Outlast Framework system libraries.
 **/
class OfwTemplateTest extends zajTest {

	/**
	 * Set up stuff.
	 **/
	public function setUp(){
		// Create some mock template variables
		$this->zajlib->variable->mock_int = 100;
		$this->zajlib->variable->mock_string = 'This is a test string.';
		$this->zajlib->lang->variable->mock_localized_int = 5;
		$this->zajlib->lang->variable->mock_localized_string = 'This is a localized string.';
	}

	/**
	 * Check template compilation with a challenging test template.
	 **/
	public function system_template_compile(){
		// Compile my template tester and fetch content
		$returned_content = $this->zajlib->template->show('system/test/template_tester.html', true, true);
		// If there is a problem, you can uncomment this to print the returned content and see what's going on...
		//print $returned_content;
		// Let's make sure we start and end properly
		zajTestAssert::areIdentical('start', substr(trim($returned_content), 0, 5));
		zajTestAssert::areIdentical('done', substr(trim($returned_content), -4));
		// We're done...
	}

	/**
	 * Reset stuff, cleanup.
	 **/
	public function tearDown(){
		// Unset mock vars
		unset($this->zajlib->variable->mock_int);
		unset($this->zajlib->variable->mock_string);
		unset($this->zajlib->lang->variable->mock_localized_int);
		unset($this->zajlib->lang->variable->mock_localized_string);
	}


}

?>