<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 27.02.2018
 * Time: 16:00
 */

namespace vivace\db\sql;


use vivace\db\Property;
use vivace\db\Reader;
use vivace\db\Relation\Many;
use vivace\db\Relation\Single;
use vivace\db\Schema;
use vivace\db\sql\statement\Columns;

class Storage implements \vivace\db\Storage
{
    protected static $schemas = [];
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

    public function getSource(): string
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

    /**
     * @param bool $force
     *
     * @return Schema
     * @throws \Exception
     */
    public function schema(bool $force = false): Schema
    {
        $source = $this->getSource();
        if (!$force && isset(self::$schemas[$source])) {
            return self::$schemas[$source];
        }
        $schema = new Schema($source);
        $result = $this->driver()->read(new Columns($source));

        foreach ($result as $key => $value) {
            $property = new Property($value['name']);
            $property->setNullable($value['nullable']);
            $property->setType($value['type']);
            if (isset($value['default']))
                $property->setDefault($value['default']);

            $schema->set($value['name'], $property);
        }

        return self::$schemas[$source] = $schema;
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

    /**
     * @return \vivace\db\Reader
     * @throws \Exception
     */
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