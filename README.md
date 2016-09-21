# ReactCassandra
Performant pure-PHP CQL (Cassandara) async (ReactPHP) library

Example usage:
```php
require_once 'vendor/autoload.php';
     
 $loop = React\EventLoop\Factory::create();
 
 $cluster = new \ReactCassandra\Async\Cluster($loop, [
     ['host' => '127.0.0.1'],
     ['host' => '127.0.0.2'],
     ['host' => '127.0.0.3'],
     ['host' => '127.0.0.4'],
 ]);
 $cluster->connect('smpp')->then(function( use($cluster)){
     print "Connected to cluster keyspace smpp\n";
     $uuid = '00000000-0000-0000-0000-000000000000';
     return $cluster->query('
        SELECT *
        FROM example
        WHERE id = :id
     ',[
        'id' => new \ReactCassandra\Type\UUID($uuid),
     ]);
 })->then(function($response){
     print "Query successfull, got " . count($response->results) . " rows:\n";
     foreach($response as $row){
        var_dump($row);
     }
 });
 
 $loop->run();
```