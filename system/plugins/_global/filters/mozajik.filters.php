<?php
/**
 * Mozajik filter collection includes filters which are not in Django by default, but are part of the Mozajik system.  
 * @package Template
 * @subpackage Filters
 **/

////////////////////////////////////////////////////////////////////////////////////////////////
// The methods below will take the following parameters and generate the appropriate php code
//	using the write method.
//		- parameter - the parsed parameter variable/string
//		- source - the source file object
//		- counter (optional) - a 1-based counter specifying which filter is currently running (1st, 2nd, or 3rd, etcetc.)
////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Mozajik filter collection includes filters which are not in Django by default, but are part of the Mozajik system.  
 * @todo Make sure that filters correctly support the use of variables as filter parameters.
 **/
class zajlib_filter_mozajik extends zajElementCollection{	
	
	/**
	 * Filter: photo - Returns the url of the photo object. If it is a fetcher (many photos), then the first one will be returned.
	 *
	 *  <b>{{ user.data.photos|photo:'4' }}</b> The url of the photo will be displayed, without the baseurl.
	 **/
	public function filter_photo($parameter, &$source){
		// normal is default size
			if(!$parameter) $parameter = "'normal'";
		// write to file
			$content = <<<EOF
if(is_object(\$filter_var) && is_a(\$filter_var, "Photo")){
	\$filter_var = \$filter_var->get_image($parameter);
}
elseif(is_object(\$filter_var) && is_a(\$filter_var, "zajFetcher") && \$obj = \$filter_var->rewind()){
	\$filter_var=\$obj->get_image($parameter);
}
elseif(is_object(\$filter_var) && !empty(\$filter_var->photo_filter_supported)){
	\$filter_var = \$filter_var->{{$parameter}};
}
elseif(is_array(\$filter_var)){
	\$f = reset(\$filter_var);
	if(empty(\$f->photo_filter_supported)){
		\$filter_var = false;
	}
	else{
		\$filter_var = \$f->{{$parameter}};
	}
}
else{
	\$filter_var=false;
}
EOF;
			$this->zajlib->compile->write($content);
		return true;
	}
	/**
	 * Filter: count - Return the LIMITed count of a fetcher object. (This will be the number of rows returned taking into account LIMITs). This also works on arrays (where the number of items are returned) or any other data type (where 1 will be returned).
	 *
	 *  <b>{{fetcher|count}}</b> See {@link zajFetcher->count} for more details.
	 **/
	public function filter_count($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && class_implements("Countable") || is_array($filter_var)) $filter_var = count($filter_var); else $filter_var=count((array) $filter_var);');
		return true;
	}
	/**
	 * Filter: total - Return the total number of object in this fetcher. (This will be the number of rows returned independent of any LIMIT clause or pagination)
	 *
	 *  <b>{{fetcher|total}}</b> See {@link zajFetcher->total} for more details.
	 **/
	public function filter_total($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var->total;');
		return true;
	}
	/**
	 * Filter: truncate - Truncates the variable to the number specified by parameter.
	 *
	 *  <b>{{variable|truncate:'5'}}</b> Truncates the length of variable string to 5 characters. So 'Superdooper' will be 'Super...'
	 **/
	public function filter_truncate($parameter, &$source){
			if(!$parameter) return $source->warning('truncate filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(strlen($filter_var) > '.$parameter.') $filter_var=mb_substr($filter_var, 0, '.$parameter.')."...";');
		return true;
	}
	/**
	 * Filter: paginate - Paginates the fetcher object with the number per page set by the argument. By default, 10 per page.
	 *
	 *  <b>{{fetcher|paginate:'50'}}</b> Will list 50 items on this page. See {@link zajFetcher->paginate()} for more details.
	 **/
	public function filter_paginate($parameter, &$source){
		// default for parameter
			if(empty($parameter)) $parameter = 10;
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var->paginate('.$parameter.');');
		return true;
	}

	/**
	 * Filter: pagination - Displays the pagination widget.
	 *
	 *  <b>{{fetcher|pagination:'items'}}</b> You can optionally pass a parameter which will be the descriptor of the items. Defaults to 'items'.
	 **/
	public function filter_pagination($parameter, &$source){
		// default for parameter
		if(empty($parameter)) $parameter = '"items"';
		// write to file
		$this->zajlib->compile->write('if(!$filter_var->pagination->prevpage) $prevdisabled = "disabled"; if(!$filter_var->pagination->nextpage) $nextdisabled = "disabled"; $filter_var="<div class=\'pagination pagination-small pagination-centered\'><ul><li class=\'$prevdisabled\'><a href=\'{$filter_var->pagination->prevurl}\'>«</a></li><li class=\'disabled active\'><a href=\'#\'>{$filter_var->pagination->page} / {$filter_var->pagination->pagecount} ({$filter_var->total} ".'.$parameter.'.")</a></li><li class=\'$nextdisabled\'><a href=\'{$filter_var->pagination->nexturl}\'>»</a></li></ul></div>";');
		return true;
	}

	/**
	 * Filter: sort - Same as {@link zajlib_filter_base->filter_dictsort()
	 **/
	public function filter_sort($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('dictsort filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort('.$parameter.', "ASC");');
		return true;
	}

	/**
	 * Filter: rsort - Same as {@link zajlib_filter_base->filter_dictsortreversed()
	 **/
	public function filter_rsort($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('dictsort filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort('.$parameter.', "DESC");');
		return true;
	}

	/**
	 * Filter: print_r - Returns the value of PHP's print_r() function. Useful for debugging.
	 *
	 *  <b>{{variable|print_r}}</b> This is like running print_r(variable); in php.
	 **/
	public function filter_print_r($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = print_r($filter_var, true);');
		return true;
	}

	/**
	 * Filter: round - Round to the number of decimals specified by parameter (2 by default).
	 *
	 *  <b>{{variable|round:'2'}}</b> Assuming variable is 3.12355, the returned value will be 3.12.
	 **/
	public function filter_round($parameter, &$source){
			if(!$parameter) $parameter = 2;
		// write to file
			$this->zajlib->compile->write('$filter_var=round($filter_var, '.$parameter.');');
		return true;
	}

	/**
	 * Filter: remainder - The variable is divided by the filter paramter and the remainder is returned.
	 *
	 *  <b>{{variable|remainder:'3'}}</b> Assuming variable is 8, the returned value will be 2.
	 **/
	public function filter_remainder($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('remainder filter parameter required!');
		// write to file
			$this->zajlib->compile->write('$filter_var= $filter_var % '.$parameter.';');
		return true;
	}

	/**
	 * Filter: key - Return the key value of an associative array.
	 *
	 *  <b>{{assocarray|key:'red'}}</b> If ['green'=>'grass', 'red'=>'apple'], then this will return 'apple'.
	 **/
	public function filter_key($parameter, &$source){
		// default for parameter
			if(empty($parameter)) $parameter = '';
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var['.$parameter.'];');
		return true;
	}

	/**
	 * Filter: subtract - Subtract the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|subtract:'1'}}</b> Assuming variable is 3, the returned value will be 2.
	 **/
	public function filter_subtract($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(substr($parameter, 0, 1) != '$' && !is_numeric($parameter)) return $source->warning('subtract filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var-'.$parameter.';');
		return true;
	}
	
	/**
	 * Filter: multiply - Multiply by the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|multiply:'2'}}</b> Assuming variable is 2, the returned value will be 4.
	 **/
	public function filter_multiply($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(substr($parameter, 0, 1) != '$' && !is_numeric($parameter)) return $source->warning('multiply filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var*'.$parameter.';');
		return true;
	}

	/**
	 * Filter: divide - Divide by the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|divide:'2'}}</b> Assuming variable is 4, the returned value will be 2.
	 **/
	public function filter_divide($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(substr($parameter, 0, 1) != '$' && !is_numeric($parameter)) return $source->warning('divide filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var/'.$parameter.';');
		return true;
	}	
	
	/**
	 * Filter: toquerystring - Converts an array to a query string.
	 *
	 *  <b>{{variable|toquerystring:'name'}}</b> Assuming variable is an array ['red', 'white', 'blue'], the returned value will be name[0]=red&name[1]=white&name[2]=blue& .
	 **/
	public function filter_toquerystring($parameter, &$source){
		// validate parameter
			if(empty($parameter)) return $source->warning('toquerystring filter parameter is required!');
			$parameter = (trim($parameter,"'\""));
		// write to file
			$this->zajlib->compile->write('$new_str = ""; if(is_array($filter_var)) foreach($filter_var as $key=>$value){ $new_str .= "'.$parameter.'[$key]=$value&"; } $filter_var = $new_str;');
		return true;
	}

	/**
	 * Filter: tojson - Converts a variable or object to its JSON value.
	 *
	 *  <b>{{variable|tojson}}</b> Assuming variable is an array ['red', 'white', 'blue'], the returned value will be .
	 **/
	public function filter_tojson($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = json_encode($filter_var);');
		return true;
	}

	/**
	 * Filter: substr - Cuts a string at the given value. See also truncate.
	 *
	 *  <b>{{variable|substr:'5'}}</b> Truncates the length of variable string to 5 characters. So 'Superdooper' will be 'Super'
	 **/
	public function filter_substr($parameter, &$source){
			if(!$parameter) return $source->warning('substr filter parameter required!');
		// write to file
			$this->zajlib->compile->write('$filter_var=mb_substr($filter_var, 0, '.$parameter.');');
		return true;
	}
	
	/**
	 * Filter: querymode - Adds a ? or & to the end of the URL...whichever is needed.
	 *
	 *  <b>{{url|querymode}}</b> Assuming url is http://www.example.com/?q=1, it will return http://www.example.com/?q=1& and assuming URL is http://www.example.com/ it will return http://www.example.com/?
	 **/
	public function filter_querymode($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('if(strstr($filter_var, "?") === false) $filter_var.="?"; else $filter_var.="&";');
		return true;
	}
	
	/**
	 * Filter: printf - Allows substitutions in a string value. This is especially useful for localization.
	 *
	 *  <b>{{#translated_string#|printf:'16'}}</b> Assuming translated_string is 'There are %s registered' it will return 'There are 16 registered users'. Of course, '16' can be replaced with a variable as such: {{#translated_string#|printf:users.total}}
	 **/
	
	public function filter_printf($parameter, &$source, $counter){
		// write to file
			$this->zajlib->compile->write('$filter_var = str_ireplace(\'%'.$counter.'\', '.$parameter.', $filter_var);');
		return true;
	}

	/**
	 * Filter: translate - Translate a string to another locale.
	 *
	 * You must use a translation field as the input such as {{product.translation.name|translate:'hu_HU'}}. A warning will be generated if you try to use product.data.name instead.
	 *
	 * <b>{{product.translation.name|translate:'sk_SK'}}</b> Will return the localized version of the product name. Without the filter, the current locale's value is shown.
	 *
	 **/
	public function filter_translate($parameter, &$source, $counter){
		// If parameter is not defined, then the parameter is the current locale
			if(empty($parameter)) return $source->warning('You must specify which locale you want to translate to for filter "translate".');
		// write to file
			$this->zajlib->compile->write('$paramval = '.$parameter.'; $filter_var = $filter_var->get_by_locale($paramval);');
		return true;
	}

	/**
	 * Filter: in - You can check if an item is contained within another. This is especially useful for lists.
	 *
	 * If you check in a list, it will look for an object within that list. If you check in a string, it will check if the string is in the other. This is very similar to django's in operator {@link https://docs.djangoproject.com/en/1.2/ref/templates/builtins/#in-operator}
	 *
	 * <b>{{product|in:topproducts}}</b> Will return true if the object product is found within the list topproducts. Also works for arrays.
	 * <b>{{'something'|in:'A sentence about something.'}}</b> Will return true if the string is located within the string.
	 *
	 **/
	public function filter_in($parameter, &$source, $counter){
		// If parameter is not defined, then the parameter is the current locale
			if(empty($parameter)) return $source->warning('You must specify which variable you want to search in.');
		// Generate some code
			$content = <<<EOF
// if search in zajfetcher list
if(is_object($parameter) && is_a($parameter, "zajFetcher")){
	\$copy = clone {$parameter};
	\$filter_var = \$copy->filter('id', \$filter_var)->total;
}
elseif(is_object($parameter)){
	\$filter_var = in_array(\$filter_var, (array) $parameter);
}
elseif(is_array($parameter)){
	\$filter_var = in_array(\$filter_var, $parameter);
}
else{
	if(strstr($parameter, \$filter_var) !== false) \$filter_var = true;
	else \$filter_var = false;
}
EOF;
		$this->zajlib->compile->write($content);
		return true;
	}


}


?>