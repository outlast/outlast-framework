<?php
/**
 * Create, modify, delete cookies.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_cookie extends zajLibExtension {
	
	/**
	 * Set a cookie with a specific name. If expiration date is omitted, then it is for the session only.
	 * @param string $name The name of the cookie.
	 * @param string $value The new value of the cookie.
	 * @param string $expiration The new expiration date of the cookie. 0 means only this session.
	 * @param boolean $subdomains Make it available to all subdomains if this is true. Default is false.
	 * @param boolean $secure Set this to true if you only want this cookie in secure mode. Default is false.
	 * @param boolean $httponly Set this to true if you only want this cookie in http mode. Default is true.
	 **/
	function set($name, $value, $expiration=0, $subdomains=false, $secure=false, $httponly=true){
		// Allow iframe cookies in IE
			header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
		// Set up domain
			if($subdomains) $domain = '.'.$this->zajlib->domain;
			else $domain = '';
		// Now create and return
			return setcookie($name, $value, $expiration, '/', $domain, $secure, $httponly);
	}
	/**
	 * An alias of set.
	 * @ignore
	 **/
	function add($name, $value, $expiration=0, $subdomains=false, $secure=false, $httponly=true){
		$this->set($name, $value, $expiration, $subdomains, $secure, $httponly);
	}

	/**
	 * Get a cookie.
	 * @param string $name The name of the cookie.
	 * @return string The value of the cookie.
	 **/
	function get($name){
		return $_COOKIE[$name];
	}

	/**
	 * Remove a cookie with the name $name.
	 * @param string $name The name of the cookie to remove.
	 * @param boolean $subdomains Make it available to all subdomains if this is true. Default is false. Must be the same value as when you were setting the cookie.
	 **/
	function remove($name, $subdomains=false){
		// Allow iframe cookies in IE
			header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
		// Set up domain
			if($subdomains) $domain = '.'.$this->zajlib->domain;
			else $domain = '';
		// Set and return 
			return setcookie($name, '', time()-24*60*60*365, '/', $domain);
	}
	/**
	 * An alias of remove.
	 * @ignore
	 **/
	function delete($name){ return $this->remove($name); }

	/**
 	 * Remove all cookies.
 	 **/
	function remove_all(){
		if(isset($_SERVER['HTTP_COOKIE'])) {
			$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			foreach($cookies as $cookie) {
				$parts = explode('=', $cookie);
				$name = trim($parts[0]);
				setcookie($name, '', time()-1000);
				setcookie($name, '', time()-1000, '/');
			}
		}		
	}
	/**
	 * An alias of remove_all.
	 * @ignore
	 **/
	function delete_all(){ return $this->remove_all(); }

}
?>