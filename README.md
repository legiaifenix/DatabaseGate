#Legiai Fenix DatabaseGate

**Version:** 1.0
**Frameworks:** Laravel 5.*
**License:** MIT

**Supported functionality:** select, join, leftJoin, where, orWhere, whereBetween, group, sum, count, paginate, delete, update, insert

**Future Support:** PDO

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

##Left Joins

Thanks to Joao Cunha I was able to remember that LEft Joins are missing. So here goes a new implementation of it.
They work exactly like right joins, but they are left. Mind blow.
Left joins will fetch the conjunction regardless if there are values or not. While right joins will only fetch rows that do not miss
in their search. We are not oblige to own rows every time we do a search, right?

Here we go:

```
    $conditions = [
            'select' => [
                ['clients.name'],
                ['clients.email'],
                ['products.*']
            ],
            'leftJoin' => [
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

V.0.1.3 included a debug for select queries. This are the ones that can get more complex and not totally straight forward. 
There for the necessity to debug them is immense. To start debugging them you can just add a true as boolean at the end of the calls.

Ex:. 
**Condition example**
```
                $conditions = [
                    'select' => [
                        ['colors.*']
                    ],
                    'order' => ['color_name', 'ASC']
                ];
```

```
    $results = $this->databaseGate->getEntriesOfTableWithConditions('colors', $conditions, true);
    var_dump($results);
```

**Output**
```
    select colors.* from `colors` order by `color_name` asc
```

**Debug example with joins**
```
            $conditions = [
                'select' => [
                    ['products_colors.*'],
                    ['colors.*']
                ],
                'joins' => [
                    'colors' => ['colors.id', '=', 'products_colors.color_id']
                ],
                'where' => [
                    ['colors.color_name', '=', 'test']
                ],
                'order' => ['color_name', 'ASC']
            ];
```

**Output**
```
    select products_colors.*,colors.* from `products_colors` where `colors`.`color_name` = ? order by `color_name` asc
```

#Delete Query

if you wish to delete entries from your database, you sure know that Laravel provides that option and even more!
For the time being I just allowed to delete permanently. Later intend to add the soft deletes.
 
 **Condition example**
```
            $conditions = [
                'delete' => [
                    'permanent'    
                ],
                'where' => [
                    ['col', '=', '<title>']
                ]
            ];     
```

The delete is already an array to accommodate the later changes which will have the soft deletes.
For now use it as it is displayed.

#Inserts

This insert will return the new id inserted in the database. So you can have an object or variable expecting it.

**Condition example**
```
            $conditions = [
                'insert' => [
                    'title' => 'test', 
                    'price' => '33.33', 
                    'other col here' => 'other value here' 
                ]
            ];     
```

```
    $id = $this->databaseGate->getEntriesOfTableWithConditions('<table name>', $conditions);
    var_dump($results);
```

#Update Information

```
        $conditions = [
            'update' => [
                'title' => 'Title1',
                'desc' => 'description here'
            ],
            'where' => [
                ['title', '=', 'what I want to change']
            ]
        ];
       
        $this->databaseGate->getEntriesOfTableWithConditions('table name', $conditions);
```
 
