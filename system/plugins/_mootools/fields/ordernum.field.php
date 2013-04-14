<?php
/**
 * Field definition for the order number. This is a built-in field which should not be used explicitly.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/integer.field.php');

class zajfield_ordernum extends zajfield_integer {
	// exactly the same as integer except duplication

	/**
	 * Duplicates the data when duplicate() is called on a model object.
	 * @param $data mixed The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return mixed Returns the duplicated value.
	 **/
	public function duplicate($data, &$object){
		// Ordernum for this object should not be the same as the duplicated object, instead just use what you would for a new object - max+1
			$maxnum = $this->zajlib->db->max($object->class_name, $this->name);
		return $maxnum+1;
	}
}