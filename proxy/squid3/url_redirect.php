#!/usr/bin/php

 <?php
 $proxy = "192.168.0.53";
 $temp = array();

 while ( $input = fgets(STDIN) ) {
   // Split the output (space delimited) from squid into an array.
   $temp = split(' ', $input);
 
   // Set the URL from squid to a temporary holder.
   $output = $temp[0] . "\n";
 
   // Clean the Requesting IP Address field up.
   $ip = split('/',rtrim($temp[1], "/-"));
 
   // Test Process
   if (preg_match("/^http:\/\/google.com/i", $temp[0])) {
     $output = "301:http://www.yahoo.co.jp\n";
   }

   // Merge Process
    if (preg_match("/^http:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/axis2/services/FIAPStorage?wsdl/i", $temp[0])) {
     $output = "301:http://".$proxy."/axis2/services/FIAPStorage?wsdl\n";
   }

 echo $output;
} 

