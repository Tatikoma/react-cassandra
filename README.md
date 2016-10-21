# React\Cassandra
Performant pure-PHP CQL v4 (Cassandara) async (ReactPHP) library.

Library is not ready for production and much of functional is not implemented yet.

Installation
```
php composer.phar require tatikoma/react-cassandra:dev-master
```

Example usage (async mode):
```php
 require_once 'vendor/autoload.php';
     
 $loop = React\EventLoop\Factory::create();
 
 $cluster = new \React\Cassandra\Async\Cluster($loop, [
     ['host' => '127.0.0.1'],
     ['host' => '127.0.0.2'],
     ['host' => '127.0.0.3'],
     ['host' => '127.0.0.4'],
 ]);
 $cluster->connect('test')->then(function() use($cluster){
     print "Connected to cluster keyspace test\n";
     $uuid = '00000000-0000-0000-0000-000000000000';
     return $cluster->query('
        SELECT *
        FROM example
        WHERE id = :id
     ',[
        'id' => new \React\Cassandra\Type\UUID($uuid),
     ]);
 })->then(function($response){
     print "Query successfull, got " . count($response->results) . " rows:\n";
     foreach($response as $row){
        var_dump($row);
     }
 });
 
 $loop->run();
```


Example usage (sync mode):
```php
 require_once 'vendor/autoload.php';
      
 $cluster = new \React\Cassandra\Cluster([
     ['host' => '127.0.0.1'],
     ['host' => '127.0.0.2'],
     ['host' => '127.0.0.3'],
     ['host' => '127.0.0.4'],
 ]);
 $cluster->connect('test');
 $uuid = '00000000-0000-0000-0000-000000000000';
 $response = $cluster->query('
     SELECT *
     FROM example
     WHERE id = :id
     ',[
        'id' => new \React\Cassandra\Type\UUID($uuid),
 ]);
 print "got " . count($response->results) . " rows:\n"; 
 foreach($response as $row){
    var_dump($row);
 }
```