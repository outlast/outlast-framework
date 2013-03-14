<?php
/**
 * Various methods inteded to help handling arrays.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_array extends zajLibExtension{


	/**
	 * Sort array values after striping words like "The" and removing accented characters.
	 * @param array $array The array to be sorted.
	 * @return array Returns the sorted array
	 */
	public function sort($array){
		@uasort($array, array($this, 'sort_me_comp'));
		return $array;
	}
	/**
	 * @ignore
	 */
	private function sort_me_comp($a, $b){
		// strip both elements
		$this->zajlib->load->library("lang");
		$a = $this->zajlib->lang->convert_eng($a);
		$a = $this->zajlib->text->strip_pre_words($a);
		$b = $this->zajlib->lang->convert_eng($b);
		$b = $this->zajlib->text->strip_pre_words($b);
		// now compare
		if($a == $b) return 0;
		return ( $a < $b ) ? -1 : 1;
	}
	/**
	 * @ignore
	 * @depricated
	 */
	public function sort_me($array){ $this->sort($array); }

	/**
	 * Merges two array without a notice. This is similar to PHP's built-in array_merge but will not fail if one or the other parameters is not an array.
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @return array Returns the merged array. If one or the other is not an array, only the array is returned. If neither are arrays an empty array is returned.
	 */
	public function merge($array1, $array2){
		if(is_array($array1) && is_array($array2)) return array_merge($array1, $array2);
		if(is_array($array1)) return $array1;
		if(is_array($array2)) return $array2;
		return array();
	}
	/**
	 * @ignore
	 * @depricated
	 */
	public function array_merge($array1, $array2){ return $this->merge($array1,$array2); }
	
	/**
	 * This generates php code for a given array.
	 * @param array $array The array values to use when generating the code.
	 * @return string The php code required to generate this array.
	 **/
	public function to_code($array){
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
	 * @ignore
	 * @depricated
	 */
	public function array_to_php($array){ return $this->to_code($array); }

	/**
	 * Recursively typecasts an array to an object.
	 * @param array $array The array values to use when generating the code.
	 * @return Object The array as an object.
	 * @todo Test and remove invalid keys (if needed) - for example ones with . in it...
	 **/
	public function to_object($array){
		if(!is_array($array) && !is_object($array)){
			$this->zajlib->warning("The parameter passed to array_to_object is not an array or object!");
			return $array;
		}
		foreach($array as $key => $element){
			if(is_array($element)) $array[$key] = $this->array_to_object($element);
		}
		return (object) $array;
	}
	/**
 	 * @ignore
	 * @depricated
	 */
	public function array_to_object($array){ return $this->to_object($array); }

}

?>