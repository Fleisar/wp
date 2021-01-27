<?php
require "core.php";
$cms = new wp();
if($cms==false) error();
$file = $cms->path.str_replace(["..","//"],["","/"],$_GET[$cms->pool]);
if(substr($file,-1) == "/")
    if(file_exists($file."index.html"))$file.="index.html";
    else $file.="index.php";
$cms->presets["CMS-URI"] = $file;
if(!file_exists($file))if(!$cms->call("pageNotFound")) error();
close($file);
function close($file){
    global $cms;
    require $file;
}
function error(string $text = "Unable to process your request.",int $code = 500,$debug = ""): void{
    global $cms,$file;
    $error = $cms->lastError;
    echo "${text}<hr/><b>Error: </b>${error}<br/><b>Request: </b>${file}";
    if($debug!=""){
        echo "<pre style='background:#f0f0f0;padding:10px;border:1px solid #555'>";
        var_dump($debug);
        echo "</pre>";
    }
    exit($code);
}
