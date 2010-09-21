<?php

/**
 * Utility functions used by the IMS formats - CP and QTI.
 * 
 * 
 * University of Geneva 
 * 
 * @licence GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */

function require_once_all($pattern){
	if($files = glob($pattern)){
		foreach($files as $file){
			require_once $file;
		}
	}else{
		//debug($files);
	}
}

function str_right($text, $length){
	$text_length = strlen($text);
	return substr($text, $text_length - $length, $length);
}

function str_left($text, $length){
	return substr($text, 0, $length);
}

function html_trim_tag($text, $tag_){
	$result = $text;
    $tags = func_get_args();
    array_shift($tags);
    $match = true;
    while($match){
    	$match = false;
    	foreach($tags as $tag){
	    	$s = "<$tag>";
	    	$sl = strlen($s);
	    	$e = "</$tag>";
	    	$el = strlen($e);
	    	$result = trim($result);
	    	if(str_left($result, $sl) == $s && str_right($result, $el) == $e){
	    		$match = true;
	    		$result = substr($result,$sl, strlen($result) - $sl);
		    	$result = substr($result, 0, strlen($result) - $el);
	    	}
    	}
    }
	    		
    //debug(htmlentities($result));
    return $result;
    }
    
function str_safe($value, $replace = '_'){
	$result = '';
	for ($i = 0, $j = strlen($value); $i < $j; $i++) {
		$ascii = ord($value[$i]);
		$char = $value[$i];
		if(	(ord('a')<= $ascii && $ascii <=ord('z')) || 
			(ord('A')<= $ascii && $ascii <=ord('Z')) ||
			(ord('0')<= $ascii && $ascii <=ord('9')) ||
			$char == '.' || $char == '_'){
			$result .= $value[$i];
		}else{
			$result .= $replace;
		}
	}
    return $result;
}

function array_flatten(array $items, $deep = false){
	$result = array();
	foreach($items as $item){
		if(is_array($item) && $deep){
			$childs = array_flatten($item);
			$result = array_merge($result, $childs);
		}else{
			$result[] = $item;
		}
	}
	return $result;
}




?>