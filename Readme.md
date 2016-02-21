# Plugin for BackedSearch via Elasticsearch

## Create Mapping for object classes

Per object class one index with one document type.
Sample script to delete index, create index, set mapping for object type.
Deleting index might be necessary since update mapping is not always possible.

```
<?php
$client = \ESBackendSearch\Plugin::getESClient();
$service = new ESBackendSearch\Service();

$classes = ["Product", "Customer"];

foreach($classes as $class) {

    $response = $client->indices()->delete(["index" => strtolower($class)]);
    p_r($response);


    $response = $client->indices()->create(["index" => strtolower($class)]);
    p_r($response);


    $mapping = $service->generateMapping(\Pimcore\Model\Object\ClassDefinition::getByName($class));
    $response = $client->indices()->putMapping($mapping);
    p_r($response);

    $params = [
        'index' => strtolower($class),
        'type' => $class
    ];
    $response = $client->indices()->getMapping($params);
    p_r($response);

}
```


## Update index data

on object save or via script:
```
<?php
$service = new ESBackendSearch\Service();

$objects = \Pimcore\Model\Object\Product::getList();
foreach($objects as $object) {
    $service->doUpdateIndexData($object);
}

```


## Search/Filter for data

```
<?php
$service = new ESBackendSearch\Service();

//filter for relations via ID
$results = $service->doFilter("Product",
    [
        "objects" => (object)[
            "type" => "object",
            "id" => 75
        ],

    ]
);



//filter for relations via sub query
$results = $service->doFilter("Product",
    [
        "objects" => (object)[
            "type" => "object",
            "className" => "Customer",
            "filters" => [
                "firstname" => "tom"
            ]
        ],

    ],
    ""
);


// full text search query without filters
$results = $service->doFilter("Product",
    [],
    "sony"
);


// filter for several attributes - e.g. number field, input, localized fields
$results = $service->doFilter("Product",
    [
        /*"price" => (object)[
            "gte" => 50.77,
            "lte" => 50.77
        ],*/
        /*"price" => 50.77,*/
        "price" => [
            50.77,
            (object)[
                "gte" => 50.77,
                "lte" => 50.77
            ]
        ],
        "keywords" => "test2",
        "localizedfields" => (object)[
            "en" => [
                "locprice" => [
                    55,
                    (object)[
                        "gte" => 53
                    ]
                ],
                "locname" => "englname"
            ],
            "de" => [
                "locname" => "deutname"
            ]
        ],

    ],
    ""
);

```
