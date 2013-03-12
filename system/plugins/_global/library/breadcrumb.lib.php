<?php
/**
 * Helps you create breadcrumb navigation easily.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 * @depricated
 **/

class zajlib_breadcrumb extends zajLibExtension {
	
	var $crumbs = (object) array();
	
	/**
	 * Adds a level to the current session.
	 * @param string $name The name of the crumb which must be a unique name.
	 * @param string $relative_url The relative url of the crumb.
	 * @return object The current crumbs in a template-ready object list.
	 **/
	function add($name, $relative_url){
		// Add a crumb
			$this->crumbs->$name = $relative_url;
		return $this->crumbs;
	}

	/**
	 * Remove a level from the current session by name.
	 * @param string $name The name of the crumb which must be a unique name.
	 * @return object The current crumbs in a template-ready object list.
	 **/
	function remove($name){
		// Remove a crumb
			unset($this->crumbs->$name);
		return $this->crumbs;
	}

	/**
	 * Return the crumbs object.
	 * @return object The current crumbs in a template-ready object list.
	 **/
	function get(){
		return $this->crumbs;
	}
	
}


	
?>