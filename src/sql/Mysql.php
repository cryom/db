<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 18:52
 */

namespace vivace\db\sql;


use SebastianBergmann\Diff\Chunk;
use Traversable;
use vivace\db\Exception;
use vivace\db\Property;
use vivace\db\Reader;
use vivace\db\sql\statement\Read;
use vivace\db\sql\statement\Columns;

class Mysql extends Driver
{
    const VERSION = '1';
    const OP_LITERAL = 0;
    const OP_IDENTIFIER = 1;
    const OP_VALUE = 2;
    const OP_CONDITION = 3;

    protected $placeholderPrefix = 'x';
    /**
     * @var \PDO
     */
    protected $pdo;
    protected $meta;

    protected $stored = false;


    /**
     * Mysql constructor.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected static function literal($value)
    {
        return [self::OP_LITERAL, $value];
    }

    protected static function identifier($value)
    {
        return [self::OP_IDENTIFIER, $value];
    }

    protected static function value($value)
    {
        return [self::OP_VALUE, $value];
    }

    protected static function condition($value)
    {
        return [self::OP_CONDITION, $value];
    }

    protected function quote(string $identifier): string
    {
        return '`' . str_replace('.', '`.`', $identifier) . '`';
    }


    /**
     * @param \vivace\db\sql\statement\Statement|array $statement
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    public function build($statement, array $params = []): array
    {
        $stack = [$statement];
        $logical = null;

        $sql = [];
        $values = [];
        $ph = 0;
        while ($statement = array_pop($stack)) {
            if (is_array($statement)) {
                [$op, $args] = $statement;
                switch ($op) {
                    case self::OP_LITERAL:
                        $sql[] = $args;
                        break;
                    case self::OP_IDENTIFIER:
                        $sql[] = $this->quote($args);
                        break;
                    case self::OP_VALUE:
                        $args = (array)$args;
                        $s = '';
                        foreach ($args as $i => $arg) {
                            $id = $this->placeholderPrefix . sprintf('%x', $ph++);
                            $values[$id] = $arg;
                            if ($i) {
                                $s .= ',';
                            }
                            $s .= ':' . $id;
                        }
                        $sql[] = $s;
                        break;
                    case self::OP_CONDITION:
                        if (!isset($args[0])) {
                            $sep = false;
                            $stack[] = self::literal('(');
                            foreach ($args as $key => $val) {
                                if ($sep) {
                                    $stack[] = self::literal(' AND ');
                                } else {
                                    $sep = true;
                                }
                                if (is_array($val)) {
                                    $stack[] = self::condition(['in', $key, $val]);
                                } else {
                                    $stack[] = self::identifier($key);
                                    $stack[] = self::literal(' = ');
                                    $stack[] = self::value($val);
                                }
                            }
                            $stack[] = self::literal(')');
                        } else {
                            switch ($args[0]) {
                                case '=':
                                    if (is_array($args[2])) {
                                        $stack[] = self::condition(['in', $args[1], $args[2]]);
                                        break;
                                    }
                                case '>':
                                case '<':
                                case '>=':
                                case '<=':
                                case '!=':
                                    $stack[] = self::identifier($args[1]);
                                    $stack[] = self::literal(' ' . $args[0] . ' ');
                                    $stack[] = self::value($args[2]);
                                    break;
                                case 'and':
                                case 'or':
                                    $expressions = array_slice($args, 2);
                                    $stack[] = self::literal('(');
                                    $stack[] = self::condition($args[1]);
                                    $word = $args[0] === 'and' ? 'AND' : 'OR';
                                    foreach ($expressions as $expression) {
                                        $stack[] = self::literal(" $word ");
                                        $stack[] = self::condition($expression);
                                    }
                                    $stack[] = self::literal(')');
                                    break;
                                case 'in':
                                    $stack[] = self::identifier($args[1]);
                                    $stack[] = self::literal(' IN(');
                                    $stack[] = self::value($args[2]);
                                    $stack[] = self::literal(')');
                                    break;
                                case 'between':
                                    $stack[] = self::identifier($args[1]);
                                    $stack[] = self::literal(' BETWEEN ');
                                    $stack[] = self::value($args[2]);
                                    $stack[] = self::literal(' AND ');
                                    $stack[] = self::value($args[3]);
                                    break;
                            }
                        }

                        break;
                }
            } else {
                switch (get_class($statement)) {
                    case statement\Select::class:
                        /** @var $statement statement\Select */
                        $stack[] = self::literal('SELECT ');

                        if (!$statement->projection) {
                            $stack[] = self::literal('*');
                        } else {
                            $coma = false;
                            foreach ($statement->projection as $key => $val) {
                                if ($coma) {
                                    $stack[] = self::literal(', ');
                                } else {
                                    $coma = true;
                                }
                                if (is_int($key)) {
                                    $stack[] = self::identifier($val);
                                } else {
                                    if (is_string($val)) {
                                        if ($val === '*') {
                                            $stack[] = self::identifier($key);
                                            $stack[] = self::literal($key);
                                        } else {
                                            $stack[] = self::identifier($key);
                                            $stack[] = self::literal(' ');
                                            $stack[] = self::identifier($val);
                                        }
                                    } elseif (is_array($val)) {
                                        foreach ($val as $k => $v) {
                                            if (is_int($k)) {
                                                $stack[] = self::identifier("$key.$v");
                                            } else {
                                                $stack[] = self::identifier("$key.$k");
                                                $stack[] = self::literal(' ');
                                                $stack[] = self::identifier($v);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $stack[] = self::literal(' FROM ');
                        $stack[] = self::identifier($statement->source);

                        if ($statement->join) {
                            foreach ($statement->join as $join) {
                                switch ($join->type) {
                                    case statement\Join::LEFT:
                                        $stack[] = self::literal(' LEFT JOIN ');
                                        break;
                                    case statement\Join::RIGHT:
                                        $stack[] = self::literal(' RIGHT JOIN ');
                                        break;
                                    case statement\Join::INNER:
                                        $stack[] = self::literal(' INNER JOIN ');
                                        break;
                                }
                                $stack[] = self::identifier($join->source);
                                $stack[] = self::literal(' ON ');
                                $stack[] = self::condition($join->on);
                            }
                        }

                        if ($statement->where) {
                            $stack[] = self::literal(' WHERE ');
                            $stack[] = self::condition($statement->where);
                        }

                        if ($statement->order) {
                            $stack[] = self::literal(' ORDER BY ');
                            $coma = false;
                            foreach ($statement->order as $column => $direction) {
                                if ($coma) {
                                    $stack[] = self::literal(', ');
                                }
                                $stack[] = self::identifier($column);
                                if ($direction === -1) {
                                    $stack[] = self::literal(' DESC');
                                }
                            }
                        }

                        if ($statement->limit) {
                            $stack[] = self::literal(' LIMIT ');
                            if ($statement->offset) {
                                $stack[] = self::value((int)$statement->offset);
                                $stack[] = self::literal(', ');
                                $stack[] = self::value((int)$statement->limit);
                            } else {
                                $stack[] = self::value((int)$statement->limit);
                            }
                        } elseif ($statement->offset) {
                            $stack[] = self::literal(' LIMIT ');

                            $stack[] = self::value((int)$statement->offset);
                            $stack[] = self::literal(', ');
                            $stack[] = self::literal('18446744073709551615');
                        }

                        break;

                    case Columns::class:
                        /** @var Columns $statement */
                        $stack[] = self::literal('SHOW COLUMNS FROM ');
                        $stack[] = self::identifier($statement->sourceName);
                        break;

                    default:
                        throw new Exception("Not supported statement " . get_class($statement), Exception::STATEMENT_NOT_EXPECTED);
                }
            }


        }

        return [implode('', array_reverse($sql)), $values];
    }

    protected function prepare(string $sql, array $values): \PDOStatement
    {

        $stmt = $this->pdo->prepare($sql, [
            \PDO::ATTR_EMULATE_PREPARES => 1,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);

        foreach ($values as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, \PDO::PARAM_INT);
            } elseif (is_bool($v)) {
                $stmt->bindValue($k, $v, \PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($k, $v, \PDO::PARAM_STR);
            }
        }

        return $stmt;
    }

    /**
     * @param \vivace\db\sql\statement\Read $query
     *
     * @return \vivace\db\Reader
     * @throws \Exception
     */
    public function read(statement\Read $query): \vivace\db\Reader
    {
        [$sql, $params] = $this->build($query);
        if ($query instanceof Columns) {
            $stmt = $this->pdo->query($sql);
            return $this->readSchema($stmt, $query);
        } else {
            $stmt = $this->prepare($sql, $params);
            return $this->readData($stmt, $query);
        }
    }

    /**
     * @param \vivace\db\sql\statement\Modifier $query
     *
     * @return int
     * @throws \Exception
     */
    public function execute(statement\Modifier $query): int
    {

        $stmt = $this->prepare(...$this->build($query));
        $stmt->execute();

        return (int)$stmt->rowCount();
    }

    protected function readData(\PDOStatement $stmt, Read $query): \vivace\db\Reader
    {
        return new class($stmt) implements \vivace\db\Reader
        {
            /** @var \PDOStatement */
            protected $stmt;
            protected $first;
            protected $executed;

            public function __construct($stmt)
            {
                $this->stmt = $stmt;
            }

            public function __destruct()
            {
                $this->stmt->closeCursor();
            }

            /**
             * @return array|null
             */
            public function one(): ?array
            {
                $this->stmt->execute();
                $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
                $this->stmt->closeCursor();

                return $result;
            }

            public function all(): array
            {
                $this->stmt->execute();
                $result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->stmt->closeCursor();

                return $result;
            }

            public function count(): int
            {
                $this->stmt->execute();
                $result = $this->stmt->rowCount();
                $this->stmt->closeCursor();

                return $result;
            }

            public function chunk(int $size): Reader
            {
                return new Chunker($this, $size);
            }

            /**
             * Retrieve an external iterator
             *
             * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
             * @return Traversable An instance of an object implementing <b>Iterator</b> or
             * <b>Traversable</b>
             * @since 5.0.0
             */
            public function getIterator()
            {

                if (!$this->stmt->execute()) {
                    [$sqlstate, $driverCode, $text] = $this->stmt->errorInfo();
                    throw new Exception("SQLSTATE[$sqlstate]: ($driverCode) $text", $sqlstate);
                }
                while ($item = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
                    yield $item;
                }
                $this->stmt->closeCursor();
            }
        };
    }

    protected function readSchema(\PDOStatement $stmt, Columns $query): \vivace\db\Reader
    {
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($data as $i => $row) {
            $field = [
                'primaryKey' => false,
                'index' => $i,
                'name' => $row['Field'],
                'nullable' => false,
            ];
            if ($row['Null'] === 'YES') {
                $field['nullable'] = true;
            }
            if ($row['Default'] !== null || $field['nullable']) {
                $field['default'] = $row['Default'];
            }

            switch ($row['Key']) {
                case 'PRI':
                    $field['primaryKey'] = true;
                    break;
            }

            if (($pos = strpos($row['Type'], '(')) !== false) {
                $type = substr($row['Type'], 0, $pos);
            } else {
                $type = $row['Type'];
            }

            switch ($type) {
                case 'bit':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                    $field['type'] = 'int';
                    $field['unsigned'] = strpos($row['Type'], 'unsigned') !== false;
                    break;
                case 'datetime':
                case 'timestamp':
                    $field['type'] = \DateTime::class;
                    break;
                case 'float':
                case 'double':
                case 'decimal':
                    $field['type'] = 'float';
                    break;
                default:
                    $field['type'] = 'string';
                    break;

            }
            $result[] = $field;
        }

        return new class($result, count($result)) implements Reader
        {

            protected $data;
            protected $count;

            public function __construct($data, $count)
            {
                $this->data = $data;
                $this->count = $count;
            }

            /**
             * @return array|null
             */
            public function one(): ?array
            {
                return $this->data[0];
            }

            public function all(): array
            {
                return $this->data;
            }

            public function count(): int
            {
                return $this->count;
            }

            public function chunk(int $size): Reader
            {
                return $this;
            }

            /** @inheritdoc */
            public function getIterator()
            {
                return new \ArrayIterator($this->data);
            }
        };
    }

    /**
     * @param \vivace\db\Property $property
     * @param $value
     *
     * @return mixed
     */
    function typecastIn(Property $property, $value)
    {
        switch ($property->getType()) {
            case Property::TYPE_BOOL:
            case Property::TYPE_INT:
            case Property::TYPE_STRING:
            case Property::TYPE_FLOAT:
                settype($value, $property->getType());
                return $value;
            case Property::TYPE_DATETIME:
                if ($value instanceof \DateTime) {
                    return $value->format(\DateTime::ISO8601);
                }
        }

        return $value;
    }

    function typecastOut(Property $property, $value)
    {
        switch ($property->getType()) {
            case Property::TYPE_BOOL:
            case Property::TYPE_INT:
            case Property::TYPE_STRING:
            case Property::TYPE_FLOAT:
                settype($value, $property->getType());
                return $value;
            case Property::TYPE_DATETIME:
                return new \DateTime($value);
        }

        return $value;
    }
}
