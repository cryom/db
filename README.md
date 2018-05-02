# vivace\db

_ORM_ with relationship support and a query builder.
## Goals

Create a simply orm with relation and flexible query builder and nothing more.

## Features

- [x] Reading with Filters/Sort/Limit/Skip conditions
- [x] Chunk loading
- [x] Field aliases in Filter, Sort and in result dataset
- [x] Many to many relation with relation via field aliases
- [x] One to many relation with relation via field aliases
- [ ] Deleting with conditions
- [ ] Saving

## Installing

```
composer require vivace/db
```

## Usage

```php
$pdo = new \PDO('your dsn', 'your user', 'your pass');
$pgsql = new vivace\db\sql\Pgsql($pdo);
$mysql = new \vivace\db\sql\Mysql($pdo);

// Next, we define two repositories, the data of which are stored in various repositories
// Pass $driver as first argument and in second argument source table name
$user = new \vivace\db\sql\Storage($pgsql, 'user');
$order = new \vivace\db\sql\Storage($mysql, 'order');

// Now we initialize the finder with the projection.
// Any method from finder which change finder data return a clone of finder
$finder = $user->projection([
    // By this, we tell the finder that he would receive orders from the user
    'orders' => $order->many(['id' => 'user_id'])
]);
// Next we call method "fetch", which create and return data reader.
try {
    $reader = $finder->fetch();
} catch (Exception $e) {
    // well, it's just an alpha version
    // follow by link https://github.com/php-vivace/db/issues/new and ask question
}

// Data reader is iterator, and he not load all data in memory
foreach ($reader as $user) {
    // $user has the following structure, now we can use it to solve our problems
    $user = [
        'id' => 1,
        'orders' => [
            [
                'id' => 1,
                'user_id' => 1,
            ],
            [
                'id' => 2,
                'user_id' => 1,
            ]
        ]
    ];
}

// also we can fetch all data set as array
$users = $reader->all();
// or fetch only first row
$user = $reader->one();
    
```

## Running the tests

For tests, you need to connect to a database.
If you use a docker, then you can raise the database with the following command:
```
docker-compose up -d mysql pgsql
```
And run tests:
```
docker-compose run --rm php72 codecept run --env pgsql --env mysql
```

