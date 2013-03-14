<?php
/**
 * Useful for fetching information about the user's browser.
 * @require Up-to-date version of browscap file, located in the system folder of Outlast Framework.
 * @package Library
 **/


/**
 * Class zajlib_browser
 * @property string browser The browser name.
 * @property string version The full version number.
 * @property integer majorver The major version.
 * @property float minorver The minor version.
 * @property string platform Platform name.
 * @property string platform_version Platform version.
 * @property bool alpha True if this is an alpha version.
 * @property bool beta True if this is a beta version.
 * @property bool win16 True if win16.
 * @property bool win32 True if win32.
 * @property bool win64 True if win64.
 * @property bool frames True if frames are supported.
 * @property bool iframes True if iframes are supported.
 * @property bool tables True if tables are supported.
 * @property bool cookies True if cookies are supported.
 * @property bool backgroundsounds True if sounds are supported.
 * @property bool javascript True if javascript is supported.
 * @property bool vbscript True if vbscript is supported.
 * @property bool javaapplets True if java is supported.
 * @property bool activexcontrols True if activex is supported.
 * @property bool ismobiledevice True if this is a mobile device (including tablets).
 * @property bool issyndicationreader True if this is an RSS reader.
 * @property bool crawler True if this is a crawler.
 * @property string cssversion CSS version support.
 * @property string aolversion AOL version support.
 */
class zajlib_browser extends zajLibExtension {

	private $data = false;

	private $browscapIni = null;
	private $browscapPath = '';

	/**
	 * This will return the browscap info by user agent.
	 * @param string|bool $user_agent A custom user agent string. If not set, your own will be used.
	 * @return zajlib_browser Returns the object ready for chaining.
	 */
	public function get($user_agent = false){
		// My own user agent (parse data only once)
			if($user_agent === false){
				if($this->data === false){
					$data = $this->get_browser_local(null,false,$this->zajlib->basepath.'system/ext/browscap.ini');
					// Switch out boolean values
					foreach($data as $key=>$val){
						if($val == 'true') $data->$key = true;
						if($val == 'false') $data->$key = false;
					}
					$this->data = $data;
				}
				else $data = $this->data;
			}
		// Custom user agent
			else{
				// Get by custom user agent string
					$data = $this->get_browser_local($user_agent,false,$this->zajlib->basepath.'system/ext/browscap.ini');
				// Switch out boolean values
					foreach($data as $key=>$val){
						if($val == 'true') $data->$key = true;
						if($val == 'false') $data->$key = false;
					}
			}
		return $data;
	}


	/**
	 * The basic function will return the full browscap info.
	 * @param string $name The name of the variable requested.
	 * @return mixed Returns the data from browscap.
	 */
	public function __get($name){
		// Get with my own user agent
			$this->get();
		// Make sure boolean is returned as boolean
			if($this->data->$name == 'false') return false;
			if($this->data->$name == 'true') return true;
		return $this->data->$name;
	}


	/**
	 * Private methods.
	 */

	private function _sortBrowscap($a,$b){
		$sa=strlen($a);
		$sb=strlen($b);
		if ($sa>$sb) return -1;
		elseif ($sa<$sb) return 1;
		else return strcasecmp($a,$b);
	}

	private function _lowerBrowscap($r) {return array_change_key_case($r,CASE_LOWER);}

	private function get_browser_local($user_agent=null,$return_array=false,$db='./browscap.ini',$cache=false){
		//http://alexandre.alapetite.fr/doc-alex/php-local-browscap/
		//Get php_browscap.ini on http://browsers.garykeith.com/downloads.asp
		if (($user_agent==null)&&isset($_SERVER['HTTP_USER_AGENT'])) $user_agent=$_SERVER['HTTP_USER_AGENT'];
		if ((!isset($this->browscapIni))||(!$cache)||($this->browscapPath!==$db))
		{
			$this->browscapIni=defined('INI_SCANNER_RAW') ? parse_ini_file($db,true,INI_SCANNER_RAW) : parse_ini_file($db,true);
			$this->browscapPath=$db;
			uksort($this->browscapIni,array($this, '_sortBrowscap'));
			$this->browscapIni=array_map(array($this, '_lowerBrowscap'),$this->browscapIni);
		}
		$cap=null;
		foreach ($this->browscapIni as $key=>$value)
		{
			if (($key!='*')&&(!array_key_exists('parent',$value))) continue;
			$keyEreg='^'.str_replace(
				array('\\','.','?','*','^','$','[',']','|','(',')','+','{','}','%'),
				array('\\\\','\\.','.','.*','\\^','\\$','\\[','\\]','\\|','\\(','\\)','\\+','\\{','\\}','\\%'),
				$key).'$';
			if (preg_match('%'.$keyEreg.'%i',$user_agent))
			{
				$cap=array('browser_name_regex'=>strtolower($keyEreg),'browser_name_pattern'=>$key)+$value;
				$maxDeep=8;
				while (array_key_exists('parent',$value)&&array_key_exists($parent=$value['parent'],$this->browscapIni)&&(--$maxDeep>0))
					$cap+=($value=$this->browscapIni[$parent]);
				break;
			}
		}
		if (!$cache) $this->browscapIni=null;
		return $return_array ? $cap : (object)$cap;
	}


}