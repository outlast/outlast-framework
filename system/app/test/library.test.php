<?php
/**
 * A standard unit test for Outlast Framework system libraries.
 **/
class OfwLibraryTest extends zajTest {

	/**
	 * Set up stuff.
	 **/
    public function setUp(){
		// Lang setup
			// Set my default locale to en_US, but save my current one before...
				$this->hardcoded_locale = $this->zajlib->zajconf['locale_default'];
			// Now unload lib and change the hardcoded value
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = 'en_US';
    }

	/**
	 * Check language library.
	 **/
	public function system_library_language(){
		// Make sure that en_US is set and returned as default
			zajTestAssert::areIdentical('en_US', $this->zajlib->lang->get_default_locale());
		// So now, let's set the current locale to hu_HU and ensure we get the proper result
			$this->zajlib->lang->set('hu_HU');
			$this->zajlib->lang->load('system/update');
			zajTestAssert::areIdentical('magyar', $this->zajlib->lang->variable->system_update_lang);
		// Finally, let's set it to some crazy unknown language (non-existant) and make sure it works with en_US default
			$this->zajlib->lang->set('xx_XX');
			$this->zajlib->lang->load('system/update');
			zajTestAssert::areIdentical('english', $this->zajlib->lang->variable->system_update_lang);
		// We're done...
	}

	/**
	 * Reset stuff, cleanup.
	 **/
    public function tearDown(){
    	// Lang teardown
			// Set my default locale to en_US, but save my current one before...
				unset($this->zajlib->load->loaded['library']['lang']);
				unset($this->zajlib->lang);
				$this->zajlib->zajconf['locale_default'] = $this->hardcoded_locale;
    }


}

?>