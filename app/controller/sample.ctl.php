<?php
/**
 * This is another sample controller to demonstrate how controller requests are handled by Mozajik. This file is for demo only and is safe to delete (and should be deleted).
 *  Notice that the app class is named zajapp_sample which correspons to the controller's file name!
 * @package Controller
 * @subpackage Example
 **/

	class zajapp_sample extends zajController{
		/**
		 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
		 *		related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
		 *		any admin pages will need to login first...
		 **/
		public function __load(){
			// This is set any time any request is made to this controller (so every time we call /sample/anything/ or even just /sample/)
				$this->zajlib->variable->my_own_template_variable = "This is from the Sample controller!";
		}
		
		/**
		 * The main() method is the default for any controller.
		 **/
		public function main(){
			// Again, like in the default, we show the welcome, but this time the __load() method set my_own_template_variable
				$this->zajlib->template->show('welcome.html');
		}


		/**
		 * This is another method. The name is important, because this determines which requests go here. So this will process /sample/try/this/
		 **/
		public function try_this(){
			// Let's set another template variable
				$this->zajlib->variable->another_variable = "You have also successfully requested the try_this method!";
			// This time let's show another template
				$this->zajlib->template->show('trythis.html');
		}
		
		/**
		 * This method will handle all requests which could not be routed anywhere.
		 * @param string $request A string of the actual request.
		 * @param array $optional_parameters This is only specified when the request is coming from another app and $optional_parameters were given.
		 **/
		function __error($request, $optional_parameters){
			echo "The page $request could not be found.";
			return false;
		}
	
	
	}
	

?>