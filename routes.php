<?php

//#########################################################
//A reverse proxy script writen for php
// Author: facat 
// Email: boost.subscribing at gmail.com
// Version: 1.0
// Date: 2014-08-11


// Features:
// 1. open source
// 2. support both GET and POST
// 3. handle cookie and http header

// not completed:
// 1. not support https 
// 2. no timeout retry
//#########################################################


//########
//set this to you  desired host.
//for example. if you want http://yourhost.com/test to be proxied by 
//http://newhost.com/test, just set $new_url='http://yourhost.com'
$new_url='YOUR_HOST';
//########
//extract headers from a string. header is in the name:value format.
function splitHeader($strHeader){
    $sep=explode("\r\n", $strHeader);
    return $sep;
}

//simulate getallheaders function, becuase nginx doesn't have this function.
//this code if from http://php.net/manual/zh/function.getallheaders.php
if (!function_exists('getallheaders')) 
{ 
    function getallheaders() 
    { 
           $headers = ''; 
       foreach ($_SERVER as $name => $value) 
       { 
           if (substr($name, 0, 5) == 'HTTP_') 
           { 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } else if ($name == "CONTENT_TYPE") { 
               $headers["Content-Type"] = $value; 
           } else if ($name == "CONTENT_LENGTH") { 
               $headers["Content-Length"] = $value;
            } 
       } 
       return $headers; 
    } 
} 

//header to curl shoud be in name:value format. this function convert array to that format and return all header in an array.
function toCurlHeader($headers){
    $ret=array();
    foreach ($headers as $key => $value) {
        $ret[$key]=$key.":".$value;
    }
    return $ret;
}

//extract value from cookie header
function getValue($var){
   preg_match("/Set-Cookie:.*?=(.*?);/is",$var,$restr); 
   if(count($restr)>=2){
    return $restr[1]; 
   }
   return "";
   
}

//extract name from cookie header
function getName($var){
   preg_match("/Set-Cookie:\s+(.*?)=.*?;/is",$var,$restr); 
   if(count($restr)>=2){
    return $restr[1]; 
   }
   return "";
   
}

//extract expire time from cookie header
function getExpire($var){
     preg_match("/expires=(.*);/i",$var,$restr);  
   if(count($restr)>=2){
    return (int)$restr[1]; 
   }
   return 0;
}

//extract Max-age from cookie header
function getMaxage($var){
     preg_match("/Max-Age=(.*);/i",$var,$restr); 
   if(count($restr)>=2){
    return $restr[1]; 
   }
   return "";
}

//extract path from cookie header
function getPath($var){
     preg_match("/path=(.*);?/i",$var,$restr); 
   if(count($restr)>=2){
    return $restr[1]; 
   }
   return "";
}


$cookie="";

//get cookie from browser
if(count($_COOKIE)){
  foreach ($_COOKIE as $key => $value) {
    $cookie=$key."=".$value.";".$cookie;
  }
}
$cookie=urlencode($cookie);

$req_url=substr ($_SERVER['REQUEST_URI'],23,strlen($_SERVER['REQUEST_URI']));
$url = $new_url.$req_url; 

$ch = curl_init(); 
$timeout = 30; 
curl_setopt($ch, CURLOPT_URL, $url); 
if($_SERVER['REQUEST_METHOD']=="POST"){
  curl_setopt($ch, CURLOPT_POSTFIELDS,file_get_contents("php://input"));
}
if(count($_COOKIE)){
  curl_setopt($ch,CURLOPT_COOKIE,$cookie);
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$curlHeader=toCurlHeader(getallheaders());
curl_setopt($ch,CURLOPT_HTTPHEADER,$curlHeader );
// curl_setopt($ch, CURLOPT_HEADER, 1);

$contents = curl_exec($ch); 
curl_close($ch);

// echo print_r($contents);
// return;

header('Content-Type: application/json;charset=UTF-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: DENY');
header('Keep-Alive: timeout=60');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,PUT,DELETE');
header('Access-Control-Allow-Headers: Authorization, Lang');
echo $contents;//this is the body.
