<?php
/**
 * Field definition for dates.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/time.field.php');

class zajfield_date extends zajfield_time {
	// similar to time
	
	// save is different though
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_filter = false;			// boolean - true if fetcher needs to be modified
	const search_field = false;		// boolean - true if this field is used during search()

	/**
	 * Check to see if input data is valid.
	 * @param $input The input data.
	 * @return boolean Returns true if validation was successful, false otherwise.
	 **/
	public function validation($input){
		return true;
	}
	
	/**
	 * Preprocess the data before returning the data from the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return Return the data that should be in the variable.
	 **/
	public function get($data, &$object){
		return $data;
	}
	
	/**
	 * Preprocess the data before saving to the database.
	 * @param $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 * @todo Remove display/format version
	 **/
	public function save($data, &$object){
		if(is_array($data)){
			// date[format] and date[display] (backwards compatible
			if(!empty($data['format'])){
				$dt = date_create_from_format($data['format'], $data['display']);
				if(is_object($dt)){
					$tz = date_default_timezone_get();
					$dt->setTimezone(new DateTimeZone($tz));
					$dt->setTime(0, 0);
					$data = $dt->getTimestamp();
				}
				else $data = '';
			}
			else{
				$data = $data['value'];
			}
		}
		return array($data, $data);
	}

}


?>