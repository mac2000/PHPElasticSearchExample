<?php
use Elasticsearch\Client;

header('Content-Type: application/json');
$q = isset($_GET['term']) && !empty(strval($_GET['term'])) ? strval($_GET['term']) : null;

$q = 'п';
if(!$q) {
    echo '[]';
    return;
}

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Client();

$response = $client->suggest([
    'index' => 'sakila',
    'body' => [
        'sakila' => [
            'text' => $q,
            'completion' => ['field' => 'autocomplete_suggest']
        ]
    ]
]);

//$i = $client->transport->getLastConnection()->getLastRequestInfo();
//print_r($i);

/*curl -X POST localhost:9200/sakila/_suggest -d '
{
  "sakila" : {
    "text" : "ap",
    "completion" : {
      "field" : "autocomplete_suggest"
    }
  }
}'
*/

$options = array_map(function($option){
    return $option['text'];
}, $response['sakila'][0]['options']);

echo json_encode($options);