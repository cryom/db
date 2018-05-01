<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 18:52
 */

namespace vivace\db\sql;


use Traversable;
use vivace\db\Exception;
use vivace\db\Property;
use vivace\db\Reader;
use vivace\db\sql\statement\Join;
use vivace\db\sql\statement\Read;
use vivace\db\sql\statement\Columns;
use vivace\db\sql\statement\Select;


final class Pgsql extends Driver
{
    const TPL_SCHEMA =
        /** @lang text */
        "SELECT  
            f.attnum AS number,  
            f.attname AS name,  
            f.attnum,  
            f.attnotnull AS notnull,  
            pg_catalog.format_type(f.atttypid,f.atttypmod) AS type,  
            CASE  
                WHEN p.contype = 'p' THEN 't'  
                ELSE 'f'  
            END AS primarykey,  
            CASE  
                WHEN p.contype = 'u' THEN 't'  
                ELSE 'f'
            END AS uniquekey,
            CASE
                WHEN p.contype = 'f' THEN g.relname
            END AS foreignkey,
            CASE
                WHEN p.contype = 'f' THEN p.confkey
            END AS foreignkey_fieldnum,
            CASE
                WHEN p.contype = 'f' THEN g.relname
            END AS foreignkey,
            CASE
                WHEN p.contype = 'f' THEN p.conkey
            END AS foreignkey_connnum,
            CASE
                WHEN f.atthasdef = 't' THEN d.adsrc
            END AS default
        FROM pg_attribute f  
            JOIN pg_class c ON c.oid = f.attrelid  
            JOIN pg_type t ON t.oid = f.atttypid  
            LEFT JOIN pg_attrdef d ON d.adrelid = c.oid AND d.adnum = f.attnum  
            LEFT JOIN pg_namespace n ON n.oid = c.relnamespace  
            LEFT JOIN pg_constraint p ON p.conrelid = c.oid AND f.attnum = ANY (p.conkey)  
            LEFT JOIN pg_class AS g ON p.confrelid = g.oid  
        WHERE c.relkind = 'r'::char  
            AND {condition}
            AND f.attnum > 0 ORDER BY number";

    const VERSION = '1';
    const OP_LITERAL = 1;
    const OP_IDENTIFIER = 2;
    const OP_VALUE = 3;

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


    protected static function literal(array &$stack, $value)
    {
        $stack[] = [self::OP_LITERAL, $value];
    }

    protected static function identifier(array &$stack, $value)
    {
        $stack[] = [self::OP_IDENTIFIER, $value];
    }

    protected static function value(array &$stack, $value)
    {
        $stack[] = [self::OP_VALUE, $value];
    }

    protected static function condition(array &$stack, $value)
    {
        if (!isset($value[0])) {
            $sep = false;
            $isMulti = count($value) > 1;
            $isMulti && self::literal($stack, '(');
            foreach ($value as $key => $val) {
                if ($sep) {
                    self::literal($stack, ' AND ');
                } else {
                    $sep = true;
                }
                self::identifier($stack, $key);
                self::literal($stack, ' = ');
                self::value($stack, $val);
            }
            $isMulti && self::literal($stack, ')');
        } else {
            $stack[] = $value;
        }
    }

    protected static function columns(array &$stack, Columns $statement)
    {
        if (strpos($statement->sourceName, '.') !== false) {
            [$schema, $table] = explode('.', $statement->sourceName);
            $condition = "n.nspname = '$schema' AND c.relname = '$table'";
        } else {
            $condition = "c.relname = '$statement->sourceName'";
        }
        self::literal($stack, str_replace('{condition}', $condition, self::TPL_SCHEMA));
    }

    protected static function join(array &$stack, Join $statement)
    {
        switch ($statement->type) {
            case statement\Join::LEFT:
                self::literal($stack, ' LEFT JOIN ');
                break;
            case statement\Join::RIGHT:
                self::literal($stack, ' RIGHT JOIN ');
                break;
            case statement\Join::INNER:
                self::literal($stack, ' INNER JOIN ');
                break;
        }
        self::identifier($stack, $statement->source);
        self::literal($stack, ' ON ');
        self::condition($stack, $statement->on);
    }

    protected static function select(array &$stack, Select $statement)
    {
        self::literal($stack, 'SELECT ');

        if (!$statement->projection) {
            self::literal($stack, '*');
        } else {
            $coma = false;
            foreach ($statement->projection as $key => $val) {
                if ($coma) {
                    self::literal($stack, ', ');
                } else {
                    $coma = true;
                }
                if (is_int($key)) {
                    self::identifier($stack, $val);
                } else {
                    if (is_string($val)) {
                        if ($val === '*') {
                            self::identifier($stack, $key);
                            self::literal($stack, $key);
                        } else {
                            self::identifier($stack, $key);
                            self::literal($stack, ' ');
                            self::identifier($stack, $val);
                        }
                    } elseif (is_array($val)) {
                        foreach ($val as $k => $v) {
                            if (is_int($k)) {
                                self::identifier($stack, "$key.$v");
                            } else {
                                self::identifier($stack, "$key.$k");
                                self::literal($stack, ' ');
                                self::identifier($stack, $v);
                            }
                        }
                    }
                }
            }
        }

        self::literal($stack, ' FROM ');
        self::identifier($stack, $statement->source);

        if ($statement->join) {
            foreach ($statement->join as $join) {
                self::join($stack, $join);
            }
        }

        if ($statement->where) {
            self::literal($stack, ' WHERE ');
            self::condition($stack, $statement->where);
        }

        if ($statement->order) {
            self::literal($stack, ' ORDER BY ');
            $coma = false;
            foreach ($statement->order as $column => $direction) {
                if ($coma) {
                    self::literal($stack, ', ');
                }
                self::identifier($stack, $column);
                if ($direction === -1) {
                    self::literal($stack, ' DESC');
                }
            }
        }

        if ($statement->limit) {
            self::literal($stack, ' LIMIT ');
            self::value($stack, (int)$statement->limit);

        } elseif ($statement->offset) {
            self::literal($stack, ' OFFSET ');
            self::value($stack, (int)$statement->offset);
        }
    }

    /**
     * @param \vivace\db\sql\statement\Statement|array $statement
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    public function build($statement): array
    {
        $stack = [];
        $sql = [];
        $values = [];
        $ph = 0;

        do {
            if (is_array($statement)) {
                $kind = $statement[0];
            } elseif (is_object($statement)) {
                $kind = get_class($statement);
            } else {
                throw new Exception("Not expected statement type", Exception::STATEMENT_NOT_EXPECTED);
            }
            switch ($kind) {
                case self::OP_LITERAL:
                    $sql[] = $statement[1];
                    break;
                case self::OP_IDENTIFIER:
                    $sql[] = '"' . str_replace('.', '"."', $statement[1]) . '"';
                    break;
                case self::OP_VALUE:
                    $s = '';
                    foreach ((array)$statement[1] as $i => $arg) {
                        $id = $this->placeholderPrefix . sprintf('%x', $ph++);
                        $values[$id] = $arg;
                        if ($i) {
                            $s .= ',';
                        }
                        $s .= ':' . $id;
                    }
                    $sql[] = $s;
                    break;
                case '=':
                case '>':
                case '<':
                case '>=':
                case '<=':
                case '!=':
                    self::identifier($stack, $statement[1]);
                    self::literal($stack, ' ' . $statement[0] . ' ');
                    self::value($stack, $statement[2]);
                    break;
                case 'and':
                case 'or':
                    $expressions = array_slice($statement, 2);
                    self::literal($stack, '(');
                    self::condition($stack, $statement[1]);
                    $word = $statement[0] === 'and' ? 'AND' : 'OR';
                    foreach ($expressions as $expression) {
                        self::literal($stack, " $word ");
                        self::condition($stack, $expression);
                    }
                    self::literal($stack, ')');
                    break;
                case 'in':
                    self::identifier($stack, $statement[1]);
                    self::literal($stack, ' IN(');
                    self::value($stack, $statement[2]);
                    self::literal($stack, ')');
                    break;
                case 'between':
                    self::identifier($stack, $statement[1]);
                    self::literal($stack, ' BETWEEN ');
                    self::value($stack, $statement[2]);
                    self::literal($stack, ' AND ');
                    self::value($stack, $statement[3]);
                    break;
                case statement\Select::class:
                    /** @var Select $statement */
                    self::select($stack, $statement);
                    break;

                case Columns::class:
                    /** @var Columns $statement */
                    self::columns($stack, $statement);
                    break;

                default:
                    throw new Exception("Not supported statement " . $kind, Exception::STATEMENT_NOT_EXPECTED);
            }
        } while ($statement = array_pop($stack));

        $sql = implode('', array_reverse($sql));
        return [$sql, $values];
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
                'primaryKey' => $row['primarykey'] === 't',
                'index' => $i,
                'name' => $row['name'],
                'nullable' => $row['notnull'] === false,
            ];

            if ($row['default']) {
                if (is_numeric($row['default'])) {
                    $field['default'] = $row['default'];
                } elseif (preg_match('/^\'([^\'])\'::character varying$/', $row['default'], $matches)) {
                    $field['default'] = $matches[1];
                }
            }

            if (($pos = strpos($row['type'], '(')) !== false) {
                $type = substr($row['type'], 0, $pos);
            } else {
                $type = $row['type'];
            }

            switch ($type) {
                case 'smallint':
                case 'integer':
                case 'bigint':
                case 'smallserial':
                case 'serial':
                case 'bigserial':
                    $field['type'] = 'int';
                    $field['unsigned'] = false;
                    break;
                case 'datetime':
                case 'timestamp without time zone':
                    $field['type'] = \DateTime::class;
                case 'timestamp with time zone':
                    $field['type'] = \DateTime::class;
                    $field['timezone'] = true;
                    break;
                case 'decimal':
                case 'numeric':
                case 'real':
                case 'double precision':
                    $field['type'] = 'float';
                    break;
                case 'boolean':
                    $field['type'] = 'boolean';
                    break;
                case 'bytea':
                    $field['type'] = 'resource';
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
