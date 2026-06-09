<?
$LOG_URL= $_POST["LOG_URL"];
//$LOG_URL= $_GET["LOG_URL"];
if(substr($LOG_URL, 0, 27) == "/var/archspace/data/battle/") 
{ 
list($temp[1], $temp[2]) = split("/", substr($LOG_URL,27)); //,27,27
echo readfile("/var/archspace/data/battle/".$temp[1]."/".$temp[2]); 
}else 
{ 
die("HACKER!");
} 

?> 


