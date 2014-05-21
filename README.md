PHPElasticSearchExample
=======================

PHP ElasticSearch Sample Project

https://gist.github.com/svartalf/4465752

# Suggest requests example

    curl -X POST localhost:9200/sakila/_suggest -d '
    {
      "sakila" : {
        "text" : "ap",
        "completion" : {
          "field" : "autocomplete_suggest"
        }
      }
    }'