<?php
/**
 * Various methods inteded to help handling arrays.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_array extends zajLibExtension{


	/**
	 * This method will sort array values after striping words like "The" and removing accented characters.
	 * @param array $array The array to be sorted.
	 * @return array Returns the sorted array
	 */
	public function sort_me($array){
		@uasort($array, "sort_me_comp");
		return $array;
	}
	private function sort_me_comp($a, $b){
		// strip both elements
		$this->zajlib->load->library("lang");
		$a = $this->zajlib->lang->convertEng($a);
		$a = $this->zajlib->text->stripPreWords($a);
		$b = $this->zajlib->lang->convertEng($b);
		$b = $this->zajlib->text->stripPreWords($b);
		// now compare
		if($a == $b) return 0;
		return ( $a < $b ) ? -1 : 1;
	}

	/**
	 * Merges two array without a notice (even if one of the parameters is not an array). Otherwise it is the same as the built-in php function array_merge().
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @return array Returns the merged array
	 * @todo Is this needed for final veresion?
	 */
	function array_merge($array1, $array2){
		if(is_array($array1) && is_array($array2)) return array_merge($array1, $array2);
		if(is_array($array1)) return $array1;
		if(is_array($array2)) return $array2;
		return false;
	}
	
	/**
	 * This generates php code for a given array.
	 * @param array $array The array values to use when generating the code.
	 * @return string The php code required to generate this array.
	 * @todo Is this needed for final veresion?
	 **/
	function array_to_php($array){
		$php_code = 'array(';
			if(is_array($array)){
				foreach($array as $key=>$value){
					// generate key
						if(!is_integer($key)) $key = '"'.str_ireplace('"', '\"', $key).'"';
					// generage value
						if(is_array($value)) $php_code .= $key.'=>'.$this->array_to_php($value);
						elseif(is_numeric($value)) $php_code .= $key.'=>'.$value;
						else $php_code .= $key.'=>"'.str_ireplace('"', '\"', $value).'"';
						$php_code .= ',';
				}
			}
			$php_code .= ')';	
		return $php_code;
	}


	/**
	 * Recursively typecasts an array to an object.
	 * @param array $array The array values to use when generating the code.
	 * @return Object The array as an object.
	 * @todo Test and remove invalid keys (if needed) - for example ones with . in it...
	 **/
	function array_to_object($array){
		if(!is_array($array) && !is_object($array)){
			$this->zajlib->warning("The parameter passed to array_to_object is not an array or object!");
			return $array;
		}
		foreach($array as $key => $element){
			if(is_array($element)) $array[$key] = $this->array_to_object($element);
		}
		return (object) $array;
	}

}

?>