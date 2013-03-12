<?php
/**
 * Methods related to manipulating text.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_text extends zajLibExtension {

	/**
	 * Converts new line characters to <br /> tags.
	 * @param string $str The original string.
	 * @return string The string with tags.
	 **/
	static function nltobr($str){
		$str = str_replace ("\n", "<br />", $str);
		$str = str_replace ("\r", "", $str);
		return $str;
	}

	/**
	 * Converts <br /> tags to new lines.
	 * @param string $str The original string.
	 * @return string The string without tags.
	 **/
	static function brtonl($str){
		$str = str_replace ("<br>", "\n", $str);
		$str = str_replace ("<br />", "\n", $str);
		return $str;
	}

	/**
	 * Removes newlines from the string.
	 * @param string $str The original string.
	 * @return string The string without tags.
	 **/
	static function remove_nl($str){
		$str = str_replace ("\n", " ", $str);
		$str = str_replace ("\r", "", $str);
		return $str;	
	}
	
	/**
	 * Strips pre words such as 'the' and 'a'.
	 * @param string $str The original string.
	 * @return string The string without the pre words.
	 * @todo Move this to a plugin.
	 **/
	function strip_pre_words($string){
		$string = mb_strtolower($string);
		$conData = str_replace("a ", "", $string);
		$conData = str_replace("az ", "", $conData);
		$conData = str_replace("the ", "", $conData);
		$conData = str_replace("dj ", "", $conData);
		$conData = str_replace("dj. ", "", $conData);
		$conData = str_replace("\"", "", $conData);
		return $conData;
	}
	
	/**
	 * Truncates a string to length. Depricated.
	 * @param string $str The original string.
	 * @param integer $length The length to truncate to.
	 * @return string The truncated string.
	 * @todo This is no longer needed here, since this is done in the template tag 'truncate'.
	 **/
	function cut_me($string, $length){
		if(strlen($string) > $length) $string = mb_substr($string, 0, $length-2)."...";
	    return $string;
	}
	
	/**
	 * Convert text urls to links.
	 * @param string $text The text to convert.
	 * @param integer $truncate Truncate long links in text to shorter version (www.facebook.com/asd) to this many characters.
	 * @return string The updated string with urls in place.
	 **/
	function urlize($text, $truncate = false){
		return $this->get_auto_link($text, $truncate);
	}

	///////////////////////////////////////////////////////////////////////////////////////////
	// Tag conversions
	function convertTagToURL($text, $tag, $link){
	 $text = ereg_replace("<$tag>([A-z0-9ÁÉÓÖŐÜŰÚÍéáűőúöüóí&'\?!:/\. \-]*)</$tag>", "<b><a href=\"$link\\1\">\\1</a></b>", $text);
	 return $text;
	}
	function convertTagToIMG($text, $tag, $align='left'){
	 	$parts = explode("<$tag>", $text);
	 	if(count($parts) > 1){
	 		// get all images
	 		foreach($parts as $part){
	 			$data = explode("</$tag>", $part);
	 			$imgurl[] = $data[0];
	 		}
	 		// process all images
	 		foreach($imgurl as $img){
				$sizedata = @getimagesize($img);
				$width = $sizedata[0];
				$height = $sizedata[1];
				$text = str_replace("<$tag>$img</$tag>", "<a href=\"javascript:newFixedWindow('$img','dszimgviewer',$width,$height);\"><img src='$img' width='200' border='0' align='$align'></a>", $text);
			}
	 	}
	 	return $text;
	}
	
	/**
	 * Depricated version
	 * @ignore
	 **/
	function get_auto_link($text, $truncate = false){
	  if(strip_tags($text) == $text){
	  	$text = ereg_replace('((www\.)([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/]))', "http://\\1", $text);
	  	$text = ereg_replace('((ftp://|http://|https://){2})([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])', "http://\\3", $text);
	  	$text = ereg_replace('(((ftp://|http://|https://){1})[a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])', "<A HREF=\"\\1\" TARGET=\"_blank\">\\1</A>", $text);
	  	$text = ereg_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',"<A HREF=\"mailto:\\1\">\\1</A>", $text);
	  }
	  return $text;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Unique number
	function unique_number(){
		$uab=57;
		$lab=48;
		
		$mic= microtime();
		$smic= substr($mic,1,2); 
		$emic= substr($mic,4,6); 
		
		$ch= (mt_rand()%($uab-$lab))+$lab;
		  
		$po= strpos($emic, chr($ch));
		
		$emica=substr($emic,0,$po);
		$emicb=substr($emic,$po,strlen($emic));
		$out=substr($emica.$smic.$emicb, 1).rand(0, 9);
			
		return $out;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Is this in that?
	function is_this_in_that($thisone, $thatone){
		if(mb_strrpos($thatone, $thisone) === false) return false;
		else return true;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// Str to proper (utf support)
	function str_to_proper($str){
		return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE);
	}

}



?>