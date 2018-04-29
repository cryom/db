<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module\Db;
use vivace\db\sql\Mysql;
use vivace\db\sql\Storage;

class Functional extends \Codeception\Module
{

    /**
     * @param string $source
     *
     * @return \vivace\db\sql\Storage
     * @throws \Codeception\Exception\ModuleException
     */
    public function createStorage(string $source)
    {
        /** @var Db $module */
        $module = $this->getModule('Db');
        $config = $module->_getConfig();
        $driver = new Mysql(new \PDO($config['dsn'], $config['user'], $config['password']));

        return new Storage($driver, $source);
    }
}
