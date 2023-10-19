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
		 * @param string $request A string of the request path, relative to this controller.
         * @param array $optional_parameters These are optional parameters passed when the load method is called in code.
         * @return mixed Usually return true if successful, false otherwise. But can return any custom value as well.
		 **/
		public function __load($request, $optional_parameters=[]){
			// This is set any time any request is made to this controller (so every time we call /sample/anything/ or even just /sample/)
			$this->ofw->variable->my_own_template_variable = "This is from the Sample controller!";

			return true; // This will be returned by $this->ofw->load->controller(). An explicit return of false is usually meant to signify a problem.
		}
		
		/**
		 * The main() method is the default for any controller.
		 **/
		public function main(){
			
			// Again, like in the default, we show the welcome, but this time the __load() method set my_own_template_variable
			return $this->ofw->template->show('welcome.html');

		}


		/**
		 * This is another method. The name is important, because this determines which requests go here. So this will process /sample/try/this/
		 **/
		public function try_this(){
			
			// Let's set another template variable
			$this->ofw->variable->another_variable = "You have also successfully requested the try_this method!";
			
			// This time let's show another template
			return $this->ofw->template->show('trythis.html');

		}

		/**
		 * This is an example of using ajax requests.
		 */
		public function try_some_ajax(){
			return $this->ofw->ajax("<h3>Hello World!</h3> This is a 'Hello world!' message from the try_some_ajax() function in /app/controller/sample.ctl.php");
		}


		/**
		 * This will handle all other requests for this controller.
		 * @param string $request A string of the actual request.
		 * @param array $optional_parameters This is only specified when the request is coming from another app and $optional_parameters were given.
		 * @return boolean
		 **/
		public function __error($request, $optional_parameters=[]){
			// __error methods are optional in other controllers. The default controller's error method will be called if none exist here!

			// You can add custom logic here to handle any subfolder requests.

			// You can also reroute to the main error method to display standard 404
			return $this->ofw->reroute($request, $optional_parameters);
		}
	
	}