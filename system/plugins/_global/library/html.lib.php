<?php
/**
 * This library is intended for generating HTML content. It is depricated and should not be used.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 * @depricated
 **/

class zajlib_html extends zajLibExtension {
	
	// array can be:	- a single-dimensional array ("value/key" is used in select)
	//					- a multi-dimensional array ("first element of second level / key of first level" is used in select)
	//					- an array of zajModel objects ("name / id" is used in select)
	// values_equal_desc overrides the key and makes the value equal to the displayed name of each option
	
	function radio($name, $array, $selected_element='', $extra_tag_parameters='', $values_equal_desc=false){
		$html_code = "<div id='$name-radiocontainer' class='radiocontainer'>";
		if(count($array) > 0){
		  foreach($array as $value => $desc){
			// if it is an array of objects that has been passed take name and id
				if(is_object($desc)){
					$value = $desc->id;
					$desc = $desc->name;
				}
			// if it is an array of arrays that has been passed take first element
				if(is_array($desc)) $desc = array_shift($desc);
			// if values_equal_desc is set to true
				if($values_equal_desc) $value = $desc;
			// if the current value is the selected element
				if($value == $selected_element) $selected = "checked";
				else $selected = "";
			// add special characters
				$value = htmlspecialchars($value, ENT_QUOTES);
				$html_code .= "<input type='radio' name='$name' id='{$name}[{$value}]' $selected value=\"$value\"> <span style='cursor:pointer;' onclick=\"$('{$name}[{$value}]').checked=true; $('{$name}[{$value}]').fireEvent('change');\">$desc</span><br>";
		  }
		}
		$html_code .= "</div>";
		return $html_code;		
	}


	function select($name, $array, $selected_element='', $extra_tag_parameters='', $values_equal_desc=false){
		$html_code = "<select name='$name' id='$name' $extra_tag_parameters>";
		// TODO: make this more effecient!
		// this is a zajFetcher object, so display the elements
		if(is_object($array)){
			foreach($array as $el) $new_array[$el->id] = $el->name;
			$array = $new_array;
		}
		
		if(is_array($array)){
		  foreach($array as $value => $desc){
			// if it is an array of objects that has been passed take name and id
				if(is_object($desc)){
					$value = $desc->id;
					$desc = $desc->name;
				}
				if(is_object($selected_element)) $selected_element = $selected_element->id;
			// if it is an array of arrays that has been passed take first element
				if(is_array($desc)) $desc = array_shift($desc);
			// if values_equal_desc is set to true
				if($values_equal_desc) $value = $desc;
			// if the current value is the selected element
				if($value == $selected_element) $selected = "selected";
				else $selected = "";
			// add special characters
				$value = htmlspecialchars($value, ENT_QUOTES);
				$html_code .= "<option $selected value=\"$value\">$desc</option>";
		  }
		}
		$html_code .= "</select>";
		return $html_code;		
	}
}


	
?>