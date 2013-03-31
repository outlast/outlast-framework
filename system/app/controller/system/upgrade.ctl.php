<?php
	/**
	 * This controller enables system upgrades.
	 * @package Controller
	 * @subpackage BuiltinControllers
	 **/

	// Set default configuration options

	class zajapp_system_upgrade extends zajController{

		/**
		 * Load method is called each time any system action is executed.
		 **/
		public function __load(){
		}

		/**
		 * Main method checks for necessary upgrades and performs any actions.
		 **/
		public function main(){
			// Let's check to see if any old photo paths
				if(Photo::fetch()->filter('timepath', false)->total > 0) $this->photos_to_time();
		}


		public function photos_to_time(){
			$count = $error = 0;
			// Get all non-timepathed photos
				$oldphotos = Photo::fetch()->filter('status', 'saved')->limit(5000);
			// By default only non-converted photos are copied. But with ?force=true you can also force copy.
				if(empty($_GET['force'])) $oldphotos->filter('timepath', false);
				foreach($oldphotos as $op){
					foreach($GLOBALS['photosizes'] as $key=>$size){
						// If size is valid!
						if($size){
							$rel = 'rel_'.$key;
							// Make sure timepath is false to get the correct FROM path
							$op->timepath = false;
							$from_path = $this->zajlib->basepath.$op->$rel;
							$to_path = $this->zajlib->file->get_time_path($this->zajlib->basepath."data/Photo", $op->id.'-'.$key.'.'.$op->extension, $op->time_create, true);
							if(!file_exists($from_path)){
								print "Error copying file $from_path (file not found).<br/>";
								//$op->set('status', 'uploaded')->save();
								$error++;
							}
							else{
								copy($from_path, $to_path);
								$op->set('timepath', true)->save();
								$count++;
							}
						}
					}
				}
			print "Finished copying $count files ($error errors).<br/>";
		}
	}