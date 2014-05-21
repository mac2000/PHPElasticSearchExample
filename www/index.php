<?php
use Elasticsearch\Client;

require_once __DIR__ . '/../vendor/autoload.php';

$twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/../templates'));
$twig->addExtension(new Twig_Extension_Debug());
$twig->enableDebug();
$client = new Client();

$categories = [];
$ratings = [];
$prices = [];

if(isset($_GET['categories']) && !empty($_GET['categories'])) {
    $categories[] = [
        'term' => [
            'category' => $_GET['categories']
        ]
    ];
}

if(isset($_GET['ratings']) && !empty($_GET['ratings'])) {
    $ratings[] = [
        'term' => [
            'rating' => $_GET['ratings']
        ]
    ];
}

if(isset($_GET['prices'])) {
    if(in_array('0', $_GET['prices'])) {
        $prices[] = [
            'range' => [
                'price' => ['lte' => 1]
            ]
        ];
    }

    if(in_array('1', $_GET['prices'])) {
        $prices[] = [
            'range' => [
                'price' => ['gte' => 1, 'lte' => 3]
            ]
        ];
    }

    if(in_array('2', $_GET['prices'])) {
        $prices[] = [
            'range' => [
                'price' => ['gte' => 3]
            ]
        ];
    }
}


if(empty($ratings) && empty($categories) && empty($prices) && empty($_GET['q'])) {
    $query = ['match_all' => []];
} else {
    if(empty($ratings) && empty($categories) && !empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'or' => $prices
                ]
            ]
        ];
    } elseif(empty($ratings) && !empty($categories) && empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'or' => $categories
                ]
            ]
        ];
    } elseif(empty($ratings) && !empty($categories) && !empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'and' => [
                        ['or' => $categories],
                        ['or' => $prices]
                    ]
                ]
            ]
        ];
    } elseif(!empty($ratings) && empty($categories) && empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'or' => $ratings
                ]
            ]
        ];
    } elseif(!empty($ratings) && empty($categories) && !empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'and' => [
                        ['or' => $ratings],
                        ['or' => $prices]
                    ]
                ]
            ]
        ];
    } elseif(!empty($ratings) && !empty($categories) && empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'and' => [
                        ['or' => $ratings],
                        ['or' => $categories]
                    ]
                ]
            ]
        ];
    } elseif(!empty($ratings) && !empty($categories) && !empty($prices)) {
        $query = [
            'filtered' => [
                'filter' => [
                    'and' => [
                        ['or' => $ratings],
                        ['or' => $categories],
                        ['or' => $prices]
                    ]
                ]
            ]
        ];
    }

    if(isset($_GET['q']) && !empty($_GET['q'])) {
        if(!empty($ratings) || !empty($categories) || !empty($prices)) {
            $query = [
//                'multi_match' => [
//                    'query' => $_GET['q'],
//                    'fields' => ['title'/*, 'description'*/]
//                ],
                'match' => ['title' => $_GET['q']],
                'filtered' => $query['filtered']
            ];
        } else {
            $query = [
                'match' => ['title' => $_GET['q']]
            ];
        }
    }
}

$response = $client->search([
    'index' => 'sakila',
    'type' => 'film',
    'body' => [
        'size' => 5,
        'query' => $query,
        'facets' => [
            'categories' => [
                'terms' => [
                    'field' => 'category'
                ]
            ],
            'ratings' => [
                'terms' => [
                    'field' => 'rating'
                ]
            ],
            'prices' => [
                'range' => [
                    'price' => [
                        ['to' => 1],
                        ['from' => 1, 'to' => 3],
                        ['from' => 3]
                    ]
                ]
            ],
            'price_stats' => [
                'statistical' => [
                    'field' => 'price'
                ]
            ],
            'category_price_stats' => [
                'terms_stats' => [
                    'key_field' => 'category',
                    'value_field' => 'price'
                ]
            ],
            'rating_price_stats' => [
                'terms_stats' => [
                    'key_field' => 'rating',
                    'value_field' => 'price'
                ]
            ]
        ]
    ]
]);

$categories = [];
foreach($response['facets']['categories']['terms'] as $term) {
    $categories[] = [
        'name' => 'categories[]',
        'value' => $term['term'],
        'label' => $term['term'] . ' (' . $term['count'] . ')',
        'selected' => isset($_GET['categories']) && in_array($term['term'], $_GET['categories'])
    ];
}

$ratings = [];
foreach($response['facets']['ratings']['terms'] as $term) {
    $ratings[] = [
        'name' => 'ratings[]',
        'value' => $term['term'],
        'label' => $term['term'] . ' (' . $term['count'] . ')',
        'selected' => isset($_GET['ratings']) && in_array($term['term'], $_GET['ratings'])
    ];
}

$prices = [];
foreach($response['facets']['prices']['ranges'] as $index => $range) {
    if(!$range['count']) continue;
    $from = isset($range['from']) ? $range['from'] : 0;
    $to = isset($range['to']) ? $range['to'] : round($range['max']);
    $prices[] = [
        'name' => 'prices[]',
        'value' => $index,
        'label' => 'from ' . $from . ' to ' . $to . ' (' . $range['count'] . ')',
        'selected' => isset($_GET['prices']) && in_array(strval($index), $_GET['prices'])
    ];
}

echo $twig->render('index.html', [
    'categories' => $categories,
    'ratings' => $ratings,
    'prices' => $prices,
    'items' => array_map(function($hit){
        return $hit['_source'];
    }, $response['hits']['hits']),
    'total' => $response['hits']['total'],
    'took' => $response['took'],
    'price_stats' => $response['facets']['price_stats'],
    'category_price_stats' => $response['facets']['category_price_stats']['terms'],
    'rating_price_stats' => $response['facets']['rating_price_stats']['terms'],
    'q' => isset($_GET['q']) ? $_GET['q'] : '',
    'response' => $response
]);
