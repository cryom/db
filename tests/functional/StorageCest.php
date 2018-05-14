<?php

use vivace\db\Relation\Many;
use vivace\db\Relation\Single;
use \vivace\db\sql;

class StorageCest
{
    /** @var \vivace\db\Storage[] */
    protected $storage;

    public function _before(FunctionalTester $I)
    {

    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function fetchOneWithFilter(FunctionalTester $I)
    {
        $bar = $I->createStorage('bar');
        $data = $bar->filter(['id' => 1])->fetch()->one();

        $I->assertInternalType('array', $data);
        $I->assertArrayHasKey('name', $data);
        $I->assertArrayHasKey('id', $data);

        $I->assertEquals(1, $data['id']);
        $I->assertEquals('bar_1', $data['name']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function fetchAllWithLimit(FunctionalTester $I)
    {
        $bar = $I->createStorage('bar');
        $data = $bar->limit(2)->fetch()->all();

        $I->assertInternalType('array', $data);
        $I->assertCount(2, $data);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function fetchAllByDatetime(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $finder = $foo->filter(['datetime' => new DateTime('2011-01-01 22:17:16')]);
        $result = $finder->fetch()->all();

        $I->assertCount(1, $result);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function fetchAllWithProjection(FunctionalTester $I)
    {
        $bar = $I->createStorage('bar');
        $data = $bar->projection([
            'bazID' => 'baz_id',
            'IDENTIFIER' => 'id',
            'baz_id' => false,
        ])->filter(['in', 'IDENTIFIER', [2, 3]])
            ->sort(['IDENTIFIER' => -1])
            ->fetch()->all();

        $I->assertInternalType('array', $data);
        $I->assertCount(2, $data);

        $I->assertArrayHasKey('name', $data[0]);
        $I->assertArrayHasKey('bazID', $data[0]);
        $I->assertArrayHasKey('IDENTIFIER', $data[0]);

        $I->assertSame(3, $data[0]['IDENTIFIER']);
        $I->assertSame(2, $data[1]['IDENTIFIER']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function case2(FunctionalTester $I)
    {
        $I->wantTo('Fetch two `bar` by id');

        $bar = $I->createStorage('bar');
        $finder = $bar->filter(['in', 'id', [1, 2]]);
        $finder = $finder->sort(['id' => -1]);

        $data = $finder->fetch();
        $data = iterator_to_array($data);

        $I->assertArrayHasKey(0, $data);
        $I->assertInternalType('array', $data[0]);
        $I->assertArrayHasKey('name', $data[0]);
        $I->assertArrayHasKey('id', $data[0]);
        $I->assertEquals(2, $data[0]['id']);
        $I->assertEquals('bar_2', $data[0]['name']);

        $I->assertArrayHasKey(1, $data);
        $I->assertInternalType('array', $data[1]);
        $I->assertArrayHasKey('name', $data[1]);
        $I->assertArrayHasKey('id', $data[1]);
        $I->assertEquals(1, $data[1]['id']);
        $I->assertEquals('bar_1', $data[1]['name']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function case6(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');

        $finder = $foo->filter(['between', 'bar_id', 1, 2]);
        $finder = $finder->and(['=', 'is_enabled', true]);
        $finder = $finder->or(['and', ['is_enabled' => false], ['in', 'type', [6, 7]]]);
        $finder = $finder->skip(1);
        $finder = $finder->sort(['order' => 1]);

        $result = $finder->fetch();

        $data = iterator_to_array($result);

        $I->assertCount(3, $data);
        $I->assertEquals('6', $data[0]['type']);
        $I->assertEquals('7', $data[1]['type']);
        $I->assertEquals('4', $data[2]['type']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @env mysql
     * @throws \Exception
     */
    public function mysqlTypecasting(FunctionalTester $I)
    {
        $storage = $I->createStorage('type_table');
        $item = $storage->fetch()->one();

        $I->assertSame(1, $item['bit']);
        $I->assertSame(2, $item['tinyint']);
        $I->assertSame(1, $item['bool']);
        $I->assertSame(4, $item['smallint']);
        $I->assertSame(5, $item['mediumint']);
        $I->assertSame(7, $item['integer']);
        $I->assertSame(8, $item['bigint']);

        $I->assertSame(9.3, $item['decimal']);
        $I->assertSame(9.4, $item['float']);
        $I->assertSame(9.5, $item['double']);


        $I->assertEquals(new DateTime('2017-01-02 00:00:00'), $item['datetime']);
        $I->assertEquals(new DateTime('2017-01-02 00:00:01'), $item['timestamp']);
        $I->assertSame('2017-01-02', $item['date']);
        $I->assertSame('2017', $item['year']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @env pgsql
     * @throws \Exception
     */
    public function pgsqlTypecasting(FunctionalTester $I)
    {
        $storage = $I->createStorage('type_table');
        $item = $storage->fetch()->one();

        $I->assertSame(1, $item['smallint']);
        $I->assertSame(2, $item['integer']);
        $I->assertSame(3, $item['bigint']);
        $I->assertSame(4.1, $item['decimal']);
        $I->assertSame(4.2, $item['numeric']);
        $I->assertSame(4.3, $item['real']);
        $I->assertSame(4.4, $item['double_precission']);
        $I->assertSame('$10.30', $item['money']);
        $I->assertSame('char_var', $item['char_var']);
        $I->assertSame(str_pad('char', 10, ' '), $item['char']);
        $I->assertSame('text', $item['text']);
        $I->assertInternalType(\PHPUnit\Framework\Constraint\IsType::TYPE_RESOURCE, $item['bytea']);
        $I->assertEquals(new DateTime('2017-01-01 22:01:02'), $item['timestamp']);
        $I->assertEquals(new DateTime('2017-01-01 22:01:03'), $item['timestamptz']);
        $I->assertSame('2017-01-01', $item['date']);
        $I->assertSame('22:01:02', $item['time']);
        $I->assertSame('22:01:03+00', $item['timetz']);
        $I->assertSame('00:00:35', $item['interval']);
        $I->assertSame(false, $item['boolean']);
        $I->assertSame('two', $item['enum']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function relation(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $bar = $I->createStorage('bar');
        $baz = $I->createStorage('baz');


        $items = $bar->projection([
            'barID' => 'id',
            'rBazName' => 'baz_name',
            'bazList' => (new Many($baz, ['rBazName' => 'bazName', 'baz_type' => 'type']))
                ->projection([
                    'bazName' => 'name'
                ]),
            'foo' => (new Single($foo, ['id' => 'bar_id']))
                ->filter(['is_enabled' => true])
                ->projection([
                    'bar' => (new Single($bar, ['bar_id' => 'id']))->filter(['!=', 'id', 4])
                ])
        ])
            ->skip(1)
            ->filter(['in', 'barID', [1, 2, 4]])
            ->limit(2)
            ->fetch()
            ->all();


        $I->assertCount(2, $items);

        $I->assertArrayHasKey('bazList', $items[0]);
        $I->assertCount(2, $items[0]['bazList']);
        $I->assertInternalType('array', $items[0]['bazList']);
        $I->assertArrayHasKey('bazName', $items[0]['bazList'][1]);
        $I->assertArrayHasKey('foo', $items[0]);
        $I->assertArrayHasKey('bar', $items[0]['foo']);

        $I->assertArrayHasKey('bazList', $items[1]);
        $I->assertInternalType('array', $items[1]['bazList']);
        $I->assertCount(0, $items[1]['bazList']);
        $I->assertArrayHasKey('foo', $items[1]);
        $I->assertArrayNotHasKey('bar', $items[1]['foo']);


        $item = $bar->projection([
            'barID' => 'id',
            'rBazName' => 'baz_name',
            'bazList' => (new Many($baz, ['rBazName' => 'bazName', 'baz_type' => 'type']))
                ->projection([
                    'bazName' => 'name'
                ]),
            'foo' => (new Single($foo, ['id' => 'bar_id']))
                ->filter(['is_enabled' => true])
                ->projection([
                    'bar' => (new Single($bar, ['bar_id' => 'id']))->filter(['!=', 'id', 4])
                ])
        ])
            ->filter(['in', 'barID', [4]])
            ->fetch()
            ->one();

        $I->assertArrayHasKey('bazList', $item);
        $I->assertInternalType('array', $item['bazList']);
        $I->assertCount(0, $item['bazList']);
        $I->assertArrayHasKey('foo', $item);
        $I->assertArrayNotHasKey('bar', $item['foo']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function multiIteration(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $reader = $foo->limit(2)->fetch();

        $I->assertCount(2, $reader);
        $data = iterator_to_array($reader);
        $I->assertCount(2, $data);
        $data = iterator_to_array($reader);
        $I->assertCount(2, $data);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function update(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');

        $vorder = 77;
        $vtype = 'case1';

        $affected = $foo->update(['type' => $vtype, 'order' => $vorder]);

        $I->assertSame(5, $affected);

        $data = $foo->fetch()->all();

        $I->assertSame($vorder, $data[0]['order']);
        $I->assertSame($vtype, $data[0]['type']);

        $I->assertSame($vorder, $data[1]['order']);
        $I->assertSame($vtype, $data[1]['type']);

        $I->assertSame($vorder, $data[2]['order']);
        $I->assertSame($vtype, $data[2]['type']);

        $I->assertSame($vorder, $data[3]['order']);
        $I->assertSame($vtype, $data[3]['type']);

        $I->assertSame($vorder, $data[4]['order']);
        $I->assertSame($vtype, $data[4]['type']);

        $finder = $foo->sort(['id' => -1])->skip(1)->limit(2);

        $vorder = 78;
        $vtype = 'case2';

        $affected = $finder->projection(['typeAlias' => 'type'])
            ->update(['typeAlias' => $vtype, 'order' => $vorder]);

        $I->assertSame(2, $affected);

        $data = $finder->skip(null)->limit(4)->fetch()->all();

        $I->assertNotSame($vorder, $data[0]['order']);
        $I->assertNotSame($vtype, $data[0]['type']);

        $I->assertSame($vorder, $data[1]['order']);
        $I->assertSame($vtype, $data[1]['type']);

        $I->assertSame($vorder, $data[2]['order']);
        $I->assertSame($vtype, $data[2]['type']);

        $I->assertNotSame($vorder, $data[3]['order']);
        $I->assertNotSame($vtype, $data[3]['type']);

    }


    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function count(FunctionalTester $I)
    {
        $foo = $I->createStorage('multi_pk');
        $I->assertEquals(6, $foo->count());
        $I->assertEquals(1, $foo->filter(['>', 'id', 2])->count());
        $I->assertEquals(3, $foo->sort(['id' => 1])->skip(1)->limit(3)->count());
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function delete(FunctionalTester $I)
    {
        $foo = $I->createStorage('multi_pk');

        $affected = $foo->sort(['id' => 1])->limit(2)->delete();
        $I->assertEquals(2, $affected);
        $affected = $foo->sort(['id' => -1])->skip(2)->delete();
        $I->assertEquals(2, $affected);
        $affected = $foo->filter(['tag' => '1-3'])->delete();
        $I->assertEquals(1, $affected);
        $data = $foo->fetch()->one();
        $I->assertEquals('2-2', $data['tag']);

    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function save(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');

        $count = $foo->count();
        $result = $foo->save(['id' => 1, 'name' => 'ABX']);
        $I->assertTrue($result);
        $I->assertEquals($count, $foo->count());

        $result = $foo->projection(['nameAlias' => 'name'])->save([
            ['id' => 1, 'nameAlias' => 'ABX1'],
            ['name' => 'ABX2', 'is_enabled' => true]
        ]);
        $I->assertTrue($result);
        $I->assertEquals($count + 1, $foo->count());

        $items = $foo->filter(['in', 'name', ['ABX1', 'ABX2']])->sort(['name' => 1])->fetch()->all();
        $I->assertCount(2, $items);

        $I->assertSame('ABX1', $items[0]['name']);
        $I->assertSame(1, $items[0]['id']);

        $I->assertSame('ABX2', $items[1]['name']);
    }
}
