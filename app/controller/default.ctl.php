<?php
/**
 * The default controller handles all default requests and requests which did not land in a more appropriate controller (for example because no controller file exists for that request). Notice that the name of the class must always be the same as the controller's file name: so in this case it is zajapp_default.
 * @package Controller
 * @subpackage BuiltinControllers
 **/
	class zajapp_default extends zajController{
		
		/**
		 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
		 *  related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
		 *  any admin pages will need to login first...
		 * @param string $request A string of the request path, relative to this controller.
         * @param array $optional_parameters These are optional parameters passed when the load method is called in code.
         * @return mixed Usually return true if successful, false otherwise. But can return any custom value as well.
		 **/
		public function __load($request, $optional_parameters=[]){
            return true;
		}
		
		/**
		 * The main() method is the default for any controller.
		 **/
		public function main(){
			// Now let's show the welcome template
			$this->zajlib->template->show('welcome.html');
		}
		
		/**
		 * This method will handle all requests which could not be routed anywhere.
		 * @param string $request A string of the actual request.
		 * @param array $optional_parameters This is only specified when the request is coming from another app and $optional_parameters were given.
		 * @return boolean
		 **/
		function __error($request, $optional_parameters=[]){
			http_response_code(404);
			return $this->zajlib->template->show('404.html');
		}
	
	}