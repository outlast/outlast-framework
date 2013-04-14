<?php
/**
 * Field definition tinymce richtext areas. This is basically an alias of textarea, but with a different control associated with it.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/textarea.field.php');

class zajfield_tinymce extends zajfield_textarea {
	const edit_template = 'field/tinymce.field.html';	// string - the edit template, false if not used
	const show_template = false;						// string - used on displaying the data via the appropriate tag (n/a)

	// alias of textarea
}