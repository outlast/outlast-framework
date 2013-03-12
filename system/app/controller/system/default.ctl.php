<?php
/**
 * This system controller handles various callbacks.
 * @package Controller
 * @subpackage BuiltinControllers
 * @todo Review the security issues for these methods.
 * @todo Disable direct access to data folder (only through PHP).
 * @todo Fix error messages to english and/or global via lang files.
 **/
	
	class zajapp_system extends zajController{
		
		/**
		 * Load method is called each time any system action is executed.
		 * @todo Allow a complete disabling of this controller. 
		 **/
		function __load(){
			// Add disable-check here!
		}
		
		/**
		 * Enable automatic file and photo uploads.
		 **/
		/**function upload_photo(){
			// capture the file
				$photofile = file_get_contents("php://input");
			// create photo file and return object
				$photo = Photo::create_from_stream();
			// if error, then return the problem
				if($photo === false) $return = array ('status'=>'error');
			// all ok, so get my thumbnail and id
				else{
					$return = array(
						'status'=>'ok',
						'id'=>$photo->id,
						'thumb'=>$photo->thumb,
					);
				}
			$this->zajlib->json(json_encode((object) $return));
			exit();
		}**/
		
		
		/**
		 * Enable file uploads.
		 **/
		/**function upload_file(){
			//print "<textarea>".json_encode((object) array('file'=>'uploaded/PIC01.jpg'))."</textarea>";
			$this->zajlib->json((object) array('file'=>'uploaded/PIC01.jpg'));
			//print "file=uploaded/PIC01.jpg,name=PIC01.jpg,width=320,height=240,type=jpg,error=Not recognized file type";
			exit;
		}**/
	
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
			
		/**
		 * Photo method handles photo uploads from zajphotoadmin.js 
		 **/
		/*function upload_photo(){
			// prepare results array
				$result = array();
				$result['time'] = @date('r');
				$result['addr'] = substr_replace(gethostbyaddr($_SERVER['REMOTE_ADDR']), '******', 0, 6);
				$result['agent'] = $_SERVER['HTTP_USER_AGENT'];
			// prepare request
				if (count($_GET)) $result['get'] = $_GET;
				if (count($_POST)) $result['post'] = $_POST;
				if (count($_FILES)) $result['files'] = $_FILES;
			
			// log the file uploads
				//$this->zajlib->load->library('log');
				//$this->zajlib->log->file('uploading file $_FILES');
			
			// get file data
				$filedata = $result['files']['Filedata'];
			
			// validation
				$error = false;
				if (!isset($filedata) || !is_uploaded_file($filedata['tmp_name'])) $error = 'Ismeretlen hiba történt. Próbáld újra!';
				if (!$error && $filedata['size'] > 2 * 1024 * 1024) $error = 'A feltöltött kép mérete meghaladja a maximális méretet (2 MB)!';
				if (!$error && !($size = @getimagesize($filedata['tmp_name']) )) $error = 'Csak a kép fájlok feltöltése engedélyezett!';
				if (!$error && !in_array($size[2], array(1, 2, 3, 7, 8) ) ) $error = 'Csak JPEG, GIF vagy PNG fájlt tölts fel!';
				if (!$error && ($size[0] < 50) || ($size[1] < 50)) $error = 'A fotó minimális mérete: 50x50px.';
				// TODO: validate actual file extension!
				
							
			// generate result			
				if ($error) $return = array('status' => '0','error' => $error);
				else {
					// load file lib
						$this->zajlib->load->library('file');
						$return = array('status' => '1','name' => $filedata['name']);
					// get extension
						$myextension = $this->zajlib->file->get_extension($filedata['name']);
						$filename = uniqid("")."-".time().".".$myextension;
						$newpath = $this->zajlib->basepath."/data/uploads/".$filename;
						$newurl = $this->zajlib->baseurl."/data/uploads/".$filename;
					// make sure the new path exists
						$this->zajlib->file->create_path_for($newpath);
					// move the uploaded file
						@move_uploaded_file($filedata['tmp_name'], $newpath);
					// not sure if needed, but for added safety, ensure that execution is off for EVERYONE
						//@chmod($newpath, 0664);
					// file is an image, so get info
						$info = @getimagesize($newpath);
						$return['width'] = $info[0];
						$return['height'] = $info[1];
						$return['mime'] = $info['mime'];
						$return['origname'] = $filedata['name'];
						$return['fileurl'] = $newurl;
					// now create photo object
						$pobj = Photo::create();
						$pobj->set_image($filename);		// sets and saves image
						//$pobj->save();
						$return['fileurl'] = $pobj->thumb;
						$return['id'] = $pobj->id;
					// delete original
						if($pobj->exists) unlink($newpath);
				}
			
			// print result				
				header('Content-type: application/json');
				echo json_encode($return);
				exit;
		}*/


		/**
		 * This method handles file uploads.
		 * @todo Temporarily disabled due to security issues.
		 **/
		/*function upload_file(){
			exit("File uploading is disabled.");
			// prepare results array
				$result = array();
				$result['time'] = @date('r');
				$result['addr'] = substr_replace(gethostbyaddr($_SERVER['REMOTE_ADDR']), '******', 0, 6);
				$result['agent'] = $_SERVER['HTTP_USER_AGENT'];
			// prepare request
				if (count($_GET)) $result['get'] = $_GET;
				if (count($_POST)) $result['post'] = $_POST;
				if (count($_FILES)) $result['files'] = $_FILES;
			
			// log the file uploads
				//$this->zajlib->load->library('log');
				//$this->zajlib->log->file('uploading file $_FILES');
			
			// get file data
				$filedata = $result['files']['Filedata'];
			
			// validation
				$error = false;
				if (!isset($filedata) || !is_uploaded_file($filedata['tmp_name'])) $error = 'error uploading!';
				if (!$error && $filedata['size'] > 10 * 1024 * 1024) $error = 'please upload only files smaller than 10Mb!';
				// TODO: add whitelist and blacklist - project-specific setting!
							
			// generate result
				if ($error) $return = array('status' => '0','error' => $error);
				else {
					// load file lib
						$this->zajlib->load->library('file');
						$return = array('status' => '1','uploadname' => $filedata['name']);
					// get extension
						$myextension = $this->zajlib->file->get_extension($filedata['name']);
					// TODO: add support for Fileinfo PECL extension for more accurate MIME detection
						$mimetype = $this->zajlib->file->get_mime_type($filedata['name']);
						$filename = $filedata['name'];
					// create file object
						$fobj = File::create();
						$fobj->set('mime',$mimetype);
						$fobj->set('name',$filename);
						$fobj->setfile($filedata['tmp_name']);
						$fobj->save();
					// return file info
						$return['mime'] = $fobj->data->mime;
						$return['name'] = $fobj->data->name;
						$return['size'] = $fobj->data->size;
						$return['id'] = $fobj->id;
				}

			// print result				
				header('Content-type: application/json');
				echo json_encode($return);
				exit;
		}*/


		/**
		 * Search for a relationship.
		 **/
		function search_relation(){		 	
		 	// strip all non-alphanumeric characters
		 		$class_name = ucfirst(strtolower(preg_replace('/\W/',"",$_REQUEST['class'])));
		 		$field_name = preg_replace('/\W/',"",$_REQUEST['field']);
		 		if(!empty($_REQUEST['type'])) $type = preg_replace('/\W/',"",$_REQUEST['type']);
		 		else $type = 'default';
		 	
		 	// is it a valid model?
		 		if(!is_subclass_of($class_name, "zajModel")) return $this->zajlib->error("Cannot search model '$class_name': not a zajModel!");
			// can the user search for relations on this model?
		 		// something like $class_name::__relation_search($field_name, $query);
		 	// now what is my field connected to?
		 		$my_model = $class_name::__model();
		 		$other_model = reset($my_model->{$field_name}->options);
		 		if(empty($other_model))  $this->zajlib->error("Cannot connect to field '$field_name' because it is not defined as a relation or its relation model has not been defined!");		 		
		 	// first fetch all
		 		$this->zajlib->variable->relations = $other_model::fetch();
		 	// filter by search query (if any)
				if(!empty($_REQUEST['query'])) $this->zajlib->variable->relations->search('%'.$_REQUEST['query'].'%', false)->limit(10);
			// now send this to the magic method
				$this->zajlib->variable->relations = $other_model::fire_static('onSearch', array($this->zajlib->variable->relations, $type));
			// error?
				if(!is_object($this->zajlib->variable->relations)){
					if($this->zajlib->variable->relations === false) $this->zajlib->variable->relations = "You must explicitly define the __onSearch() event method in class '$class_name' to enable autocomplete.";
					return $this->zajlib->json(json_encode(array(0=>array('error', $this->zajlib->variable->relations))));
				}
		 	// now output to relations json
				$my_relations = array((object) array('id'=>'', 'name'=>'-none-'));
				foreach($this->zajlib->variable->relations as $rel){
					$my_relations[] = (object) array('id'=>$rel->id, 'name'=>$rel->name);
				}
			// if dojo output requested, show that
				if(!empty($_REQUEST['dojo'])){
					$my_relations = array(
						'identifier' => 'id',
						'label' => 'name',
						'items' => $my_relations
					);
				}
			// now return the json-encoded object	
				return $this->zajlib->json(json_encode((object) $my_relations));
		}	
		
		/**
		 * Logs javascript errors to a file (if enabled)
		 **/
		function javascript_error(){
			// Check if logging is enabled
				if(empty(zajLib::me()->zajconf['jserror_log_enabled']) || empty(zajLib::me()->zajconf['jserror_log_file'])) return $this->zajlib->ajax('not logged');
			// Defaults
				if(empty($_REQUEST['line'])) $_REQUEST['line'] = 0;
				if(empty($_SERVER['HTTP_USER_AGENT'])) $_SERVER['HTTP_USER_AGENT'] = "";
			// Intro
				$intro = 'Javascript error @ '.date('Y.m.d. H:i:s').' ('.$_SERVER['REMOTE_ADDR'].' | '.$_SERVER['HTTP_USER_AGENT'].')';
			// Now write to file
				$errordata = "\n".$_REQUEST['message'].' in file '.$_REQUEST['url'].' on line '.$_REQUEST['line'];
				$errordata .= "\nPage: ".$_REQUEST['location']."\n\n";
			// Now write to javascript error log
				file_put_contents(zajLib::me()->zajconf['jserror_log_file'], $intro.$errordata, FILE_APPEND);
			// Return ok
				return $this->zajlib->ajax('logged');
		}		

	}
	

?>