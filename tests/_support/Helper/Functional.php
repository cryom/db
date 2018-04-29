<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module\Db;
use vivace\db\sql\Mysql;
use vivace\db\sql\Pgsql;
use vivace\db\sql\Storage;

class Functional extends \Codeception\Module
{

    /**
     * @param string $source
     *
     * @return \vivace\db\sql\Storage
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function createStorage(string $source)
    {
        /** @var Db $module */
        $module = $this->getModule('Db');
        $config = $module->_getConfig();

        $pdo = $module->driver->getDbh();
        $provider = \Codeception\Lib\Driver\Db::getProvider($config['dsn']);
        switch ($provider) {
            case 'mysql';
                $driver = new Mysql($pdo);
                break;
            case 'pgsql':
                $driver = new Pgsql($pdo);
                break;
            default:
                throw new \Exception("Unexpected environment: $provider");
        }

        return new Storage($driver, $source);
    }
}
