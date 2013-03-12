<?php
/**
 * Methods for manipulating images.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_graphics extends zajLibExtension {

	/**
	 * Return true if the specified file is actually an image file.
	 * @param string $path The path to the image file.
	 * @param array $allowed_formats An array of extensions to allow (not yet implemented)
	 * @todo Implement allowed_formats!
	 **/
	function is_image($path, $allowed_formats=""){
		// TODO: implement allowed_formats
		return getimagesize($path);
	}
	
	/**
	 * Resize and convert a file to a new size and format.
	 * @param string $oldpath The path to the original file. Path is a full path (for backwards compatibility).
	 * @param string $newpath The path to the new file. Whatever the filename is, it will use that extension to create the new file (unless you specify $force_exif_imagetype parameter). Format defaults to jpg, but png and gif also supported.
	 * @param integer $maxwidth Specifies the maximum width the image can be. Set to 0 or false if you do not want to touch the width.
	 * @param integer $maxheight Specifies the maximum height the image can be. Set to 0 or false if you do not want to touch the height.
	 * @param integer $jpeg_quality A number value of the jpg quality to be used in conversion. Only matters for jpg output.
	 * @param boolean $delete_original If set to true, the original file will be deleted.
	 * @param integer $force_exif_imagetype If set, this particular image format will be forced upon the conversion even if the file name would suggest otherwise. See {@link http://www.php.net/manual/en/function.exif-imagetype.php} for more info.
	 * @return boolean True if successful, false otherwise.
	 * @todo Change paths relative to basepath.
	 **/
	public function resize($oldpath, $newpath, $maxwidth = 0, $maxheight = 0, $jpeg_quality = 85, $delete_original = false, $force_exif_imagetype = 0){
		// get the new file type
			$newpathdata = pathinfo($newpath);
			$newpathdata['extension'] = mb_strtolower($newpathdata[extension]);
			
		// create the folders needed
			@mkdir($newpathdata['dirname'], 0777, true);
		// prepare image
			$im = $this->prepare_image($oldpath);
			if(!$im) return false;
		// enable alpha channels on old image
			imagealphablending($im, false);
			imagesavealpha($im, true);
		// Execute resize
			$width = imagesx($im);
			$height = imagesy($im);
			if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight)){
				if($maxwidth && $width > $maxwidth){
					$widthratio = $maxwidth/$width;
					$RESIZEWIDTH=true;
				}
				if($maxheight && $height > $maxheight){
					$heightratio = $maxheight/$height;
					$RESIZEHEIGHT=true;
				}
				if($RESIZEWIDTH && $RESIZEHEIGHT){
					if($widthratio < $heightratio) $ratio = $widthratio;
					else $ratio = $heightratio;
				}
				elseif($RESIZEWIDTH) $ratio = $widthratio;
				elseif($RESIZEHEIGHT) $ratio = $heightratio;
				
		    	$newwidth = $width * $ratio;
		        $newheight = $height * $ratio;
				if(function_exists("imagecopyresampled")){
		      		$newim = imagecreatetruecolor($newwidth, $newheight);
					imagealphablending($newim, false);
					imagesavealpha($newim, true);
		      		imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
				}else{
					$newim = imagecreate($newwidth, $newheight);
		      		imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
				}
		        $im = $newim;
			 }
		
	   // Done with resize. Save as an image.	
			if($force_exif_imagetype == IMAGETYPE_GIF || $newpathdata[extension] == "gif") ImageGif($im, $newpath);
			elseif($force_exif_imagetype == IMAGETYPE_PNG || $newpathdata[extension] == "png") ImagePng($im, $newpath);
			else ImageJpeg($im, $newpath, $jpeg_quality);
	
	   // Clean up and return true
		   ImageDestroy ($im);
		   if($delete_original) @unlink($oldpath);
		   @chmod($newpath, 0777);
		   return true;
	}


	/**
	 * Crop and convert a file to a new size and format.
	 * @param string $oldpath The path to the original file. Path is a full path (for backwards compatibility).
	 * @param string $newpath The path to the new file. Whatever the filename is, it will use that extension to create the new file. Format defaults to jpg, but png and gif also supported.
	 * @param integer $x Cropped image offset from left.
	 * @param integer $y Cropped image offset from top.
	 * @param integer $w Cropped image width.
	 * @param integer $h Cropped image height.
	 * @param integer $jpeg_quality A number value of the jpg quality to be used in conversion. Only matters for jpg output.
	 * @param boolean $delete_original If set to true, the original file will be deleted.
	 * @return boolean True if successful, false otherwise.
	 * @todo Change paths relative to basepath.
	 **/
	public function crop($oldpath, $newpath, $x, $y, $w, $h, $jpeg_quality = 85, $delete_original = false){
		// get the new file type
			$newpathdata = pathinfo($newpath);
			$newpathdata['extension'] = mb_strtolower($newpathdata[extension]);
		// create the folders needed
			@mkdir($newpathdata['dirname'], 0777, true);
		// prepare image
			$im = $this->prepare_image($oldpath);
			if(!$im) return false;
	
		// Execute crop
		if(function_exists("imagecopyresampled")){
			$newim = imagecreatetruecolor($w, $h);
			imagecopyresampled($newim, $im, 0, 0, $x, $y, $w, $h, $w, $h);
		}else{
			$newim = imagecreate($w, $h);
			imagecopyresized($newim, $im, 0, 0, $x, $y, $w, $h, $w, $h);
		}
		$im = $newim;
		
	   // Done with resize. Save as an image.	
			if($newpathdata['extension'] == "gif") ImageGif($im, $newpath);
			elseif($newpathdata['extension'] == "png") ImagePng($im, $newpath);
			else ImageJpeg($im, $newpath, $jpeg_quality);
	   // Clean up and return true
		   ImageDestroy ($im);
		   if($delete_original) @unlink($oldpath);
		   @chmod($newpath, 0777);
		   return true;
	}
	
	/**
	 * Take the old path and create an image object from it.
	 * @param string $oldpath The path to the original file. Path is a full path (for backwards compatibility).
	 **/
	private function prepare_image($oldpath){	   
		// Check for GD library
			if(!function_exists("imagecreatefromjpeg")) return $this->zajlib->error("PHP GD library not installed! Please contact your system administrator.");
		// First try jpeg, then gif and png
			$im = @imagecreatefromjpeg($oldpath);
			if($im === false) $im = @imagecreatefromgif($oldpath);
			if($im === false) $im = @imagecreatefrompng($oldpath);	   
		return $im;
	}

}




?>