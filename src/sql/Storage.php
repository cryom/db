<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 27.02.2018
 * Time: 16:00
 */

namespace vivace\db\sql;


use vivace\db\Reader;
use vivace\db\Relation\Many;
use vivace\db\Relation\Single;
use vivace\db\sql\expression\Schema;

class Storage implements \vivace\db\Storage
{
    private static $meta = [];
    /**
     * @var \PDO
     */
    protected $adapter;
    /**
     * @var string
     */
    protected $sourceName;


    /**
     * Source constructor.
     *
     * @param Driver $adapter
     * @param string $sourceName
     */
    public function __construct(Driver $adapter, string $sourceName)
    {
        $this->adapter = $adapter;
        $this->sourceName = $sourceName;
    }

    /**
     * @param null|array $filter
     *
     * @return Finder
     */
    public function filter(array $filter)
    {
        return $this->find()->filter($filter);
    }

    /**
     * @param int|null $value
     *
     * @return Finder
     */
    public function limit(int $value)
    {
        return $this->find()->limit($value);
    }

    /**
     * @return \vivace\db\sql\Finder
     */
    public function find()
    {
        $finder = new Finder($this);

        return $finder;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function driver(): Driver
    {
        return $this->adapter;
    }

    /**
     * @param int $value
     *
     * @return \vivace\db\Finder|\vivace\db\sql\Finder
     */
    public function skip(int $value)
    {
        return $this->find()->skip($value);
    }

    public function sort(array $sort)
    {
        return $this->find()->sort($sort);
    }

    public function schema(bool $force = false): array
    {
        $sourceName = $this->getSourceName();
        if (!$force && isset(self::$meta[$sourceName])) {
            return self::$meta[$sourceName];
        }

        $result = $this->driver()->read(new Schema($sourceName));
        $map = [];
        foreach ($result as $key => $value) {
            $map[$value['name']] = $value;
        }

        return self::$meta[$sourceName] = $map;
    }

    /**
     * @param array $map
     *
     * @return \vivace\db\sql\Finder
     */
    public function projection(array $map)
    {
        return $this->find()->projection($map);
    }

    public function typecast(bool $enable = true)
    {
        return $this->find()->typecast($enable);
    }

    public function fetch(): Reader
    {
        return $this->find()->fetch();
    }

    public function single(array $key): Single
    {
        return new Relation\Single($this, $key);
    }

    public function many(array $key): Many
    {
        return new Relation\Many($this, $key);
    }
}