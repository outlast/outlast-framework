<?php
/**
 * This library contains useful methods for sending requests to APIs and such.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_request extends zajLibExtension {



	/**
	 * Sends a request to a specified url via curl. This can be more reliable the file_get_contents but is not supported on all systems.
	 * @param string $url The url of the desired destination. You can specify parameters as a query string.
	 * @param string|array $params This is optional if parameters are specified via query string in the $url. It can be an array or a query string.
	 * @param array An associative array of additional curl options. {@link http://www.php.net/manual/en/function.curl-setopt.php} Example: array(CURLOPT_URL => 'http://www.example.com/')
	 * @return string Returns a string with the content received.
	 **/
	function curl($url, $params = false, $method = "GET", $additional_options = false) {
		// Check for curl support
			if(!function_exists('curl_init')) return $this->zajlib->error("Curl support not installed.");
		// Check to see if url needs to be parsed
			if($params == false){
				// parse the url
					$params = parse_url($url);
					if($params === false) return $this->zajlib->warning("Malformed url ($url). Cannot parse.");
			}
		// Now init and send request
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			if($method == 'POST'){
				curl_setopt($curl, CURLOPT_POST, true);
				if($params) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
			}
		// Set any other options?
			if(is_array($additional_options)) foreach($additional_options as $key=>$value) curl_setopt($curl, $key, $url);		
		// Send and close
			$ret = curl_exec($curl);
		// Check to see if an error occurred
			if($ret === false) $this->zajlib->warning("Curl error (".curl_errno($curl)."): ".curl_error($curl));
		// Close and return
		curl_close($curl);
		return $ret;
	}

	/**
	 * Sends a POST request to a specified url, by using the query string as post data. You can also send the POST data in the second parameter. Supports HTTPS.
	 * @param string $url The url of the desired destination. Example: post("https://www.example.com/example.php?asdf=1&qwerty=2");
	 * @param bool|string $content The content of the document to be sent.
	 * @param bool $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
	 * @param bool $customheaders
	 * @return string Returns a string with the content received.
	 */
	function post($url, $content=false, $returnheaders = false, $customheaders = false){
		// Set the content based on url query string
			if($content == false){
				// parse the url
					$urldata = parse_url($url);
					if($urldata === false) return $this->zajlib->warning("Malformed url ($url). Cannot parse.");
				// set as content
					$content = $urldata['query'];
			}
		// Default header, merge my custom into it
			$headers = array('Content-type'=>'application/x-www-form-urlencoded');
			if(is_array($customheaders)) $headers = array_merge($headers, $customheaders);
		// Now send the POST request and return the result
			return $this->get($url, $content, $returnheaders, $headers, 'POST');
	}

	/**
	 * Sends a request via GET or POST method to the specified url via fsockopen. Supports HTTPS.
	 * @param string $url The url of the desired destination.
	 * @param string $content The content of the document to be sent.
	 * @param bool $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
	 * @param array|bool $customheaders An array of keys and values with custom headers to be sent along with the content.
	 * @param string $method Specifies the method by which the content is sent. Can be GET (the default) or POST.
	 * @return string Returns a string with the content received.
	 * @todo Optimize so that calling post() doesnt run parse_url twice.
	 */
	function get($url, $content="", $returnheaders = false, $customheaders = false, $method = 'GET'){
		// parse the url
			$urldata = parse_url($url);
			if($urldata === false) return $this->zajlib->warning("Malformed url ($url). Cannot parse.");
		// get port
			if($urldata['scheme'] == "https"){
				$port = 443;
				$prefix = "ssl://";
			}
			else $port = 80;
		// get method
			 if($method == 'POST'){
			 	$method = 'POST';
			 	$path = $urldata['path'];
			 }
			 else{
			 	$method = 'GET';
			 	$path = $url;
			 }
		// assemble my headers (if none given)
			if(empty($customheaders)) $customheaders = array();
			if(!is_array($customheaders)) return $this->zajlib->error("Invalid format for custom headers! Must be a key/value array.");
			if(empty($customheaders['Content-type']) && empty($customheaders['content-type']) && empty($customheaders['Content-Type'])) $customheaders['Content-type'] = "text/html";
		// open remote host
			$fp = fsockopen($prefix.$urldata['host'], $port);
			if($fp === false) return false;
		// send GET or POST request
			fputs($fp, "$method $path HTTP/1.1\r\n");
			fputs($fp, "Host: ".$urldata['host']."\r\n");
			// Send custom headers
				foreach($customheaders as $key=>$value) fputs($fp, "$key: $value\r\n");
		// send the content
			fputs($fp, "Content-length: ".strlen($content)."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $content."\r\n\r\n");
		// get response
			$buf = '';
			while (!feof($fp)) $buf.=fgets($fp,102);
		// close connection
			fclose($fp);
		
		// now split into header and content
			$bufdata = explode("\r\n\r\n", $buf);
			$headers = $bufdata[0];
			$content = $bufdata[1];
		
		// now return what was requested
		if($returnheaders) return $buf;
		else return $content;
	}


}
