<?php

$num = 4;
$target_dir  = dirname(__FILE__);
$p = 0;

while (1) {

	$target_file = $target_dir."/data/sample.xml";
	$contents = "";
	$contents .= "<transport>"."\n";
	$contents .= str_pad(" ", 3)."<body>";

	for ($i = 1; $i <= $num; $i++) {
		//$file = "/etc/squid3/dataset/write".$i.".xml";
		$file = $target_dir."/data/write".$i.".xml";
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
  	  		if (!xml_parse($xml_parser, $data, feof($fp))) {
  	     			die( sprintf("XML error: %s at line %d",
  	                     	 	xml_error_string(xml_get_error_code($xml_parser)),
                        		xml_get_current_line_number($xml_parser)));
   	 		}
		}
	}
	$contents .= "\n";
	$contents .= str_pad(" ", 3)."</body>"."\n";
	$contents .= "</transport>"."\n";
	xml_parser_free($xml_parser);

	//ファイルに文字列が書き込めるか確認
	if (!is_writable($target_dir)) {
	    echo "(1)ディレクトリ書き込みが出来ません: $target_dir";
	    exit;
	}
	//ファイルに文字列を書き込む
	file_put_contents($target_file, $contents);
	$p++;
	echo $p.": "."Succeeded..."."\n";
	sleep(60);
}

function startElement($parser, $name, $attrs) {

    global $inTag;
    global $depth;
    global $contents;
       
    $padTag = str_repeat(str_pad(" ", 3), $depth+2);

    if (!($inTag == "")) {
        $contents .= ">";
    }
  if ($name == 'transport' || $name == 'body'){}
  else {
    $contents .= "\n$padTag<$name";
    foreach ($attrs as $key => $value) {
        $contents .= " $key=\"$value\"";
    }
    $inTag = $name;
    $depth++;
  }
}

function endElement($parser, $name) {

    global $depth;
    global $inTag;
    global $closeTag;
    global $contents;
       
    $depth--;

   if ($name == 'body' || $name == 'transport') {}
   else if ($closeTag == TRUE) {
       $contents .= "</$name>";
       $inTag = "";
   } elseif ($inTag == $name) {
       $contents .= "/>";
       $inTag = "";
   } else {
         $padTag = str_repeat(str_pad(" ", 3), $depth+2);
         $contents .= "\n$padTag</$name>";
    } 
}
 
function contents($parser, $data) {

    global $closeTag;
    global $contents;        

    $data = preg_replace("/^\s+/", "", $data);
    $data = preg_replace("/\s+$/", "", $data);

    if (!($data == ""))  {
        $contents .= ">$data";
        $closeTag = TRUE;
    } else {
        $closeTag = FALSE;
     }
}

function parseDEFAULT($parser, $data) {

    global $contents;
   
    $data = preg_replace("/</", "&lt;", $data);
    $data = preg_replace("/>/", "&gt;", $data);
    $contents .= $data;
}

function pi_handler($parser, $target, $data) {

    global $contents;

    $contents .= "<;?$target $data?>;\n";
}
?>
