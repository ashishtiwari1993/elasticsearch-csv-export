<?php

use Elasticsearch\ClientBuilder;

class Export{

    public  $host, $fields, $query, $stm = 30, $size = 100, $logfile, $csvFileObject;
    private $es, $header = array(), $paramm, $recordsToWrite, $fieldsArray, $totalRecords, $recordsProcessed = 1, $batchWrite = 5;

    function connect()
    {
        try{

            $hosts = [
                'host' => $this->host,    
            ];
            $client = ClientBuilder::create()           
                ->setHosts($hosts)      
                ->build();              
            $this->es = $client;
            $this->log("Connected successfully ...");

        }catch(exception $e){
            echo "Error while connecting to Elasticsearch ..";
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
                $paramBuild['body'] = json_decode($this->query,true);
            }

            $this->param = $paramBuild;

        }catch(Exception $e){
            $this->log($e);
        }
    }

    function fetchDataWriteCSV($id = null ,$max = null)
    {
        try{

            if(!$this->csvFileObject){
                $this->log("Check with your csvfile."); exit;
            }

            if(!empty($id) && !empty($max)){
                $this->param['body']['slice']['id'] = $id;
                $this->param['body']['slice']['max'] = $max;
            }

            $response = $this->es->search($this->param);

            $i = 1;

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

        }catch(Exception $e){
            $this->log($e);
        }
    }

    function processRecords($response)
    {
        foreach($response['hits']['hits'] as $key => $records){
            $this->recordsToWrite[] = $records['_source'];
            $this->recordsProcessed++;
        }
    }

    function writeFile()
    {
        if(!empty($this->recordsToWrite)){
            foreach($this->recordsToWrite as $rec){

                $row = array_replace($this->fieldsArray,$rec);
                fputcsv($this->csvFileObject,$row);   
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
}

?>
