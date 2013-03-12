<?php
$GLOBALS['rating_scale'] = 5;

/**
 * A built-in model to handle ratings.
 *
 * You should not directly use this model unless you are developing extensions.
 *
 * @package Model
 * @subpackage BuiltinModels
 * @todo This needs to be removed.
 */
class Rating extends zajModel {
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$fields->parent = zajDb::text();
			$fields->rating = zajDb::integer();
		// do not modify the line below!
			$fields = parent::__model(__CLASS__, $fields); return $fields;
	}
	///////////////////////////////////////////////////////////////
	// !Construction and other required methods
	///////////////////////////////////////////////////////////////
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Model methods
	///////////////////////////////////////////////////////////////
	public static function get_rating($parent){
		$parentSQL = addslashes($parent);
		$this->zajlib->db->query("SELECT COUNT(*) as count, AVG(rating) as average WHERE `parent`='$parentSQL' && `status`!='deleted'");
		return $this->zajlib->db->get_one();
	}
}