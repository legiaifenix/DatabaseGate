#Legiai Fenix DatabaseGate

**Version:** 1.0
**Frameworks:** Laravel 5.*
**License:** MIT

**Supported functionality:** select, join, leftJoin, where, orWhere, whereBetween, group, sum, count, paginate

**Future Support:** Delete, Update, Insert clauses

This is a small package to help boost laravel development. Its objective is to simplify the use of database tables in
order to minimize the written code and create a visual way to help developers understand the queries they are building.
Through it they can call in any service/controller they desire and make use of the entire database with just one class.

You will be creating arrays with keys that will navigate through your DB and fetch the desire information

##Instalation
To start using the package, two steps are needed.

**1.** Require the package in CMD by running following command:

```
    composer require legiaifenix/databasegate
```

Or add it to your composer.json file in the "required" field:

```
    "require": {
            ...
            "legiaifenix/databasegate": "*"
        },
```

**2.** To start using it in your Controllers/Services/Classes just inject the class where you need it!

```
    public function reallyNeedToSortThis(DatabaseService $databaseService)
    {
        return $databaseService->getEntriesOfTableWithConditions($yourDesiredTable, $yourConditions);
    }
```

See below documentation to know how to access your table and sort your searches!



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

##Sum all that info!

If you wish just to have the cost you don't need to provide the select, you can deploy your array of conditions to just
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

##Need prices between 30 and 70!

Well no problem, let us start by the example.

```
    $conditions = [
                'join' => [
                    'clients' => ['clients.id', '=', 'products.client_id']
                ],
                'where' => [
                    ['clients.email', '=', 'example@email.com']
                ],
                'whereBetween' => [
                    'price' => ['30', '70']
                ],
                'sum' => 'products.price'
            ];

        $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);

```

Just like the Joins, you can add more arrays to specify even more where between clauses. The header that guides the arrays of values
are, of course, the col names that own the comparison.

##What about Having and its rainbow magic?!

Chill my friend, having is inserted, as well, to save the day!

Here is an example of how you can use it to save your day! It definitely saved mine!

```
    $conditions = [
                    'join' => [
                        'clients' => ['clients.id', '=', 'products.client_id']
                    ],
                    'where' => [
                        ['clients.email', '=', 'example@email.com']
                    ],
                    'having' => [
                        ["sum(orders.cost) = $value or sum(orders.q) = $value"]
                    ],
                    'sum' => 'products.price'
                ];

            $results = $databaseGate->getEntriesOfTableWithConditions('products', $conditions);

```

Yes you guess it well! It is only supporting a Raw Having query, but is not like it is so hard it broke your fellings, right?
Anyhow I will be adding the queries outputs later on to help you debug them if needed or creating a deeper understanding of
how does it exactly works.

#Custom Pagination

You can implement your own target of rows through a custom pagination. Is not that different from pagination itself.
All you have to do is substitute the single value by an array of 2 values, being them [skip, amount].

So, let us simulate we want 25 results corresponding to page 3:

```
    $conditions = [
            ...
            'pagination' = ['3', '25']
            ];

```

That is it! Enjoy your custom pagination methods! Of course try to implement the page and amount of results in a dynamic way, not static as this example.
Damm who does this examples?

##Debug my queries

Okay I know this is a sensible field, so I am implementing query debugging slowly, but surely!
For now we are able to see almost the entire query until it reaches the final statements like