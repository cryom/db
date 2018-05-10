<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 14:18
 */

namespace vivace\db\sql;


use vivace\db\sql\statement\Columns;

abstract class Driver
{
    protected $schemas;

    /**
     * @param \vivace\db\sql\statement\Statement|array $statement
     *
     * @return array [string $query, array $params]
     */
    abstract public function build($statement): array;

    /**
     * @param \vivace\db\sql\statement\Read $query
     *
     * @return \vivace\db\sql\Fetcher
     */
    abstract public function fetch(statement\Read $query): Fetcher;

    abstract public function execute(statement\Modifier $query): int;

    /**
     * Type casting for the value passed to the store
     *
     * @param \vivace\db\sql\Field $field
     * @param $value
     *
     * @return mixed
     */
    function typecastIn(Field $field, $value)
    {
        switch ($field->getType()) {
            case Field::TYPE_BOOL:
            case Field::TYPE_INT:
            case Field::TYPE_STRING:
            case Field::TYPE_FLOAT:
                settype($value, $field->getType());
                break;
            case Field::TYPE_TIMESTAMP;
                if ($value instanceof \DateTime) {
                    $value = $value->format(\DateTime::ISO8601);
                }
                break;
        }

        return $value;
    }


    /**
     * Type casting for the received value from the store
     *
     * @param \vivace\db\sql\Field $field
     * @param $value
     *
     * @return mixed
     */
    function typecastOut(Field $field, $value)
    {
        switch ($field->getType()) {
            case Field::TYPE_BOOL:
            case Field::TYPE_INT:
            case Field::TYPE_STRING:
            case Field::TYPE_FLOAT:
                settype($value, $field->getType());
                break;
            case Field::TYPE_TIMESTAMP;
                $value = new \DateTime($value);
                break;
        }

        return $value;
    }

    public function schema(string $source, bool $refresh = false): Schema
    {
        if (!$refresh && isset($this->schemas[$source])) {
            return $this->schemas[$source];
        }

        $fields = [];
        foreach ($this->fetch(new Columns($source))->all() as $item) {
            $fields[$item['name']] = $item;
        }

        return $this->schemas[$source] = new class ($fields) implements Schema
        {
            /** @var Field[] */
            protected $fields = [];

            public function __construct($fields)
            {
                foreach ($fields as $name => $field) {
                    $this->fields[$name] = new class($field) implements Field
                    {
                        /**
                         * @var array
                         */
                        protected $field;


                        public function __construct($field)
                        {
                            $this->field = $field;
                        }

                        public function getName(): string
                        {
                            return $this->field['name'];
                        }

                        public function getType(): string
                        {
                            return $this->field['type'];
                        }

                        public function isPrimary(): bool
                        {
                            return (bool)$this->field['primary'];
                        }
                    };
                }
            }


            /** @inheritdoc */
            public function getIterator()
            {
                return new \ArrayIterator($this->fields);
            }

            /** @inheritdoc */
            public function getPrimary(): ?array
            {
                $result = [];
                foreach ($this->fields as $name => $field) {
                    if ($field->isPrimary()) {
                        $result[$name] = $field;
                    }
                }

                return $result;
            }

            /** @inheritdoc */
            public function get(string $key): Field
            {
                return $this->fields[$key];
            }

            /** @inheritdoc */
            public function has(string $key): bool
            {
                return isset($this->fields[$key]);
            }

            /** @inheritdoc */
            public function count()
            {
                return count($this->fields);
            }

            public function getNames(): array
            {
                return array_keys($this->fields);
            }
        };
    }

}

