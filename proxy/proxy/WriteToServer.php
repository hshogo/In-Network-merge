<?php

$storageIP ="192.168.0.56";
//$storageIP ="localhost";

global $pS;
global $p;
global $v;
global $pointAttribute;
global $pointSetAttribute;
global $valueAttribute;
global $values;
global $points;
global $pointSet;
global $tag;
global $past0;

$i = 1;
$pS = 0;
$pointSet = array();

$target_dir  = dirname(__FILE__);

while (1) {

	$cur_time=intval(time()/60)*60;
	$past0=date("Y-m-d\TH:i:sP",$cur_time);

	$file = $target_dir."/data/sample.xml";
	global $inTag;
	$depth = 0;

	$inTag = "";
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
	xml_set_processing_instruction_handler($xml_parser, "pi_handler");
	xml_set_default_handler($xml_parser, "parseDEFAULT");
	xml_set_element_handler($xml_parser, "startElement", "endElement");
	xml_set_character_data_handler($xml_parser, "contents");

	if (!($fp = fopen($file, "r"))) {
    	if (!xml_parse($xml_parser, $data, feof($fp))) {
       		die( sprintf("XML error: %s at line %d",
    		    	xml_error_string(xml_get_error_code($xml_parser)),
        	        xml_get_current_line_number($xml_parser)));
    		}
	}
	while ($data = fread($fp, 4096)) {
  	  	if (!xml_parse($xml_parser, $data, feof($fp))) {echo "###\n";
  	     		die( sprintf("XML error: %s at line %d",
  	                     	xml_error_string(xml_get_error_code($xml_parser)),
                        	xml_get_current_line_number($xml_parser)));
   	 	}
	}

	xml_parser_free($xml_parser);

	// Construct Body and Transport
	$body=array("pointSet"=>$pointSet);
	$transport=array("body"=>$body); 
	$dataRQ=array("transport"=>$transport);
	//var_dump($dataRQ);
	// Call an IEEE1888 Storage server (data method)
	// Specify the IP address of the SDK.
	$server = new SoapClient("http://".$storageIP."/axis2/services/FIAPStorage?wsdl");
	$dataRS = $server->data($dataRQ);
	// Parse IEEE1888 WRITE Response (Error Handling)
	if($dataRS == NULL){
   		echo "Error occured -- the result is empty.";
   		exit;
	}
	if(!array_key_exists("transport",$dataRS)){
   		echo "Error occured -- the transport in the result is empty.";
   		exit;
	}
	$transport=$dataRS->transport;

	if(!array_key_exists("header",$transport)){
   		echo "Error occured -- the header in the transport is empty.";
   		exit;
	}
	$header=$transport->header;
	if(!array_key_exists("OK",$header)){echo "1\n";
		if(!array_key_exists("error",$header)){echo "2\n";
    		echo "Error occured -- neither OK nor error presented in the header.";
			exit;
		}
    	echo "Error:".$header->error->_;
    	exit;
	} 

	echo $i++.": "."Succeeded..."."\n";
	sleep(60);
}

function startElement($parser, $name, $attrs) {

	global $past0;
    global $inTag;
    global $depth;
	global $pointSetAttribute;
	global $pointAttribute;
	global $valueAttribute;
	global $tag;
	global $p;
	global $v;
	global $values;
	global $points;

    $padTag = str_repeat(str_pad(" ", 3), $depth+2);
    if (!($inTag == "")) {
  	}
	if ($name == 'transport' || $name == 'body'){}
  	else if ($name == 'pointSet') {
		$tag = $name;
	    $points = array();
	   	$p = 0;
       	foreach ($attrs as $key => $value) {
	    	$pointSetAttribute = $value;
               }
     	}
  	else if ($name == 'point') {
		$tag = $name;
		$v = 0;
		$values = array();
     	foreach ($attrs as $key => $value) {
			$pointAttribute = $value;
          	}
     	}
  	else if ($name == 'value') {
		$tag = $name;
   		foreach ($attrs as $key => $value) {
			$valueAttribute = $value;
        	}
    	}
    else {
		echo "Unkwown Contents..."."\n";
	}
    $inTag = $name;
    $depth++;
  
}

function endElement($parser, $name) {

	global $past0;
    global $depth;
    global $inTag;
    global $closeTag;
	global $pS;
	global $p;
	global $pointSet;
	global $points;
	global $values;
	global $pointAttribute;
	global $pointSetAttribute;
       
    $depth--;

	if ($name == 'body' || $name == 'transport') {}
	else if ($name == 'pointSet') {
		$pointSet[$pS]=array("id"=>$pointSetAttribute, "point" => $points);
		$pS++;
	}
	else if ($name == 'point') {
		$points[$p]=array("id"=>$pointAttribute, "value"=>$values);
		$p++;
	}
  	else if ($closeTag == TRUE) {
    	$inTag = "";
   	} else if ($inTag == $name) {
		$inTag = "";
	}else {
		$padTag = str_repeat(str_pad(" ", 3), $depth+2);
	} 
}
 
function contents($parser, $data) {

	global $past0;
    global $closeTag;
	global $point;
	global $points;
	global $values;
	global $pointSet;
	global $pointAttribute;
	global $pointSetAttribute;
	global $valueAttribute;
	global $tag;
	global $v;
	global $p; 

    $data = preg_replace("/^\s+/", "", $data);
    $data = preg_replace("/\s+$/", "", $data);

    if (!($data == ""))  {
		if ($tag == 'value') {
			$values[$v]=array("time"=>$past0, "_"=>$data);
			$v++;
		}
        $closeTag = TRUE;
    } else {
        $closeTag = FALSE;
	}
}

?>
