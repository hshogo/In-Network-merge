<?php 
$storageIP = "192.168.0.56";
$i = 1;

while(1) {
// Prepare Historical Times
$cur_time=intval(time()/60)*60;
$past0=date("Y-m-d\TH:i:sP",$cur_time);

// Prepare Points and Values
$points = array();

$values= array();
$values[0]=array("time"=>$past0, "_" => strval(mt_rand(20,30)*1.0+mt_rand(0,9)/10.0));
$points[0]=array("id"=>"http://jo2lxq.hongo.wide.ad.jp/experiment3/Temperature",
				  "value" => $values);

$values= array();
$values[0]=array("time"=>$past0, "_" => strval(mt_rand(500,599)));
$points[1]=array("id"=>"http://jo2lxq.hongo.wide.ad.jp/experiment3/CO2",
				  "value" => $values);
  
$values= array();
$values[0]=array("time"=>$past0, "_" => strval(mt_rand(5000,7000)*1.0+mt_rand(0,9)/10.0));
$points[2]=array("id"=>"http://jo2lxq.hongo.wide.ad.jp/experiment3/Power",
				  "value" => $values);

// Summarize into PointSet
$pointSet=array("id"=>"http://jo2lxq.hongo.wide.ad.jp/experiment3/",
				"point" => $points);

// Construct Body and Transport
$body=array("pointSet"=>$pointSet);
$transport=array("body"=>$body); 
$dataRQ=array("transport"=>$transport); 
  
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

if(!array_key_exists("OK",$header)){
   if(!array_key_exists("error",$header)){
      echo "Error occured -- neither OK nor error presented in the header.";
      exit;
   }
   echo "Error:".$header->error->_;
   exit;
} 

echo $i++.": "."Succeeded..."."\n";
sleep(60);
}
  
?>

