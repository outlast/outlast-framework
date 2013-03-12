<?php
/**
 * Field definition for the name field. This is a special built-in field which is essentially the same as a standard text field. The only difference is that the name field is automatically cached and can be retrieved without a separate database query.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/text.field.php');

class zajfield_name extends zajfield_text {
	// exactly the same as text, but override the save method
	const use_save = true;			// boolean - true if preprocessing required before saving data
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajObject $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object){
		// modify the object name
			$object->name = $data;
		// now return
			return array($data, $data);	
	}
	
}


?>