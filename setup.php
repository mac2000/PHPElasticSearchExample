<?php
use Doctrine\DBAL\DriverManager;
use Elasticsearch\Client;

require_once 'vendor/autoload.php';

echo 'Connecting to ElasticSearch' . PHP_EOL;
$client = new Client();

echo 'Connecting to DataBase' . PHP_EOL;
$conn = DriverManager::getConnection([
    'dbname' => 'sakila',
    'user' => 'root',
    'password' => 'root',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'port' => 3306,
    'charset' => 'UTF8',
    'driverOptions' => [
        'charset' => 'UTF8'
    ]
]);

$response = $client->indices()->stats();
if(in_array('sakila', array_keys($response['indices']))) {
    echo 'Deleting previously created sakila index' . PHP_EOL;
    $client->indices()->delete(['index' => 'sakila']);
}
echo 'Creating sakila index' . PHP_EOL;
$client->indices()->create([
    'index' => 'sakila',
     'body' => [
     	'mappings' => [
     		'film' => [
     			'properties' => [
     				'id' => ['type' => 'integer'],
     				'category' => ['type' => 'string', 'index' => 'not_analyzed'],
     				'rating' => ['type' => 'string', 'index' => 'not_analyzed'],
     				'price' => ['type' => 'float'],
                    'autocomplete_suggest' => ['type' => 'completion']
     			]
     		]
     	]
     ]
]);

echo 'Insert records from db to es' . PHP_EOL;
$stmt = $conn->query('SELECT FID AS id, title, description, category, price, rating FROM film_list');
while($film = $stmt->fetch()) {
    $film['autocomplete_suggest'] = $film['title'];
    $response = $client->index([
        'body' => $film,
        'index' => 'sakila',
        'type' => 'film',
        'id' => $film['id']
    ]);
    echo $response['_id'] . ' film added' . PHP_EOL;
}

//$response = $client->index([
//    'body' => [
//        'id' => 10000,
//        'category' => 'категория',
//        'rating' => 'рейтинг',
//        'price' => 10,
//        'title' => 'Привет мир',
//        'description' => 'Здесь был саня',
//        'autocomplete_suggest' => 'Привет мир'
//    ],
//    'index' => 'sakila',
//    'type' => 'film',
//    'id' => 10000
//]);

echo 'Done' . PHP_EOL;