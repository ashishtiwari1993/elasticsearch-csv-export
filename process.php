<?php

include 'Export.php';

#Include PHP-Elasticsearch sdk's 'autoload.php' here
require '/yourpath/elasticsearch-php-sdk/vendor/autoload.php';

$op = array("host:", "index:","type:","query:","stm:","fields:", "size:", "csvfile:", "logfile:", "help:");
$option = getopt(null,$op);
$requiredFields = array("host","index","fields","csvfile");

if(array_key_exists('help',$option)){
    echo file_get_contents('help.txt');
    exit;
}

foreach($requiredFields as $key => $value){
    if(array_key_exists($value,$option)){
        if(empty($option[$value])){
            echo "Please provide argument --".$value."\n";exit;
        }
    }else{
        echo "Please provide argument --".$value."\n";exit;
    }
}

$e = new Export;
foreach($option as $key => $value){
    $e->$key = $value;
}

$e->connect();
$e->queryParam();
$e->fetchDataWriteCSV();

