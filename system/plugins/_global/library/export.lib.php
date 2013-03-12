<?php
/**
 * Library helps you export data from Mozajik models, database queries, or an array of standard PHP objects.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/
		
class zajlib_export extends zajLibExtension {
		
		/**
		 * Export model data as csv.
		 * @param zajFetcher|zajDb|array $fetcherOrArray A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array $fields A list of fields from the model which should be included in the export.
		 * @param string $file_name The name of the file which will be used during download.
		 * @param boolean $excel_encoding If set to true, it will download in CSV format which Excel can recognize.
		 * @param string $delimiter The separator for the CSV data. Defaults to comma, unless you set excel_encoding...then it defaults to semi-colon.
		 * @return Print the csv.
		 **/
		public function csv($fetcher, $fields = false, $file_name='export.csv', $excel_encoding = false, $delimiter = false){
			// Show template
				// Standard CSV or Excel header?
					if($excel_encoding){
						header("Content-Type: application/vnd.ms-excel; charset=UTF-16LE");
						header("Content-Disposition: attachment; filename=\"$file_name\"");
						if(!$delimiter) $delimiter = ';';
  					}
					else{
						header("Content-Type: text/csv; charset=UTF-8");
						header("Content-Disposition: attachment; filename=\"$file_name\"");
						if(!$delimiter) $delimiter = ',';
					}
				// Create output
					$outstream = fopen("php://output", 'w');
				// Now write data
					$this->send_data($outstream, $fetcher, $fields, $excel_encoding, $delimiter);
				exit;
		}

		/**
		 * Export model data as excel. It should be noted that CSV export is much less memory and processor intensive, so for large exports we recommend that.
		 * @require Requires the Spreadsheet_Excel_Writer PEAR module.
		 * @param zajFetcher|zajDb|array $fetcherOrArray A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array $fields A list of fields from the model which should be included in the export.
		 * @param string $file_name The name of the file which will be used during download.
		 * @return Sends to download of excel file.
		 **/
		public function xls($fetcher, $fields = false, $file_name='export.xls'){
			// Require it if it is available
				@include_once('Spreadsheet/Excel/Writer.php');
				if(!class_exists('Spreadsheet_Excel_Writer', false)) return $this->zajlib->error("PEAR module Spreadsheet_Excel_Writer not installed!");
			
			// Create the excel file			
				$workbook = new Spreadsheet_Excel_Writer();
				$workbook->setVersion(8);
				
			// Write output
				$this->send_data($workbook, $fetcher, $fields);
			
			// Send output
				$workbook->send($file_name);
				$workbook->close();
			exit;
		}

		/**
		 * Write data to an output.
		 * @param file|Spreadsheet_Excel_Writer $output The output object or handle.
		 * @param zajFetcher|zajDb|array $fetcherOrArray A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array $fields A list of fields from the model which should be included in the export.
		 * @param boolean $excel_encoding If set to true, it will download in CSV format which Excel can recognize.
		 * @param string $delimiter The separator for the CSV data. Defaults to comma, unless you set excel_encoding...then it defaults to semi-colon.
		 * @return integer Returns the number of rows written.
		 **/
		private function send_data($output, $fetcher, $fields, $excel_encoding=false, $delimiter=false){
			// Get fields of fetcher class if fields not passed
				if(is_a($fetcher, 'zajFetcher') && (!$fields && !is_array($fields))){
					$class_name = $fetcher->class_name;
					$my_fields = $class_name::__model();
					foreach($my_fields as $field=>$val) $fields[] = $field;
				}
			// Get fields of db object if fields not passed (the property names of the object)
				if(!is_a($fetcher, 'zajFetcher') && (!$fields && !is_array($fields))){
					// Get the first row and create $fields[] array from it
						if(is_a($fetcher, 'Iterator')) $my_fields = $fetcher->rewind();
						else $my_fields = reset($fetcher);
					// Make sure that it is an object or array
						if(!is_array($my_fields) && !is_object($my_fields)) return $this->zajlib->error("Tried exporting data but failed. Input data must be an array of objects or an object.");
					foreach($my_fields as $field=>$val) $fields[] = $field;
				}
			// Prepare if XLS
				if(is_a($output, 'Spreadsheet_Excel_Writer')){
					// Bold format
						$format_bold = $output->addFormat();
						$format_bold->setBold();
					// Add a worksheet 
						$worksheet = $output->addWorksheet('Export');
						$worksheet->setInputEncoding('utf-8');
				}			
			
			// Run through all of my rows
				$linecount = 0;
				foreach($fetcher as $s){
					// Create row data
						$data = array();
					// Is this a model or an array?
						if(is_a($s, 'zajModel')) $model_mode = true;
						else $model_mode = false;
					// Add first default value (only if model_mode)
						if($model_mode) $data['name'] = $s->name;
					// Convert encoding if excel mode selected
						if($excel_encoding) $data['name'] = mb_convert_encoding($data['name'], 'UTF-16LE', 'UTF-8');
					
						
					// Add my values for each field
						foreach($fields as $type => $field){
							// Set to value
								if($model_mode) $field_value = $s->data->$field;
								else{
									// Either an array or an object
									if(is_array($s)) $field_value = $s[$field];
									else $field_value = $s->$field;
								}
							
							// Relationship field support (for manytoone only)
								if(is_object($field_value) && is_a($field_value, 'zajModel')){
									$data[$field] = $field_value->name;
								}
							// Relationship field support (for manytomany and onetomany)
								elseif(is_object($field_value) && is_a($field_value, 'zajFetcher')){
									$data[$field] = $field_value->total.' items';
								}
							// See if field value is an array
								elseif(is_array($field_value) || (is_object($field_value) && is_a($field_value, 'stdClass'))){
									foreach($field_value as $key=>$value) $data[$field.'_'.$key] = $value;
								}								
							// Time or date field
								elseif(is_string($type) && $type == 'time' && is_numeric($field_value)) $data[$field] = date("D M j G:i:s T Y", $field_value);
								elseif(is_string($type) && $type == 'date' && is_numeric($field_value)) $data[$field] = date("D M j Y", $field_value);
							// Standard field
								else $data[$field] = $field_value;
							// Convert encoding if excel mode selected
								if($excel_encoding) $data[$field] = mb_convert_encoding($data[$field], 'UTF-16LE', 'UTF-8');
						}
					// Add default values (only if model_mode)
						if($model_mode){						
							$data['ordernum'] = $s->data->ordernum;
							$data['time_create'] = date("D M j G:i:s T Y", $s->data->time_create);
							$data['id'] = $s->data->id;
						}
					// If firstline, display fields
						if($linecount == 0){
							// Write XLS
								if(is_a($output, 'Spreadsheet_Excel_Writer')){
									// Bold format
										$format_bold =& $output->addFormat();
										$format_bold->setBold();
									// Write names
										$col = 0;
										foreach(array_keys($data) as $field_name){
											$worksheet->write(0, $col++, $field_name, $format_bold);
										}
								}
							// Write standard CSV
								else fputcsv($output, array_keys($data), $delimiter);
							$linecount++;
						}
					// Display values
						// Write XLS
						if(is_a($output, 'Spreadsheet_Excel_Writer')){
							// Write values
								$col = 0;
								foreach($data as $field_val) $worksheet->write($linecount, $col++, $field_val);
						}
						// Write standard CSV
						else fputcsv($output, $data, $delimiter);
					// Add to linecount
						$linecount++;
				}
		}
	
		/**
		 * Send an array of form responses to a Google Docs spreadsheet form. You must make the sheet publicly visible and create a form from it.
		 * @param string $formkey The form key. You can get this data from the form's public URL query string.
		 * @param array $responses An key/value array of responses which to record. You must use the same key as the name of the field in the form (use 'inspect element' feature in Chrome on the Google Docs form to check).
		 * @param string $ok_text This text is searched for when we try to determine if the save was successful. You only need to change this if a custom confirmation message is set in Google Docs.
		 * @return boolean Returns true if successful and false if fails. It also throws a warning if it failed.
		 **/
		public function gdocs_form($formkey, $responses, $ok_text = "Your response has been recorded."){
			// Build entries list
				$query = '';
				foreach($responses as $key=>$val) $query .= '&'.urlencode($key).'='.urlencode($val);
			// Send the data
				$response = file_get_contents("https://docs.google.com/a/zajmedia.com/spreadsheet/formResponse?formkey=".$formkey."&embedded=true&ifq&pageNumber=0&submit=Submit".$query);
			// If $ok_text is contained in the response then all is ok.
				if(strstr($response, $ok_text) !== false) return true;
				else{
					$this->zajlib->warning('Unable to save responses to Google Forms: '.$query);
					return false;
				}
		}

}