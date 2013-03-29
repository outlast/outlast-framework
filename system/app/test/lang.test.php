<?php
/**
 * A standard unit test for Outlast Framework database changes
 **/
class OfwLangTest extends zajTest {

	private $configvars;

	/**
	 * Set up stuff.
	 **/
	public function setUp(){
		// Save all language variables (for restore afterwards)
			$this->configvars = $this->zajlib->lang->variable;
	}

	/**
	 * Check if certain fields exist.
	 */
	public function verifyLanguageFileVariables(){
		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->zajlib->plugin->get_plugins('app') as $plugin){
			$my_files = $this->zajlib->file->get_files('plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('plugins/'.$plugin.'/lang/', '', $this->zajlib->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->zajlib->test->notice("Found old language file format: ".$file);
				else{
					$file = trim($fdata[0], '/');
					$this->verifySingleLanguageFile($file);
				}
			}
		}
		// Get all of the plugins (local lang files are in _project plugin)
		foreach($this->zajlib->plugin->get_plugins('system') as $plugin){
			$my_files = $this->zajlib->file->get_files('system/plugins/'.$plugin.'/lang/', true);
			foreach($my_files as $f){
				$file = str_ireplace('system/plugins/'.$plugin.'/lang/', '', $this->zajlib->file->get_relative_path($f));
				$fdata = explode('.', $file);
				// Check for old data
				if(strlen($fdata[1]) < 5) $this->zajlib->test->notice("Found old language file format: ".$file);
				else{
					$file = trim($fdata[0], '/');
					$this->verifySingleLanguageFile($file);
				}
			}
		}
	}

	/**
	 * This check if a specific language file is ok in all languages.
	 * @param string $name The specific lang file.
	 * @return void
	 */
	private function verifySingleLanguageFile($name){
		// Clear lang variable
		$this->zajlib->lang->reset_variables();
		// Load up a language file explicitly for default lang
		$default_locale = $this->zajlib->lang->get_default_locale();
		$res = $this->zajlib->lang->load($name.'.'.$default_locale.'.lang.ini', false, false, false);
		if(!$res) $this->zajlib->test->notice("<strong>Could not find default locale lang file!</strong> Not having lang files for default locale may cause fatal errors. We could not find this one: $name.$default_locale.lang.ini.");
		else{
			$default_array = (array) $this->zajlib->lang->variable;
			// Load up a language file explicitly
			foreach($this->zajlib->lang->get_locales() as $locale){
				if($locale != $default_locale){
					$this->zajlib->lang->reset_variables();
					$file = $this->zajlib->lang->load($name.'.'.$locale.'.lang.ini', false, false, false);
					if($file){
						$my_array = (array) $this->zajlib->lang->variable;
						$diff_keys = array_diff_key($default_array, $my_array);
						if(count($diff_keys) > 0){
							$this->zajlib->test->notice("Not all translations from $default_locale found in $locale ($name). Missing the following keys: ".join(', ', array_keys($diff_keys)));
						}
						$rev_diff_keys = array_diff_key($my_array, $default_array);
						if(count($rev_diff_keys) > 0){
							$this->zajlib->test->notice("Some translations in $locale are not found in the default $default_locale locale ($name). Missing the following keys: ".join(', ', array_keys($rev_diff_keys)));
						}
					}

				}
			}
		}
	}


	/**
	 * Reset stuff, cleanup.
	 **/
	public function tearDown(){
		// Clear lang variable
			$this->zajlib->lang->reset_variables();
		// Restore language variables
			$this->zajlib->lang->set_variables($this->configvars);
	}

}