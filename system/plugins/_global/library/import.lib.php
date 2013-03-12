<?php
/**
 * Library helps you import data into Mozajik models, arrays, or other objects.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_import extends zajLibExtension {

	/**
	 * Reads a tab of a published Google Document in CSV format returns an array of objects. In order to use this you must use the File / Publish to the web... feature. Also, check Automatically republish changes to make sure it stays in sync.
	 * @param string $url A CSV-formatted url that is displayed in the Publish to the web... feature of Google docs.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @param string $delimiter Set the field delimiter (one character only).
	 * @param string $enclosure Set the field enclosure character (one character only).
	 * @param string $escape Set the escape character (one character only). Defaults as a backslash (\).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 **/
	public function gdocs_spreadsheet($url, $first_row_is_header = true, $delimiter = ',', $enclosure = '"', $escape = '\\'){
		return $this->csv($url, $first_row_is_header, $delimiter, $enclosure, $escape);
	}

	/**
	 * Reads a CSV document and returns an array of objects.
	 * @param string $urlORfile A CSV-formatted file (relative to basepath) or URL.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @param string $delimiter Set the field delimiter (one character only).
	 * @param string $enclosure Set the field enclosure character (one character only).
	 * @param string $escape Set the escape character (one character only). Defaults as a backslash (\).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 **/
	public function csv($url, $first_row_is_header = true, $delimiter = ',', $enclosure = '"', $escape = '\\'){
		// If it is not a url, then check sandbox
			if(!$this->zajlib->url->is_url($url)) $this->zajlib->sandbox->check($url);
		// Open the url
			$return_data = array();
			if (($handle = fopen($url, "r")) !== FALSE) {
				// Use first row as header?
					if($first_row_is_header) $first_row = fgetcsv($handle);
				// Now while not feof add a row to object
					while(!feof($handle)){
						$current_data = array();
						$current_row = fgetcsv($handle);
						foreach($current_row as $key => $value){
							if($first_row_is_header) $current_data[$first_row[$key]] = $value;
							else $current_data[$key] = $value;
						}
						$return_data[] = (object) $current_data;
					}
			}
			else return $this->zajlib->warning("Could not open CSV for importing: $url");
		// Now return my data
			return $return_data;
	}

}