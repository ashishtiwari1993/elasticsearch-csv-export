<?php

use Elasticsearch\ClientBuilder;

class Export{

    public  $host, $fields, $query, $stm = 30, $size = 100, $csvfile, $logfile;
    private $es, $header = array(), $paramm, $fo, $recordsToWrite, $fieldsArray, $totalRecords, $recordsProcessed = 1, $batchWrite = 5;

    function connect()
    {
        try{

            if(!empty($this->logfile)){
                $this->lofo = fopen($this->logfile,"w");
                if(!$this->lofo){
                    echo "Check with your logfile."; exit;
                }
            }

            $hosts = [
                'host' => $this->host,    
            ];
            $client = ClientBuilder::create()           
                ->setHosts($hosts)      
                ->build();              
            $this->es = $client;
            $this->log("Connected successfully ...");

        }catch(exception $e){
            $this->log($e);
        }
    }

    function queryParam()
    {
        try{

            $params = array();
            $query = json_decode($this->query,true);

            $select = explode(',',$this->fields);
            $keys = array_flip($select);
            $this->fieldsArray = array_fill_keys(array_keys($keys),"");

            $paramBuild = [
                "_source" => $select,
                "scroll" => $this->stm."s",
                "size" => $this->size,               
                "index" => $this->index,
            ];

            if(!empty($this->type)){
                $paramBuild['type'] = $this->type;
            }

            if(!empty($this->query)){
                $paramBuild['body'] = $this->query;
            }

            $this->param = $paramBuild;

        }catch(Exception $e){
            $this->log($e);
        }
    }

    function fetchDataWriteCSV()
    {
        try{

            $this->fo = fopen($this->csvfile,"w");
            if(!$this->fo){
                $this->log("Check with your csvfile."); exit;
            }

            $response = $this->es->search($this->param);

            $i = 1;

            fputcsv($this->fo,explode(',',$this->fields));

            while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {

                if($i == 1){
                    $this->log("Found total records = ".$response['hits']['total']);
                    $this->totalRecords = $response['hits']['total'];
                }

                $this->processRecords($response);

                if($i % $this->batchWrite == 0){
                    $this->writeFile();
                }

                $scroll_id = $response['_scroll_id'];
                $response = $this->es->scroll([
                    "scroll_id" => $scroll_id,           
                    "scroll" => $this->stm."s"           
                ]);
                $i++;
            }

            $this->writeFile();
            fclose($this->fo);

        }catch(Exception $e){
            $this->log($e);
        }
    }

    function processRecords($response)
    {
        foreach($response['hits']['hits'] as $key => $records){
            $this->recordsToWrite[] = $records['_source'];
        }
    }

    function writeFile()
    {
        if(!empty($this->recordsToWrite)){
            foreach($this->recordsToWrite as $rec){

                $row = array_replace($this->fieldsArray,$rec);
                fputcsv($this->fo,$row);   
                $this->recordsProcessed++;
                $this->progress_bar($this->recordsProcessed, $this->totalRecords, "Progress");
            }   
        }
        $this->recordsToWrite = array();
    }

    function log($log)
    {
        if(!empty($log)){
            if(!empty($this->logfile)){
                file_put_contents($this->logfile, date("y-m-d H:i:s")." $log \n", FILE_APPEND | LOCK_EX);
            }else{
                echo date("y-m-d H:i:s")." $log \n";
            }    
        }    
    }

    function progress_bar($done, $total, $info="", $width=50) 
    {
        $perc = round(($done * 100) / $total);
        $bar = round(($width * $perc) / 100);
        echo sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
    }    
}

?>
