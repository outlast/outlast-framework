<?php
/**
 * Generate RSS and ATOM feeds.
 * @author Anis uddin Ahmad <anisniit@gmail.com>, Aron Budinszky <aron@mozajik.org>
 * @link http://www.ajaxray.com/projects/rss
 * @version 3.0
 * @package Library
 **/

////////////////////////////////////////////////////////////////////////////////////////////////
// RSS 0.90  Officially obsoleted by 1.0
// RSS 0.91, 0.92, 0.93 and 0.94  Officially obsoleted by 2.0
// So, define constants for RSS 1.0, RSS 2.0 and ATOM 	

define('RSS1', 'RSS 1.0', true);
define('RSS2', 'RSS 2.0', true);
define('ATOM', 'ATOM', true);

/**
 * Generate RSS and ATOM feeds.
 **/
class zajlib_feed extends zajLibExtension {
	 private $channels      = array();  // Collection of channel elements
	 private $items         = array();  // Collection of items as object of zajlib_feed_item class.
	 private $data          = array();  // Store some other version wise data
	 private $CDATAEncoding = array();  // The tag names which have to encoded as CDATA
	 private $head = '';				// Heading of the feed
	 
	 private $version   = null; 
	
	/**
	 * Construct a new feed library object.
	 **/ 
	public function __construct(&$zajlib, $system_library) {
		// send to parent to create
		parent::__construct($zajlib, $system_library);
		
		// RSS2 by default
		$this->version = RSS2;
			
		// set default heading
		$this->set_head();

		// Setting default value for essential channel elements
		$this->channels['title']        = $this->version . ' Feed';
		$this->channels['link']         = 'http://www.mozajik.org/';
				
		//Tag names to encode in CDATA
		$this->CDATAEncoding = array('description', 'content:encoded', 'summary');
		
	}

	/**
	 * Sets whether its RSS1, RSS2, or ATOM. This also resets the head to the default value for that feed type!
	 * @param $constant Use one of the constants RSS1, RSS2, or ATOM
	 **/
	public function set_version($constant){
		// Set the version variable
			$this->version = $constant;
		// Set my default head
			$this->set_head();
		return true;
	}

	/**
	 * Sets heading to a specific value. By default the head is set automatically based on the version set in {@link set_version()}.
	 * @param string $head An optional custom value set for the heading of this feed.
	 * @return string Returns the new header.
	 **/
	public function set_head($head = false){
		if($head !== false){
			// Set a specific custom header
				$this->head = $head;
		}
		else{
			// Decide automatically based on version.
				switch($this->version){
					case RSS1: $this->head = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/">';
					case RSS2: $this->head = '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/">';
					case ATOM: $this->head = '<feed xmlns="http://www.w3.org/2005/Atom">';
				}	
		}
		return $this->head;
	}


	/**
	 * Set a channel element
	 * @param string $elementName Name of the channel tag.
	 * @param string $content Content of the channel tag
 	 **/
	public function set_channel_element($elementName, $content){
		$this->channels[$elementName] = $content ;
	}
	
	/**
	 * Set multiple channel elements from an array. Array elements should be 'channelName' => 'channelContent' format.
	 * @param array $elementArray The array of channel elements
 	 **/
	public function set_channel_elements_from_array($elementArray){
		if(! is_array($elementArray)) return;
		foreach ($elementArray as $elementName => $content) $this->set_channel_element($elementName, $content);
	}
	
	/**
	 * Generate the actual RSS/ATOM file
 	 **/
	public function generate_feed(){
		header("Content-type: text/xml");
		$this->printHead();
		$this->printChannels();
		$this->printItems();
		$this->printTale();
		exit();
	}
	
	/**
	 * Create a new zajlib_feed_item
	 * @return zajlib_feed_item A new {@link zajlib_feed_item} object.
 	 **/
	public function create_new_item(){
		$Item = new zajlib_feed_item($this->version);
		return $Item;
	}
	
	/**
	 * Add a zajlib_feed_item to the main class
	 * @param zajlib_feed_item A feed item object that needs to be attached.
 	 **/
	public function add_item($zajlib_feed_item){
		$this->items[] = $zajlib_feed_item;    
	}
	
		
	/**
	 * Sets the title of the channel.
	 * @param string $title Title channel tag.
 	 **/
	public function set_title($title){
		$this->set_channel_element('title', $title);
	}
	
	/**
	 * Sets the description of the channel.
	 * @param string $description Description channel tag.
 	 **/
	public function set_description($desciption){
		$this->set_channel_element('description', $desciption);
	}
	
	/**
	 * Sets the link of the channel.
	 * @param string $link Link channel tag.
 	 **/
	public function set_link($link){
		$this->set_channel_element('link', $link);
	}
	
	/**
	 * Sets the image of the channel.
	 * @param string $title Title of image.
	 * @param string $link Link of the image.
	 * @param string $url URL to the image.
 	 **/
	public function set_image($title, $link, $url){
		$this->set_channel_element('image', array('title'=>$title, 'link'=>$link, 'url'=>$url));
	}
	
	/**
	 * Sets the about channel tag. Only for RSS 1.0
	 * @param string $url URL to the image.
	 **/
	public function set_channel_about($url){
		$this->data['ChannelAbout'] = $url;    
	}
	
	/**
	 * Generates a uuid.
	 * @param string $key The key to use.
	 * @param string $prefix An optional prefix.
	 * @return string The formated uuid.
	 **/
	public function uuid($key = null, $prefix = ''){
		$key = ($key == null)? uniqid(rand()) : $key;
		$chars = md5($key);
		$uuid  = substr($chars,0,8) . '-';
		$uuid .= substr($chars,8,4) . '-';
		$uuid .= substr($chars,12,4) . '-';
		$uuid .= substr($chars,16,4) . '-';
		$uuid .= substr($chars,20,12);
		return $prefix . $uuid;
	}

	// End # public functions ----------------------------------------------
	
	// Start # private functions ----------------------------------------------
	
	/**
	* Prints the xml and rss namespace
	* 
	* @access   private
	* @return   void
	*/
	private function printHead()
	{
		$out  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
		$out .= $this->head . PHP_EOL;;			
		echo $out;
	}
	
	/**
	* Closes the open tags at the end of file
	* 
	* @access   private
	* @return   void
	*/
	private function printTale()
	{
		if($this->version == RSS2)
		{
			echo '</channel>' . PHP_EOL . '</rss>'; 
		}    
		elseif($this->version == RSS1)
		{
			echo '</rdf:RDF>';
		}
		else if($this->version == ATOM)
		{
			echo '</feed>';
		}
	  
	}

	/**
	* Creates a single node as xml format
	* 
	* @access   private
	* @param    srting  name of the tag
	* @param    mixed   tag value as string or array of nested tags in 'tagName' => 'tagValue' format
	* @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
	* @return   string  formatted xml tag
	*/
	private function makeNode($tagName, $tagContent, $attributes = null)
	{        
		$nodeText = '';
		$attrText = '';

		if(is_array($attributes))
		{
			foreach ($attributes as $key => $value) 
			{
				$attrText .= " $key=\"$value\" ";
			}
		}
		
		if(is_array($tagContent) && $this->version == RSS1)
		{
			$attrText = ' rdf:parseType="Resource"';
		}
		
		
		$attrText .= (in_array($tagName, $this->CDATAEncoding) && $this->version == ATOM)? ' type="html" ' : '';
		$nodeText .= (in_array($tagName, $this->CDATAEncoding))? "<{$tagName}{$attrText}><![CDATA[" : "<{$tagName}{$attrText}>";
		 
		if(is_array($tagContent))
		{ 
			foreach ($tagContent as $key => $value) 
			{
				$nodeText .= $this->makeNode($key, $value);
			}
		}
		else
		{
			$nodeText .= (in_array($tagName, $this->CDATAEncoding))? $tagContent : htmlentities($tagContent);
		}           
			
		$nodeText .= (in_array($tagName, $this->CDATAEncoding))? "]]></$tagName>" : "</$tagName>";

		return $nodeText . PHP_EOL;
	}
	
	/**
	* @desc     Print channels
	* @access   private
	* @return   void
	*/
	private function printChannels()
	{
		//Start channel tag
		switch ($this->version) 
		{
		   case RSS2: 
				echo '<channel>' . PHP_EOL;        
				break;
		   case RSS1: 
				echo (isset($this->data['ChannelAbout']))? "<channel rdf:about=\"{$this->data['ChannelAbout']}\">" : "<channel rdf:about=\"{$this->channels['link']}\">";
				break;
		}
		
		//Print Items of channel
		foreach ($this->channels as $key => $value) 
		{
			if($this->version == ATOM && $key == 'link') 
			{
				// ATOM prints link element as href attribute
				echo $this->makeNode($key,'',array('href'=>$value));
				//Add the id for ATOM
				echo $this->makeNode('id',$this->uuid($value,'urn:uuid:'));
			}
			else
			{
				echo $this->makeNode($key, $value);
			}    
			
		}
		
		//RSS 1.0 have special tag <rdf:Seq> with channel 
		if($this->version == RSS1)
		{
			echo "<items>" . PHP_EOL . "<rdf:Seq>" . PHP_EOL;
			foreach ($this->items as $item) 
			{
				$thisItems = $item->get_elements();
				echo "<rdf:li resource=\"{$thisItems['link']['content']}\"/>" . PHP_EOL;
			}
			echo "</rdf:Seq>" . PHP_EOL . "</items>" . PHP_EOL . "</channel>" . PHP_EOL;
		}
	}
	
	/**
	* Prints formatted feed items
	* 
	* @access   private
	* @return   void
	*/
	private function printItems()
	{    
		foreach ($this->items as $item) 
		{
			$thisItems = $item->get_elements();
			
			//the argument is printed as rdf:about attribute of item in rss 1.0 
			echo $this->startItem($thisItems['link']['content']);
			
			foreach ($thisItems as $zajlib_feed_item ) 
			{
				echo $this->makeNode($zajlib_feed_item['name'], $zajlib_feed_item['content'], $zajlib_feed_item['attributes']); 
			}
			echo $this->endItem();
		}
	}
	
	/**
	* Make the starting tag of channels
	* 
	* @access   private
	* @param    srting  The vale of about tag which is used for only RSS 1.0
	* @return   void
	*/
	private function startItem($about = false)
	{
		if($this->version == RSS2)
		{
			echo '<item>' . PHP_EOL; 
		}    
		elseif($this->version == RSS1)
		{
			if($about)
			{
				echo "<item rdf:about=\"$about\">" . PHP_EOL;
			}
			else
			{
				die('link element is not set .\n It\'s required for RSS 1.0 to be used as about attribute of item');
			}
		}
		else if($this->version == ATOM)
		{
			echo "<entry>" . PHP_EOL;
		}    
	}
	
	/**
	* Closes feed item tag
	* 
	* @access   private
	* @return   void
	*/
	private function endItem()
	{
		if($this->version == RSS2 || $this->version == RSS1)
		{
			echo '</item>' . PHP_EOL; 
		}    
		else if($this->version == ATOM)
		{
			echo "</entry>" . PHP_EOL;
		}
	}
	

	
	// End # private functions ----------------------------------------------
	
} // end of class FeedWriter

// load into mozajik






// Feed item helper class

/**
 * This is a helper class inteded to support the creation of feeds using the {@link zajlib_feed} class.
 **/
class zajlib_feed_item
{
	private $elements = array();    //Collection of feed elements
	private $version;
	
	/**
	* Constructor 
	* 
	* @param    contant     (RSS1/RSS2/ATOM) RSS2 is default. 
	*/ 
	function __construct($version = RSS2)
	{    
		$this->version = $version;
	}
	
	/**
	* Add an element to elements array
	* 
	* @access   public
	* @param    srting  The tag name of an element
	* @param    srting  The content of tag
	* @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
	* @return   void
	*/
	public function add_element($elementName, $content, $attributes = null)
	{
		$this->elements[$elementName]['name']       = $elementName;
		$this->elements[$elementName]['content']    = $content;
		$this->elements[$elementName]['attributes'] = $attributes;
	}
	
	/**
	* Set multiple feed elements from an array. 
	* Elements which have attributes cannot be added by this method
	* 
	* @access   public
	* @param    array   array of elements in 'tagName' => 'tagContent' format.
	* @return   void
	*/
	public function add_element_array($elementArray)
	{
		if(! is_array($elementArray)) return;
		foreach ($elementArray as $elementName => $content) 
		{
			$this->add_element($elementName, $content);
		}
	}
	
	/**
	* Return the collection of elements in this feed item
	* 
	* @access   public
	* @return   array
	*/
	public function get_elements()
	{
		return $this->elements;
	}
	
	// Wrapper functions ------------------------------------------------------
	
	/**
	* Set the 'dscription' element of feed item
	* 
	* @access   public
	* @param    string  The content of 'description' element
	* @return   void
	*/
	public function set_description($description) 
	{
		$tag = ($this->version == ATOM)? 'summary' : 'description'; 
		$this->add_element($tag, $description);
	}
	
	/**
	* @desc     Set the 'title' element of feed item
	* @access   public
	* @param    string  The content of 'title' element
	* @return   void
	*/
	public function set_title($title) 
	{
		$this->add_element('title', $title);  	
	}
	
	/**
	* Set the 'date' element of feed item
	* 
	* @access   public
	* @param    string  The content of 'date' element
	* @return   void
	*/
	public function set_date($date) 
	{
		if(! is_numeric($date))
		{
			$date = strtotime($date);
		}
		
		if($this->version == ATOM)
		{
			$tag    = 'updated';
			$value  = date(DATE_ATOM, $date);
		}        
		elseif($this->version == RSS2) 
		{
			$tag    = 'pubDate';
			$value  = date(DATE_RSS, $date);
		}
		else                                
		{
			$tag    = 'dc:date';
			$value  = date("Y-m-d", $date);
		}
		
		$this->add_element($tag, $value);    
	}
	
	/**
	* Set the 'link' element of feed item
	* 
	* @access   public
	* @param    string  The content of 'link' element
	* @return   void
	*/
	public function set_link($link) 
	{
		if($this->version == RSS2 || $this->version == RSS1)
		{
			$this->add_element('link', $link);
		}
		else
		{
			$this->add_element('link','',array('href'=>$link));
			$this->add_element('id', zajlib_feed::uuid($link,'urn:uuid:'));
		} 
		
	}
	
	/**
	* Set the 'encloser' element of feed item
	* For RSS 2.0 only
	* 
	* @access   public
	* @param    string  The url attribute of encloser tag
	* @param    string  The length attribute of encloser tag
	* @param    string  The type attribute of encloser tag
	* @return   void
	*/
	public function set_encloser($url, $length, $type)
	{
		$attributes = array('url'=>$url, 'length'=>$length, 'type'=>$type);
		$this->add_element('enclosure','',$attributes);
	}
	
 } // end of class zajlib_feed_item
 