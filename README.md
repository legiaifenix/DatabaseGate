#Legiai Fenix DatabaseGate

**Version:** 1.0
**Frameworks:** Laravel 5.*
**License:** MIT

**Supported functionality:** select, sum, count, paginate, join, leftJoin, where, group,

This is a small package to help boost laravel development. Its objective is to simplify the use of database tables in
order to minimize the written code and create a visual way to help developers understand the queries they are building.
Through it they can call in any service/controller they desire and make use of the entire database with just one class.

You will be creating arrays with keys that will navigate through your DB and fetch the desire information

##Geting total products in products table
```
    $conditions = [
        'count' => '*'
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);
```

##Getting products for each client
Lets assume you got the products in "products" table and clients in "clients" table;

```
    $conditions = [
        'select' => [
            ['clients.name'],
            ['clients.email'],
            ['products.*']
        ],
        'join' => [
            'products' => ['products.client_id', '=', 'clients.id']
        ]
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('clients', $conditions);
```

##If you wish to paginate your queries by 5 per page
```
    $conditions = [
        ...
        'paginate' => '5'
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('clients', $conditions);
```

##Fetching products only for clients named "Mark" and keep pagination for 15 per page
```
    $conditions = [
        'select' => [
            ['clients.name'],
            ['clients.email'],
            ['products.*']
        ],
        'join' => [
            'products' => ['products.client_id', '=', 'clients.id']
        ],
        'where' => [
            ['clients.name', 'LIKE', 'Mark%']
        ],
        'paginate' => '15'
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('clients', $conditions);
```

##Getting all products that are visible
When you target only one table (what I like to consider the main table for all searches) you dont need to specify the select
unless you just need a few columns from it. It depends of the value and columns you use for your logic.

```
    $conditions = [
        'where' => [
            ['products.visible', '=', '1']
        ]
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);
```

##Sum the cost of all products of the client with "example@email.com" and get their email
We will be adding all the products price of the client with the "example@email.com" email
and name that column "totalCost" to use it in the views

```
    $conditions = [
        'select' => [
            ['clients.email'],
            ['( sum(products.price) )', 'totalCost']
        ],
        'join' => [
            'clients' => ['clients.id', '=', 'products.client_id']
        ],
        'where' => [
            ['clients.email', '=', 'example@email.com']
        ]
    ];

    $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);
```

In the view we can use the object as:
```
    Client Email: {{$results->email}}
    Total Cost: {{$results->totalCost}}
```

##If you wish just to have the cost you dont need to provide the select, you can deploy your array of conditions to just
return the added value

```
    $conditions = [
            'join' => [
                'clients' => ['clients.id', '=', 'products.client_id']
            ],
            'where' => [
                ['clients.email', '=', 'example@email.com']
            ],
            'sum' => 'products.price'
        ];

    $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);
```
