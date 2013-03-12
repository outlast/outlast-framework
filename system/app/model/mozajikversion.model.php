<?php
/**
 * MozajikVersion is a model which stores version information and assists in upgrades.
 *
 * @package Model
 * @subpackage BuiltinModels
 **/

class MozajikVersion extends zajModel {
	/**
	 * Major version count
	 * @var integer
	 **/
	static $major = 3;			// major version count
	/**
	 * Minor version count
	 * @var integer
	 **/
	static $minor = 0;			// minor version count
	/**
	 * Build count (based on svn repo revision)
	 * @var integer
	 **/
	static $build = 3003;		// build - based on svn repo revision
	/**
	 * Beta status
	 * @var boolean
	 **/
	static $beta = true;		// true if this release is a beta
		

	/**
	 * Model definition
	 */
	static function __model(){
		// define custom database fields
			$f->major = zajDb::integer();
			$f->minor = zajDb::integer();
			$f->build = zajDb::integer();
			$f->beta = zajDb::boolean();
			$f->installed = zajDb::boolean();
		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
	}

	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }
	
	/**
	 * Install current version
	 **/
	public static function install(){
		// If database enable
		if(zajLib::me()->zajconf['mysql_enabled']){
			// set all installed to false
				zajLib::me()->db->query("UPDATE `mozajikversion` SET `installed`='';");
			// now create the new version
				$new_installation = MozajikVersion::create();
				$new_installation->set('major', MozajikVersion::$major);
				$new_installation->set('minor', MozajikVersion::$minor);
				$new_installation->set('build', MozajikVersion::$build);
				$new_installation->set('beta', MozajikVersion::$beta);
				$new_installation->set('installed', true);
				$new_installation->save();
		}
		// now create my install.dat
			$install_array = array(
				'major'=>MozajikVersion::$major,
				'minor'=>MozajikVersion::$minor,
				'build'=>MozajikVersion::$build,
				'beta'=>MozajikVersion::$beta,
			);
		// save to install.dat
			$install_object = (object) $install_array;
			file_put_contents(zajLib::me()->basepath.'cache/install.dat', serialize($install_object));
		return $install_object;
	}

	/**
	 * Check current version and return appropriate value
	 * @return Returns negative if db is too new, zero if db is too old and upgrade of db required, and positive (true) if all is good.
	 **/
	public static function check(){
		// if no installation or db is too old return 0
			if(!is_object(zajLib::me()->mozajik) || MozajikVersion::$major > zajLib::me()->mozajik->major || MozajikVersion::$minor > zajLib::me()->mozajik->minor || MozajikVersion::$build > zajLib::me()->mozajik->build) return 0;
		// if db is too new (less likely) return 1
			if(MozajikVersion::$major < zajLib::me()->mozajik->major || MozajikVersion::$minor < zajLib::me()->mozajik->minor || MozajikVersion::$build < zajLib::me()->mozajik->build) return -1;
		// all is good
			return 1;
	}

}