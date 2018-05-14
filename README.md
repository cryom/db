# vivace\db

_ORM_ with relationship support and a advanced query builder.
## Goals

Create a simply orm with relation and flexible query builder and nothing more.
It not a ActiveRecord.

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

Now must initialize storage objects.
```php
$users = new \vivace\db\sql\Storage($driver, 'users');
```
Now you can use created storages for data manipulation.


Save the data to your storage.
```php
$ok = $users->save(['name' => 'Zoe Saldana', 'career' => 'Actor']);
```
Let's try fetch saved data from storage.
```php
$user = $users->filter(['name' => 'Zoe Saldana'])->fetch()->one();
// $user is simple assoc array.
var_dump($user);
```

Now it's time to change the data

```php
$user['age'] = 39;
```
And save changes in storage.

```php
$ok = $users->save($user);
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

