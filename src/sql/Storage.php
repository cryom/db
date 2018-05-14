<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 27.02.2018
 * Time: 16:00
 */

namespace vivace\db\sql;


use vivace\db\mixin\Projection;
use vivace\db\Reader;
use vivace\db\Relation\Many;
use vivace\db\Relation\Single;

class Storage implements \vivace\db\Storage
{
    use Projection;
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

    protected $projection;

    /**
     * Source constructor.
     *
     * @param Driver $driver
     * @param string $source
     */
    public function __construct(Driver $driver, string $source)
    {
        $this->driver = $driver;
        $this->source = $source;
        $this->projection = $this->getProjection();
    }

    protected function getProjection(): array
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
        if ($this->projection) {
            $finder = $finder->projection($this->projection);
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
     * @return \vivace\db\sql\Schema|\vivace\db\sql\Field[]
     */
    public function schema(bool $refresh = false): Schema
    {
        $source = $this->getSource();
        return $this->driver()->schema($source, $refresh);
    }

    /**
     * @return \vivace\db\Reader
     * @throws \Exception
     */
    public function fetch(): Reader
    {
        return $this->find()->fetch();
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