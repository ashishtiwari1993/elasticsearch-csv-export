# elasticsearch-csv-export 

## CLI script for CSV export from ES
A script which written in PHP, Will query in Elasticsearch and fetch the bulk data with help of the Scroll API. We can specify query in **Query DSL** syntax . Script does bulk write to the CSV file with the selected fields. It will export your data asynchronously. It will fetch data by forking child process and run them parallely. There is also class file **Export.class.php**, Which you can easily integrate with your custom script or application. It requires official **Elasticsearch-PHP** SDK to run.

## Requirements
* PHP version >= 5
* [Elasticsesarch-PHP SDK](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html) - You can choose the version according to your ES.

## Installation
* Install PHP-Elasticsearch SDK as mention [here](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/installation.html) . You can find doc according to your ES versions Also check version compatibility from [here](https://github.com/elastic/elasticsearch-php)
* ```git clone https://github.com/ashishtiwari1993/elasticsearch-csv-export.git``` .
* Include PHP-Elasticsearch sdk's ```/vendor/autoload.php```  in ```process.php```. Like  
```require '/change-with-PHP-Elasticsearch-SDK-directory-path/vendor/autoload.php';```

## Usage

```
php process.php [--host HOSTNAME:PORT] [--index INDEX] [--type TYPE]
		        [--query  QUERY] [--stm  TIMEOUT] [--fields FIELD1,FIELD2]
		        [--size SIZE] [--csvfile CSVPATH] [--logfile LOGPATH] [--async NUMBER_OF_SLICE]  
		        
Optional argument

--host      HOST:PORT       Elasticsearch hostname with port e.g. example.com:9200             [required]
--index     NAME            Provide multiple Index name with comma seperated.                  [required]
--type      TYPE            Specified type
--query     QUERY           Query string in Lucene syntax    
--stm       TIME            Value in seconds to open search context in Scroll API. Default
                            it set to 30.                                                      
--fields    field1,field2   Specify Field need to fetch with comma sperated.
			    It won't work for nested fields.				       [required] 
--size      SIZE            Per scroll api how much data should fetch in one call. By Default
                            it is set to 100.                                                     
--csvfile   CSVPATH         Path to csv file where to export.                                  [required]                         
--logfile   LOGFILEPATH     Path to log file where log will be write. 
--async	    2		    The maximum number of slices for scroll API. It will fork same
			    Number of child process.
```
## NOTE
* It is using Scroll API. You can find more [here](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html) on Scroll APIs.
* You can specify ```--query``` using [Query string](https://www.elastic.co/guide/en/elasticsearch/reference/6.3/query-dsl-query-string-query.html#query-string-syntax) OR [Query DSL](https://www.elastic.co/guide/en/elasticsearch/reference/6.3/query-dsl.html) .It should be same as when you define ```query``` param in POST call when query to elasticsearch via curl. Check here for [example](https://www.elastic.co/guide/en/elasticsearch/reference/6.3/query-filter-context.html).
* For nested fileds you need to specify nested field value like ```field.key``` in `--field` param. For example with this structure ```{info:[{name:php}]}``` , I can access my key ```name``` with this way ```info.name``` .
* For ```--async``` request, We using [```pcntl_fork```](http://php.net/manual/en/function.pcntl-fork.php) to forking child processes. It will fetch all data asynchronously irrespective of any order or sorting.

## Example
```php process.php --host 'localhost:9200' --index 'myindex1,myindex2' --type 'logs' --fields 'balance,firstname,gender,state,city' --stm 60 --size 500 --query '{"query":{"match":{"gender":"M"}}}' --csvfile '/home/ashish/records.csv' --logfile '/tmp/b.log' --async 2```

## License
This project is licensed under the MIT License - see the LICENSE file for details
