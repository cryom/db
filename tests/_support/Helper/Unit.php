<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Stub;
use vivace\db\sql\Mysql;
use vivace\db\sql\Storage;

class Unit extends \Codeception\Module
{
    /**
     * @return \vivace\db\sql\Storage
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function createStorage(string $source)
    {
        /** @var Db $module */
        $module = $this->getModule('Db');
        $config = $module->_getConfig();
        $pdo = Stub::makeEmpty(\PDO::class);
        $driver = new Mysql($pdo);
        return new Storage($driver, $source);
    }
}
