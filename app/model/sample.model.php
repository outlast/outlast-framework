<?php
/**
 * This is just a sample model file. You can (and should) delete this once you start developing your app.
 * @package Model
 * @subpackage Example
 */


class Sample extends zajModel {
	
	/**
	 * __model function. creates the database fields available for objects of this class.
	 * 
	 */
	public static function __model(){
		/////////////////////////////////////////
		// begin custom fields definition:
			$f = (object) array();
			$f->name = zajDb::name();
			$f->description = zajDb::text();
			$f->photos = zajDb::photos();
		// end of custom fields definition
		/////////////////////////////////////////		

		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
	}

	/**
	 * Contruction and static calling methods. These are required and not to be modified!
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }
	
	
	/**
	 * This method is called after the object is fetched from the database. You will want to use this for caching.
	 **/
	public function __afterFetch(){
		// The following code will cache the description of this object
			$this->description = $this->data->description;
		// name and id are cached automatically, so they are available as $this->name and $this->id

		// Fields you do not cache can be accessed via the $this->data->fieldname property.
	}
	
	// For additional available methods, see documentation on model methods.

}
?>
