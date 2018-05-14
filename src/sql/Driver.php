<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 14:18
 */

namespace vivace\db\sql;


use vivace\db\sql\Statement\Columns;

abstract class Driver
{
    protected $schemas;

    /**
     * @param \vivace\db\sql\Statement\Statement|array $statement
     *
     * @return array [string $query, array $params]
     */
    abstract public function build($statement): array;

    /**
     * @param \vivace\db\sql\Statement\Read $query
     *
     * @return \vivace\db\sql\Fetcher
     */
    abstract public function fetch(Statement\Read $query): Fetcher;

    abstract public function execute(Statement\Modifier $query): Result;

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

    /**
     * @param string $source
     * @param bool $refresh
     *
     * @return \vivace\db\sql\Schema|\vivace\db\sql\Field[]
     */
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
            /** @var \vivace\db\sql\Field|null */
            protected $autoincrement;
            /** @var \vivace\db\sql\Field[] */
            protected $primary = [];
            /** @var \vivace\db\sql\Field[] */
            protected $unique = [];

            public function __construct($fieldsArr)
            {
                foreach ($fieldsArr as $name => $fieldArr) {
                    $field = new class($fieldArr) implements Field
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

                        public function isUnique(): bool
                        {
                            return (bool)$this->field['unique'];
                        }

                        public function isNullable(): bool
                        {
                            return (bool)$this->field['nullable'];
                        }

                        public function isDefault(): bool
                        {
                            return $this->field['default'] !== null || $this->isNullable();
                        }

                        public function isAutoincrement(): bool
                        {
                            return (bool)$this->field['autoincrement'];
                        }

                        public function getDefault()
                        {
                            return $this->field['default'];
                        }


                    };

                    if ($field->isAutoincrement()) {
                        $this->autoincrement = $field;
                    }
                    if ($field->isPrimary()) {
                        $this->primary[] = $field;
                    }
                    if ($field->isUnique()) {
                        $this->unique[] = $field;
                    }
                    $this->fields[$name] = $field;
                }
            }

            public function getAutoincrement(): ?Field
            {
                return $this->autoincrement;
            }


            /** @inheritdoc */
            public function getIterator()
            {
                return new \ArrayIterator($this->fields);
            }

            /** @inheritdoc */
            public function getPrimary(): ?array
            {
                return $this->primary;
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

            /** @inheritdoc */
            public function getUnique(): ?array
            {
                return $this->unique;
            }
        };
    }
}

