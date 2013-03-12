<?php
/**
 * The abstract controller base class.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Controller
 * @subpackage Base
 */
 
/**
 * The abstract controller base class.
 * @method void __load() EVENT. Executed each time the given controller is loaded.
 * @method void __error() EVENT. Executed when no valid controller app/method was found! 
 * @package Controller
 * @subpackage Base
 **/
abstract class zajController{
	/**
	 * A reference to the global zajlib object.
	 * @var zajLib
	 **/
	var $zajlib;		// the global zajlib
	/**
	 * The name of the current app.
	 * @var string
	 **/
	var $name;			// name of the app
	
	/**
	 * Creates a new controller object.
	 * @param zajLib $zajlib A reference to the global zajlib object.
	 * @param string $name The name of the app.
	 **/
	function __construct(&$zajlib, $name){
		$this->zajlib = $zajlib;
		$this->name = $name;
	}

	/**
	 * Magic method which calls the appropriate method within the given controller class.
	 **/
	function __call($name, $arguments){
		// if not in debug mode, call the __error on current app
			if(method_exists($this, "__error")) $this->__error($name, $arguments);
		// else just call the standard mozajik error
			else $this->zajlib->error("application request ($this->name/$name) could not be processed. no matching application control method found!");
	}	

}


?>