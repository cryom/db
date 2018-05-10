<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 18:52
 */

namespace vivace\db\sql\MySQL;


use vivace\db\Exception;
use vivace\db\sql\Field;
use vivace\db\sql\statement;

final class Driver extends \vivace\db\sql\Driver
{

    const VERSION = '1';
    const QUERY_COLUMN =
        'SELECT
          ORDINAL_POSITION                  as `position`,
          COLUMN_NAME                       as `name`,
          COLUMN_DEFAULT                    as `default`,
          IF(IS_NULLABLE = "YES", 1, 0) as `nullable`,
          IF(COLUMN_KEY=\'PRI\', 1, 0)    as `primary`,
          DATA_TYPE                         as innerType,
          CASE DATA_TYPE
          WHEN \'bit\'
            THEN \'int\'
          WHEN \'tinyint\'
            THEN \'int\'
          WHEN \'smallint\'
            THEN \'int\'
          WHEN \'mediumint\'
            THEN \'int\'
          WHEN \'int\'
            THEN \'int\'
          WHEN \'bigint\'
            THEN \'int\'
          WHEN \'float\'
            THEN \'float\'
          WHEN \'double\'
            THEN \'float\'
          WHEN \'decimal\'
            THEN \'float\'
          WHEN \'timestamp\'
            THEN \'timestamp\'
          WHEN \'datetime\'
            THEN \'timestamp\'
          ELSE \'string\'
          END                               as `type`
        
        FROM information_schema.`COLUMNS`
        WHERE TABLE_SCHEMA=:schema AND TABLE_NAME=:table';

    const OP_LITERAL = 1;
    const OP_IDENTIFIER = 2;
    const OP_VALUE = 3;

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
        if (!$value) {
            return;
        }
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


    protected function update(array &$stack, statement\Update $statement)
    {
        self::literal($stack, 'UPDATE ');
        if ($statement->offset) {
            self::identifier($stack, $statement->source);
            self::literal($stack, 'X0_dst, (');
            $select = new statement\Select($statement->source);
            $select->where = $statement->where;
            $select->offset = $statement->offset;
            $select->limit = $statement->limit;
            $select->order = $statement->order;
            self::select($stack, $select);
            self::literal($stack, ') X0_src');

            self::literal($stack, ' SET ');
            $coma = false;
            foreach ($statement->set as $key => $value) {
                if ($coma) {
                    self::literal($stack, ',');
                } else {
                    $coma = true;
                }

                self::identifier($stack, "X0_dst.{$key}");
                self::literal($stack, ' = ');
                self::value($stack, $value);
            }

            $schema = $this->schema($statement->source);
            $keys = $schema->getPrimary() ?? $schema;
            self::literal($stack, ' WHERE ');
            foreach ($keys as $field) {
                self::identifier($stack, "X0_dst.{$field->getName()}");
                self::literal($stack, ' = ');
                self::identifier($stack, "X0_src.{$field->getName()}");
            }
        } else {
            self::identifier($stack, $statement->source);
            self::literal($stack, ' SET ');
            $coma = false;
            foreach ($statement->set as $key => $value) {
                if ($coma) {
                    self::literal($stack, ',');
                } else {
                    $coma = true;
                }

                self::identifier($stack, $key);
                self::literal($stack, ' = ');
                self::value($stack, $value);
            }

            if ($statement->where) {
                self::literal($stack, ' WHERE ');
                self::condition($stack, $statement->where);
            }

            self::order($stack, $statement->order);
            self::limit($stack, $statement->limit, null);
        }


    }

    protected static function columns(array &$stack, statement\Columns $statement)
    {
        if (strpos($statement->sourceName, '.') !== false) {
            [$db, $table] = array_map(function ($val) {
                return "\"$val\"";
            }, explode('.', $statement->sourceName));
        } else {
            [$db, $table] = ['DATABASE()', "\"$statement->sourceName\""];
        }
        self::literal($stack, strtr(self::QUERY_COLUMN, [
            ':schema' => $db, ':table' => $table
        ]));
    }

    protected static function join(array &$stack, statement\Join $statement)
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

    protected static function select(array &$stack, statement\Select $statement)
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
                    if ($val === '*') {
                        self::identifier($stack, $statement->source);
                        self::literal($stack, '.*');
                    } else {
                        self::identifier($stack, $val);
                    }
                } else {
                    if (is_string($val)) {
                        if ($val === '*') {
                            self::identifier($stack, $key);
                            self::literal($stack, '.*');
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

        self::order($stack, $statement->order);
        self::limit($stack, $statement->limit, $statement->offset);
    }

    protected static function order(array &$stack, ?array $order)
    {
        if ($order) {
            self::literal($stack, ' ORDER BY ');
            $coma = false;
            foreach ($order as $column => $direction) {
                if ($coma) {
                    self::literal($stack, ', ');
                }
                self::identifier($stack, $column);
                if ($direction === -1) {
                    self::literal($stack, ' DESC');
                }
            }
        }
    }

    protected static function limit(array &$stack, ?int $limit, ?int $offset)
    {
        if ($limit) {
            self::literal($stack, ' LIMIT ');
            if ($offset) {
                self::value($stack, (int)$offset);
                self::literal($stack, ', ');
                self::value($stack, (int)$limit);
            } else {
                self::value($stack, (int)$limit);
            }
        } elseif ($offset) {
            self::literal($stack, ' LIMIT ');
            self::value($stack, (int)$offset);
            self::literal($stack, ', ');
            self::literal($stack, '18446744073709551615');
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
                throw new Exception("Not expected statement type", Exception::INVALID_STATEMENT);
            }
            switch ($kind) {
                case self::OP_LITERAL:
                    $sql[] = $statement[1];
                    break;
                case self::OP_IDENTIFIER:
                    $sql[] = '`' . str_replace('.', '`.`', $statement[1]) . '`';
                    break;
                case self::OP_VALUE:
                    $s = '';
                    foreach ((array)$statement[1] as $i => $arg) {
                        $id = sprintf("X0%x", $ph++);
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
                    /** @var \vivace\db\sql\statement\Select $statement */
                    self::select($stack, $statement);
                    break;

                case statement\Columns::class:
                    /** @var \vivace\db\sql\statement\Columns $statement */
                    self::columns($stack, $statement);
                    break;
                case statement\Update::class:
                    /** @var \vivace\db\sql\statement\Update $statement */
                    $this->update($stack, $statement);
                    break;
                default:
                    throw Exception::invalidStatement("Not expected statement " . $kind);
            }
        } while ($statement = array_pop($stack));

        $sql = implode('', array_reverse($sql));
        return [$sql, $values];
    }

    /**
     * @param \vivace\db\sql\statement\Read $query
     *
     * @return Fetcher
     * @throws \Exception
     */
    public function fetch(statement\Read $query): \vivace\db\sql\Fetcher
    {
        [$sql, $params] = $this->build($query);

        return new Fetcher($this->pdo, $sql, $params);
    }

    /**
     * @param \vivace\db\sql\statement\Modifier $query
     *
     * @return int
     * @throws \Exception
     */
    public function execute(statement\Modifier $query): int
    {
        [$sql, $params] = $this->build($query);
        if ($params) {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $k => $v) {
                if (is_int($v)) {
                    $stmt->bindValue($k, $v, \PDO::PARAM_INT);
                } elseif (is_bool($v)) {
                    $stmt->bindValue($k, $v, \PDO::PARAM_BOOL);
                } else {
                    $stmt->bindValue($k, $v, \PDO::PARAM_STR);
                }
            }

            if (!$stmt->execute()) {
                [$sqlstate, $driverCode, $text] = $stmt->errorInfo();
                throw new \Exception("SQLSTATE[$sqlstate]: ($driverCode) $text", $sqlstate);
            }

            $affected = $stmt->rowCount();
        } else {
            $affected = $this->pdo->exec($sql);
        }

        return (int)$affected;
    }


}
