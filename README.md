# vivace\db

[![Latest Stable Version](https://poser.pugx.org/vivace/db/v/stable)](https://packagist.org/packages/vivace/db)
[![Total Downloads](https://poser.pugx.org/vivace/db/downloads)](https://packagist.org/packages/vivace/db)
[![License](https://poser.pugx.org/vivace/db/license)](https://packagist.org/packages/vivace/db)
[![composer.lock](https://poser.pugx.org/vivace/db/composerlock)](https://packagist.org/packages/vivace/db)
[![Maintainability](https://api.codeclimate.com/v1/badges/996b9318332fb25f58e0/maintainability)](https://codeclimate.com/github/php-vivace/db/maintainability)
## Goals

Create a simply orm with relationship support and flexible query builder and nothing more.

## Requirements
- php __>= 7.1__

## Supported databases

- [x] PostgreSQL __>=9.5__
- [x] MySQL __>= 5.7__

## Installing

```
composer require vivace/db
```

## Usage

Initialize driver for your database. In this example, the driver for postgresql.
```php
$pdo = new \PDO('dsn', 'user', 'pass');
$driver = new \vivace\db\sql\PostrgeSQL\Driver($pdo);
```

Initialize storage objects.
```php
$userStorage = new \vivace\db\sql\Storage($driver, 'users');
```
Now you can use created storages for data manipulation.


Save the data to your storage.
```php
$ok = $userStorage->save(['name' => 'Zoe Saldana', 'career' => 'actor', 'rating' => 4.95]);
```
Let's try fetch saved data from storage.
```php
$user = $userStorage->filter(['name' => 'Zoe Saldana'])->fetch()->one();
// $user is simple assoc array.
var_dump($user);
```

Now it's time to change the data

```php
$user['age'] = 39;
```
And save changes in storage.

```php
$ok = $userStorage->save($user);
```

## More examples.


#### Filtering.
```php
$users = $userStorage
    ->limit(100)
    ->filter(['or', ['in', 'career', ['actor', 'producer'], ['>=', 'age', 40]])
    ->fetch();
```


#### Updating.
```php
$ok = $userStorage
    ->sort(['id' => -1])// Sorting by `id` in descending order
    ->skip(100)// skip first 100 found rows
    ->update(['career' => 'actor']);
```


#### Relations.
```php
$userStorage = $userStorage->projection([
    'country' => new \vivace\Relation\Single($countryStorage, ['country_id' => 'id'])
]);

$users = $userStorage->fetch()->all();

foreach($users as $user){
    if(isset($user['country'])) {
        var_dump($user['country']);
    }
}

```
#### Field aliases.
```php
$userStorage = $userStorage->projection([
    'rank' => 'rating'
]);

// Aliases are available for use in the condition.
$user = $userStorage->filter(['between', 'rank', 4, 5])->fetch()->one();
```


## Running the tests

For tests, you need to connect to a database.
If you use a docker, then you can raise the database with the following command:
```
docker-compose up -d mysql pgsql
```
And run tests:
```
docker-compose run --rm php codecept run --env pgsql --env mysql
```

