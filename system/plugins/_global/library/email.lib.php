<?php
/**
 * Send emails in various formats.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_email extends zajLibExtension {
	
	/**
	 * Send a text-based email in ISO or UTF encoding. This should not be used much - HTML format is prefered!
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body.
	 * @param string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param string $bounceto If set, the email will bounce to this address. By default, bounces are ignored and not sent anywhere.
	 * @param string $additional_headers If set, these additional headers will be appended to the email.
	 * @return bool Returns true if succesful, false otherwise.
	 **/
	function send($from, $to, $subject, $body, $sendcopyto = false, $bounceto = false, $additional_headers = false){
		// Encode headers
			$subject = mb_encode_mimeheader($subject, "UTF-8");
			$from = mb_encode_mimeheader($from, "UTF-8");
			$to = mb_encode_mimeheader($to, "UTF-8");
		// send a copy to?
			if($sendcopyto) $bcc = "Bcc: ".mb_encode_mimeheader($sendcopyto, "UTF-8")."\r\n";
			else $bcc = '';
		// bounce to set?
			if($bounceto) $pars = "-f $bounceto";
			else $pars = '';
		// try to send email
			$return = mail($to, $subject, $body, "From: $from\r\n${bcc}".$additional_headers, $pars);
			//$return = mail('aron@zajmedia.com', 'asdf', 'Test!', 'From: aron@zajlik.hu\r\n');
		return $return;
	}
	
	/**
	 * Send a UTF-encoded HTML email.
	 * @param string $from The email which is displayed as the from field.
	 * @param string $to The email to which this message should be sent.
	 * @param string $subject A string with the email's subject.
	 * @param string $body The email's body which should be in HTML.
	 * @param string $sendcopyto If set, a copy of the email will be sent (bcc) to the specified email address. By default, no copy is sent.
	 * @param string $bounceto If set, the email will bounce to this address. By default, bounces are ignored and not sent anywhere.
	 * @param string $body_text If set, text-version will be set to this.
	 * @return bool Returns true if succesful, false otherwise.
	 */
	function send_html($from, $to, $subject, $body, $sendcopyto = "", $bounceto = "", $body_text = ""){
		// Create a plain text version
			$txt_body = strip_tags($this->zajlib->text->brtonl($body));		
		
		// Create new mail object
			$mimemail = new mMail();
			//$mimemail->set_charset("utf-8");
			$from = mb_encode_mimeheader($from, "UTF-8");
			$mimemail->setFrom(trim($from));
			$mimemail->addTo(trim($to));
		// If copy requested
			if($sendcopyto) $mimemail->addBcc($sendcopyto);
		// If bounce requested (not yet supported)
			if($bounceto) $mimemail->setBounce(trim($bounceto));
			//else $mimemail->setBounce(trim($from));
			
			// move this to mailer class!
			$subject = mb_encode_mimeheader($subject, "UTF-8");
			$mimemail->setSubject($subject);
			$mimemail->setTextBody($body_text);
			$mimemail->setHtmlBody($body);

			return $mimemail->send();
	}
	
	/**
	 * Parse an email address in "Mr. Name <name@example.com>" format. Returns an object.
	 * @param string $email_address_with_name The email address to parse.
	 * @return object Returns an object {'name'=>'Mr. Name', 'email'=>'name@example.com'}. If no name specified, the 'name' property will be empty.
	 **/
	function get_named_email($email_address_with_name){
		// Parse an email first via regexp (if in format My Name <name@example.com>)
			$result = preg_match_all('/([^<]*)<([^>]*)/', $email_address_with_name, $arr, PREG_SET_ORDER);
		// Create my return object
			$email_data = (object) array();
		// If result found then parse it now
			if($result){
				$email_data->name = trim($arr[0][1]);
				$email_data->email = trim($arr[0][2]);
			}
			else{
				$email_data->name = '';
				$email_data->email = trim($email_address_with_name);
			}
		return $email_data;
	}

	/**
	 * Checks and returns true if the email address is valid. You can specify whether to allow "Name <test@test.com>" formatting.
	 * @param string $email The email address to test.
	 * @param boolean $allow_named_format Set to true if you want to allow named format. False by default.
	 * @todo Change to preg_match support.
	 * @return boolean Returns true if the email is valid, false otherwise.
	 **/
	function valid($email, $allow_named_format = false){
		// If allow named format
			if($allow_named_format){
				$email_data = $this->get_named_email($email);
				$email = $email_data->email;
			}
		// Now check and return 
			return (boolean) preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/', $email);
	}
}

/************************************************\
    Name    : mMail
    Version : 1.0
    Author  : Fadil Kujundzic
    Date    : 18 May 2005
    Modifications: Aron Budinszky

    mMail is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    mMail is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with mMail; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
\***********************************************/

class mMail
{
	var $To;
	var $Cc;
	var $Bcc;
	var $From;
	var $ReplayTo;
	var $Subject;
	var $Header;
	var $Body_html;
	var $Html_images;
	var $Body_txt;
	var $Attachments;
	var $Boundary;
	var $Bounce;
        var $Priority;
	
	function mMail()
	{
		$this->To = null;
		$this->Cc = null;
		$this->Bcc = null;
		$this->From = null;
		$this->ReplayTo = null;
		$this->Subject = "";
		$this->Header = null;
		$this->Body_html = null;
		$this->Html_images = null;
		$this->Body_txt = null;
		$this->Bounce = null;
		$this->Attachments = null;
                $this->Priority = null;
	}
	
	function setHtmlBody($body)
	{
		$this->Body_html = $body;
	}
	
	function setTextBody($body)
	{
		$this->Body_txt = $body;
	}
	
	function setPriority($priority=3)
	{
		/**
		1: Highest Priority
		2: High Priority
		3: Normal (default if not defined)
		4: Low Priority
		5: Lowest Priority
		*/

		if($priority > 0 && $priority < 6)
		{
			$this->Priority = $priority;
			return true;
		}
		else 
			return false;
	}
	
	function addTo($email)
	{
		if(!is_array($this->To))
			$this->To = array();
		if($this->checkMail($email))
		{
			$this->To[] = $email;
			return true;
		}
		return false;
	}
	
	function addCc($email)
	{
		if(!is_array($this->Cc))
			$this->Cc = array();
		if($this->checkMail($email))
		{
			$this->Cc[] = $email;
			return true;
		}
		return false;
	}
	
	function addBcc($email)
	{
		if(!is_array($this->Bcc))
			$this->Bcc = array();
		if($this->checkMail($email))
		{
			$this->Bcc[] = $email;
			return true;
		}
		return false;
	}
	
	function setFrom($name)
	{
		$this->From = $name;
	}
	
	function setSubject($subject)
	{
		$this->Subject = $subject;
	}
	
	function setReplayTo($email)
	{
		if($this->checkMail($email))
		{
			$this->ReplayTo = $email;
			return true;
		}
		return false;
	}

	function setBounce($email){
		if($this->checkMail($email))
		{
			$this->Bounce = $email;
			return true;
		}
		return false;
	}
	
	function addAttachment($name, $type, $path, $encoding="base64")
	{
		if(!is_array($this->Attachments))
			$this->Attachments = array();
		$this->Attachments[] = array('name'=>$name, 'type'=>$type, 'path'=>$path, 'encoding'=>$encoding);
	}
	
	function send()
	{
		$mail = $this->build();
		$success = true;

		// Create bounce
			if($this->Bounce) $bounce = "-f ".$this->Bounce;
			else $bounce = "";
		// Send each
			foreach($this->To as $to){
				$result = mail($to, $this->Subject, $mail, $this->Header, $bounce);
				if(!$result) $success = false;
			}
		return $success;
	}
	
	function build()
	{
		$this->getHeader();
		if($this->Body_html && !is_array($this->Attachments))
		{
			$boundary = "=-".md5(microtime());
			$this->Header = preg_replace("=\[BOUNDARY\]=", $boundary, $this->Header);
			$boundary_alt = "=-".md5(microtime());
			$this->parseHtml($boundary);
			$msg  = "--".$boundary."\n".
							"Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"\n\n".
							"--".$boundary_alt."\n".
							"Content-Type: text/plain; charset=iso-8859-1\n".
							"Content-Transfer-Encoding: 7bit\n\n".
							$this->Body_txt."\n\n".
							"--".$boundary_alt."\n".
							"Content-Type: text/html; charset=utf-8\n".
							"Content-Transfer-Encoding: 7bit\n\n".
							$this->Body_html."\n\n".
							"--".$boundary_alt."--\n\n".
							$this->Html_images.
							"--".$boundary."--\n";
		}
		elseif($this->Body_html && is_array($this->Attachments))
		{
			$boundary = "=-".md5(microtime());
			$boundary_mail = "=-".md5(microtime());
			$this->Header = preg_replace("=\[BOUNDARY\]=", $boundary, $this->Header);
			$boundary_alt = "=-".md5(microtime());
			$this->parseHtml($boundary_mail);
			$msg  = "--".$boundary."\n".
							"Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"".$boundary_mail."\"\n\n".
							"--".$boundary_mail."\n".
							"Content-Type: multipart/alternative; boundary=\"".$boundary_alt."\"\n\n".
							"--".$boundary_alt."\n".
							"Content-Type: text/plain; charset=iso-8859-1\n".
							"Content-Transfer-Encoding: 7bit\n\n".
							$this->Body_txt."\n\n".
							"--".$boundary_alt."\n".
							"Content-Type: text/html; charset=utf-8\n".
							"Content-Transfer-Encoding: 7bit\n\n".
							$this->Body_html."\n\n".
							"--".$boundary_alt."--\n\n".
							$this->Html_images.
							"--".$boundary_mail."--\n\n".
							$this->getAttachments($boundary).
							"--".$boundary."--\n";
		}
		elseif($this->Body_txt && is_array($this->Attachments))
		{
			$boundary = "=-".md5(microtime());
			$this->Header = preg_replace("=\[BOUNDARY\]=", $boundary, $this->Header);
			$msg = "--".$boundary."\n".
						 "Content-Type: text/plain; charset=iso-8859-1\n".
						 "Content-Transfer-Encoding: 7bit\n\n".
						 $this->Body_html."\n\n".
						 $this->getAttachments($boundary).
						 "--".$boundary."--\n";
		}
		else 
			$msg = $this->Body_txt;
		return $msg;
	}
	
	function getHeader()
	{
		$this->Header = "From: ".$this->From."\r\n";
		//$this->Header .= "Subject: ".$this->Subject."\r\n";
		if($this->Priority) $this->Header .= "X-Priority: ".$this->Priority."\r\n";
		if(is_array($this->Cc)) $this->Header .= "Cc: ".implode(',', $this->Cc)."\r\n";
		if(is_array($this->Bcc)) $this->Header .= "Bcc: ".implode(',', $this->Bcc)."\n";
		$this->Header .= "Date: ".date("D, d M Y H:i:s")."\n";
		$this->Header .= "Mime-Version: 1.0\n";
		$this->Header .= "Message-ID: <id_".md5(microtime()).">\r\n";
		if($this->Body_html)
			$this->Header .= is_array($this->Attachments) ? "Content-Type: multipart/mixed; boundary=\"[BOUNDARY]\"\n" : "Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"[BOUNDARY]\"\n";
		else
			$this->Header .= is_array($this->Attachments) ? "Content-Type: multipart/mixed; boundary=\"[BOUNDARY]\"\n" : "Content-Type: text/plain; charset=iso-8859-1\nContent-Transfer-Encoding: 7bit\n\n";
		$this->Header .= "\n";
	}
	
	function parseHtml($boundary)
	{
		$images = array();
		$html_images = "";
		preg_match_all("/src=\"[^\"]*\"/", $this->Body_html, $images);
		foreach($images[0] as $image)
		{
			$path = preg_replace(array("/src=/", "/\"/"), "", $image);
			$iname = explode('/', $path);
			$iname = $iname[count($iname)-1];
			$itype = explode('.', $iname);
			$itype = $itype[count($itype)-1];
			$cid = 'image_'.md5(microtime());
			$path = preg_replace("/http\:\/\//", "", $path);
			$path = explode("/", $path);
			$path[0] = substr($_SERVER['DOCUMENT_ROOT'],0,strlen($_SERVER['DOCUMENT_ROOT']));
			$path = implode("/", $path);
			$fp = fopen($path,'r');
			$data = chunk_split(base64_encode(fread($fp, filesize($path))));
			fclose($fp);
			$this->Body_html = preg_replace("/(".addcslashes($image, "./").")/", "src=\"cid:".$cid."\"", $this->Body_html);
			$html_images .= "--".$boundary."\n".
											"Content-Type: image/".$itype."; name=\"".$iname."\"\n".
											"Content-Transfer-Encoding: base64\n".
											"Content-ID: <".$cid.">\n".
											"Content-Disposition: attachment; filename=".$iname."\n\n".
											$data."\n\n";
		}
		$this->Html_images = $html_images;
		if(!$this->Body_txt)
			$this->Body_txt = strip_tags($this->Body_html);
	}
	
	function getAttachments($boundary)
	{
		$attachments = "";
		foreach($this->Attachments as $attachment)
		{
			$attachments .= "--".$boundary."\n".
											"Content-Type: ".$attachment['type']."; name=\"".$attachment['name']."\"\n".
											"Content-Transfer-Encoding: ".$attachment['encoding']."\n".
											"Content-Disposition: attachment; filename=".$attachment['name']."\n\n";
			$fp = fopen($attachment['path'],'r');
			$data = fread($fp, filesize($attachment['path']));
			fclose($fp);
			$attachments .= ($attachment['encoding'] != 'base64' ? $data : chunk_split(base64_encode($data)))."\n\n";
		}
		return $attachments;
	}
	
	function checkMail($email)
	{
	  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email))
	    return false;
	
	  $email_array = explode("@", $email);
	  $local_array = explode(".", $email_array[0]);
	  for ($i = 0; $i < sizeof($local_array); $i++)
	    if(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",$local_array[$i]))
	      return false;
	
	  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) 
		{
	    $domain_array = explode(".", $email_array[1]);
	    if (sizeof($domain_array) < 2)
	        return false;
	
	    for ($i = 0; $i < sizeof($domain_array); $i++)
	      if(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",$domain_array[$i]))
	        return false;
	  }
	
		if(@getmxrr($email_array[1], $MXHost)) 
		  return true;
		else 
		  return (@fsockopen($email_array[1], 25, $errno, $errstr, 30) ? true : false); 
	}
}
?>