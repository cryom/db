<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 04.05.2018
 * Time: 8:57
 */

namespace vivace\db\sql\PostgreSQL;


use vivace\db\Exception;

class Fetcher implements \vivace\db\sql\Fetcher
{
    /** @var \PDOStatement */
    protected $stmt;
    protected $executed;
    /**
     * @var string
     */
    protected $sql;
    /**
     * @var array
     */
    protected $params;
    /**
     * @var \PDO
     */
    protected $PDO;

    public function __construct(\PDO $PDO, string $sql, array $params = [])
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->PDO = $PDO;
    }

    /**
     * @throws Exception
     */
    protected function execute()
    {
        if (!$this->stmt) {
            if ($this->params) {
                $this->stmt = $this->PDO->prepare($this->sql);
                foreach ($this->params as $k => $v) {
                    if (is_int($v)) {
                        $this->stmt->bindValue($k, $v, \PDO::PARAM_INT);
                    } elseif (is_bool($v)) {
                        $this->stmt->bindValue($k, $v, \PDO::PARAM_BOOL);
                    } else {
                        $this->stmt->bindValue($k, $v, \PDO::PARAM_STR);
                    }
                }
            } else {
                $this->stmt = $this->PDO->query($this->sql);
            }
        }

        if (!$this->executed) {
            if (!$this->stmt->execute()) {
                [$sqlstate, $driverCode, $text] = $this->stmt->errorInfo();
                throw Exception::onExecuting("SQLSTATE[$sqlstate]: ($driverCode) $text");
            }
            $this->executed = true;
        }
    }

    protected function close()
    {
        if ($this->executed) {
            $this->stmt->closeCursor();
            $this->executed = false;
        }
    }


    /**
     * @return array|null
     * @throws \Exception
     */
    public function one(): ?array
    {
        $this->execute();
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        $this->close();

        return $result !== false ? $result : null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function all(): array
    {
        $this->execute();
        $result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->close();

        return $result;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function count(): int
    {
        $this->execute();
        $result = $this->stmt->rowCount();

        return $result;
    }


    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getIterator()
    {
        $this->execute();
        while ($item = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield $item;
        }
        $this->close();
    }

    public function __clone()
    {
        $this->close();
        $this->stmt = null;
        $this->executed = false;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function scalar()
    {
        $this->execute();
        $result = $this->stmt->fetch(\PDO::FETCH_NUM);
        $this->close();

        return $result[0] ?? null;
    }
}