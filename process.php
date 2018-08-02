<?php

include 'Export.php';
#Include PHP-Elasticsearch sdk's 'autoload.php' here
require '/var/www/html/elastic-csv-export/vendor/autoload.php';

$op = array("host:", "index:", "fields:", "csvfile:", "type:", "size:", "query:", "stm:", "logfile:", "async:", "help::");
$option = getopt(null,$op);
$requiredFields = array("host","index","fields","csvfile");

if(array_key_exists('help',$option)){
    echo file_get_contents('help.txt');
    exit;
}

#Validating manadotary fields
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


$logfile = null;
if(!empty($option['logfile'])){
    $logfile = fopen($option['logfile'],"w");
    $e->logfile = $option['logfile'];
    if(!$logfile){
        echo "Check with your logfile."; exit;
    }
}

$e->connect();
$e->queryParam();

$csvfile = fopen($option['csvfile'],"w");

if($csvfile){
    $e->csvFileObject = $csvfile;
    fputcsv($csvfile,explode(',',$option['fields']));
}else{
    echo "Check with your csvfile";
}

if(array_key_exists('async', $option)){
    if(!empty($option['async'])){
        $max = $option['async'];
        for($i = 0; $i < $max; $i++){
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit("Error forking...\n");
            }
            else if ($pid == 0) {
                $e->fetchDataWriteCSV($i, $max);
                exit();
            }
        }
    }else{ echo "Kindly provide --async argument."; }
}else{
    $e->fetchDataWriteCSV();
}
