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
#### Filtering expressions

```php
// Let's start with comparison operators. Next expression return one user, whose name is "Foo".
$finder = $user->filter(['=', 'name', 'Foo']);

// So instead of equal operator you can use the following operators, which speak for themselves: >,>=,!=, <=, <
$finder = $user->filter(['>=', 'age', 18]);

// Is interpreted as "name = 'Foo' AND age > 40"
$finder = $user->filter(['and', ['=', 'name', 'Foo'], ['>', 'age', 40]]);

// Is interpreted as "name != 'Foo' OR age <= 40"
$finder = $user->filter(['or', ['!=', 'name', 'Foo'], ['<=', 'age', 40]]);

// IN operator. Is interpreted as "name IN('Foo', 'Bar', 'Baz')"
$finder = $user->filter(['in', 'name', ['Foo', 'Bar', 'Baz']]);

// BETWEEN operator. Is interpreted as "age BETWEEN 18 AND 30"
$finder = $user->filter(['between', 'age', 18, 30]);

// Associative array. Is interpreted as "age = 18 AND name = 'Foo'"
$finder = $user->filter(['age' => 18, 'name' => 'Foo']);

// A more complex example of combining expression."
$finder = $user->filter([
    'or',
    ['age' => 19, 'name' => 'Foo'],
    ['age' => 19, 'name' => 'Baz'],
    [
        'and',
        ['between', 'status', 10, 20],
        ['!=', 'is_deleted', true]
    ]
]);
// Adding conditions to the finder. Is interpreted as "(name = 'Foo' AND age = 18) OR is_deleted = true"
$finder = $finder->filter(['name' => 'Foo']);
$finder = $finder->and(['age' => 18]);
$finder = $finder->or(['is_deleted' => true]);
```
#### Sorting and pagination

```php
// Fetch from 20 to 30 rows
$finder = $finder->skip(20)->limit(10);

// Sort by descending of age and ascending status
$finder = $finder->sort(['age' => -1, 'status' => 1]);
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

