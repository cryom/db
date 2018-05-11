<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 27.02.2018
 * Time: 16:00
 */

namespace vivace\db\sql;


use vivace\db\Collection;
use vivace\db\Entity;
use vivace\db\Reader;
use vivace\db\Relation\Many;
use vivace\db\Relation\Single;

class Storage implements \vivace\db\Storage
{
    /**
     * @var \PDO
     */
    protected $driver;
    /**
     * @var string
     */
    protected $source;
    /**
     * @var array
     */
    protected $schema;

    /**
     * Source constructor.
     *
     * @param Driver $driver
     * @param string $source
     * @param array $schema
     */
    public function __construct(Driver $driver, string $source, array $schema = [])
    {
        $this->driver = $driver;
        $this->source = $source;
        $this->schema = $schema;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProjection(): array
    {
        $schema = $this->schema();
        return array_combine(
            $schema->getNames(),
            array_pad([], count($schema), true)
        );
    }

    /**
     * @param null|array $filter
     *
     * @return Finder
     * @throws \Exception
     */
    public function filter(array $filter)
    {
        return $this->find()->filter($filter);
    }

    /**
     * @param int|null $value
     *
     * @return Finder
     * @throws \Exception
     */
    public function limit(int $value)
    {
        return $this->find()->limit($value);
    }

    /**
     * @return \vivace\db\sql\Finder
     * @throws \Exception
     */
    public function find()
    {
        $finder = new Finder($this);
        if ($projection = $this->getProjection()) {
            $finder = $finder->projection($projection);
        }
        return $finder;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function driver(): Driver
    {
        return $this->driver;
    }

    /**
     * @param int $value
     *
     * @return \vivace\db\Finder|\vivace\db\sql\Finder
     * @throws \Exception
     */
    public function skip(int $value)
    {
        return $this->find()->skip($value);
    }

    /**
     * @param array $sort
     *
     * @return \vivace\db\Finder|\vivace\db\sql\Finder
     * @throws \Exception
     */
    public function sort(array $sort)
    {
        return $this->find()->sort($sort);
    }

    /**
     * @param bool $refresh
     *
     * @return \vivace\db\sql\Schema
     */
    public function schema(bool $refresh = false): Schema
    {
        $source = $this->getSource();
        return $this->driver()->schema($source, $refresh);
    }

    /**
     * @param array $map
     *
     * @return \vivace\db\sql\Finder
     * @throws \Exception
     */
    public function projection(array $map)
    {
        return $this->find()->projection($map);
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

    /**
     * @param array $data
     *
     * @return \vivace\db\Entity
     * @throws \Exception
     */
    public function entity(array $data = []): Entity
    {
        return new \vivace\db\sql\Entity($this, $this->getProjection(), $data);
    }

    /**
     * @param \vivace\db\Entity[] $entities
     *
     * @return \vivace\db\Collection
     */
    public function collection(array $entities = []): Collection
    {
        return new \vivace\db\sql\Collection($this, $entities);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function update(array $data): int
    {
        return $this->find()->update($data);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function delete(): int
    {
        return $this->find()->delete();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function count(): int
    {
        return $this->find()->count();
    }
}