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
use vivace\db\sql\Statement\Expression\DefaultValue;
use vivace\db\sql\Statement\Insert;

class Storage implements \vivace\db\Storage
{
    use Projection {
        projection as protected projection_;
    }
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
    }

    protected function getDefaultProjection(): array
    {
        $schema = $this->schema();
        return array_combine(
            $schema->getNames(),
            array_pad([], count($schema), true)
        );
    }

    public function projection(?array $projection)
    {
        if (!$this->projection) {
            $this->projection = $this->getDefaultProjection();
        }
        return $this->projection_($projection);
    }

    /**
     * @param null|array $filter
     *
     * @return Finder
     * @throws \Exception
     */
    public function filter(?array $filter)
    {
        return $this->find()->filter($filter);
    }

    /**
     * @param int|null $value
     *
     * @return Finder
     * @throws \Exception
     */
    public function limit(?int $value)
    {
        return $this->find()->limit($value);
    }

    /**
     * @return \vivace\db\sql\Finder
     * @throws \Exception
     */
    public function find()
    {
        if (empty($this->projection)) {
            $this->projection = $this->getDefaultProjection();
        }
        $finder = new Finder($this, $this->projection);
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
    public function skip(?int $value)
    {
        return $this->find()->skip($value);
    }

    /**
     * @param array $sort
     *
     * @return \vivace\db\Finder|\vivace\db\sql\Finder
     * @throws \Exception
     */
    public function sort(?array $sort)
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

    /** @inheritdoc */
    public function save(array $data): bool
    {
        $columns = [];
        $values = [];


        $multiple = isset($data[0]);
        if ($multiple && count($data) == 1) {
            $multiple = false;
            $data = $data[0];
        }

        if ($multiple) {
            foreach ($data as $i => $row) {
                foreach ($row as $name => $value) {
                    if ($this->projection && isset($this->projection[$name]) && is_string($this->projection[$name])) {
                        $name = $this->projection[$name];
                    }
                    $idx = $columns[$name] ?? $columns[$name] = count($columns);
                    $values[$i][$idx] = $value;
                }
            }
            foreach ($columns as $name => $idx) {
                foreach ($values as &$value) {
                    if (!isset($value[$idx])) {
                        $value[$idx] = new DefaultValue();
                    }
                }
            }
            foreach ($values as &$value) {
                ksort($value);
            }
            $columns = array_keys($columns);
        } else {
            $columns = array_keys($data);
            foreach ($columns as &$column) {
                if ($this->projection && isset($this->projection[$column]) && is_string($this->projection[$column])) {
                    $column = $this->projection[$column];
                }
            }
            $values[] = array_values($data);
        }

        $query = new Insert($this->getSource(), $columns, $values);
        $query->update = true;

        return (bool)$this->driver()->execute($query)->getInsertedId();
    }
}