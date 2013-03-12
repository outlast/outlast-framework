<?php
/**
 * A length of time in hours and minutes.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/time.field.php');

class zajfield_duration extends zajfield_time {

	// Same as time
	
	// Only the controller differs
	const edit_template = 'field/duration.field.html';	// string - the edit template, false if not used

}


?>