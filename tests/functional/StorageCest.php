<?php

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
    public function case1(FunctionalTester $I, \Codeception\Scenario $scenario)
    {
        $I->wantTo('Fetch one `bar`');

        $bar = $I->createStorage('bar');
        $data = $bar->filter(['id' => 1])->typecast()->fetch()->one();

        $I->assertInternalType('array', $data);
        $I->assertArrayHasKey('name', $data);
        $I->assertArrayHasKey('id', $data);

        $I->assertSame(1, $data['id']);
        $I->assertEquals('bar_1', $data['name']);
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
        $finder = $bar->filter(['id' => [1, 2]]);
        $finder = $finder->sort(['id' => -1]);
        $finder = $finder->typecast();

        $data = $finder->fetch();
        $data = iterator_to_array($data);

        $I->assertArrayHasKey(0, $data);
        $I->assertInternalType('array', $data[0]);
        $I->assertArrayHasKey('name', $data[0]);
        $I->assertArrayHasKey('id', $data[0]);
        $I->assertSame(2, $data[0]['id']);
        $I->assertEquals('bar_2', $data[0]['name']);

        $I->assertArrayHasKey(1, $data);
        $I->assertInternalType('array', $data[1]);
        $I->assertArrayHasKey('name', $data[1]);
        $I->assertArrayHasKey('id', $data[1]);
        $I->assertSame(1, $data[1]['id']);
        $I->assertEquals('bar_1', $data[1]['name']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function case3(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $bar = $I->createStorage('bar');
        $bar = $bar->projection([
            'foo' => $foo->single(['id' => 'bar_id']),
        ]);
        $result = $bar->fetch()->one();

        $I->assertArrayHasKey('foo', $result);
        $I->assertInternalType('array', $result['foo']);
        $I->assertNotEmpty($result['foo']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function case4(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $baz = $I->createStorage('baz');
        $bar = $I->createStorage('bar');
        $bar = $bar->projection([
            'foo' => $foo->single(['id' => 'bar_id']),
            'baz' => $baz->single(['baz_id' => 'id'])
        ]);
        $result = $bar->filter(['id' => [1, 2, 3]])->fetch()->all();

        $I->assertCount(3, $result);
        $I->assertArrayHasKey('foo', $result[0]);
        $I->assertArrayHasKey('baz', $result[0]);

        $I->assertArrayHasKey('foo', $result[1]);
        $I->assertArrayHasKey('baz', $result[1]);

        $I->assertArrayNotHasKey('foo', $result[2]);
        $I->assertArrayHasKey('baz', $result[2]);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    public function case5(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');
        $baz = $I->createStorage('baz');
        $bar = $I->createStorage('bar');

        $bar = $bar->projection([
            'foo' => $foo->many(['id' => 'bar_id']),
            'baz' => $baz->many(['baz_id' => 'id'])
        ]);
        $result = $bar->filter(['=', 'id', [1, 2, 3]])->fetch()->all();

        $I->assertCount(3, $result);
        $I->assertArrayHasKey('foo', $result[0]);
        $I->assertCount(2, $result[0]['foo']);
        $I->assertArrayHasKey('baz', $result[0]);
        $I->assertCount(1, $result[0]['baz']);

        $I->assertArrayHasKey('foo', $result[1]);
        $I->assertCount(2, $result[1]['foo']);
        $I->assertArrayHasKey('baz', $result[1]);
        $I->assertCount(1, $result[1]['baz']);


        $I->assertArrayNotHasKey('foo', $result[2]);
        $I->assertArrayHasKey('baz', $result[2]);
        $I->assertCount(1, $result[2]['baz']);
    }

    /**
     * @param \FunctionalTester $I
     *
     * @throws \Codeception\Exception\ModuleException
     */
    public function case6(FunctionalTester $I)
    {
        $foo = $I->createStorage('foo');

        $finder = $foo->filter(['between', 'bar_id', 1, 2]);
        $finder = $finder->and(['=', 'is_enabled', true]);
        $finder = $finder->or(['and', ['is_enabled' => false], ['in', 'type', [6, 7]]]);
        $finder = $finder->skip(1);
        $finder = $finder->sort(['order' => 1]);

        $result = $finder->typecast()->fetch();

        $data = iterator_to_array($result);

        $I->assertCount(3, $data);
//        $I->assertSame('5', $data[0]['type']);
        $I->assertSame('6', $data[0]['type']);
        $I->assertSame('7', $data[1]['type']);
        $I->assertSame('4', $data[2]['type']);
    }

    public function case7(FunctionalTester $I)
    {
        $I->wantTo('Check float pointing values');

        $bar = $I->createStorage('foo');
        $data = $bar->filter(['id' => 1])->typecast()->fetch()->one();

        $I->assertSame(1, $data['id']);
        $I->assertEquals('foo_1', $data['name']);
        $I->assertSame(0.1, $data['float']);
        $I->assertSame(0.2, $data['double']);
        $I->assertSame(0.3, $data['decimal']);
        $I->assertEquals(DateTime::createFromFormat('Y-m-d H:i:s', '2011-01-01 22:17:17'), $data['datetime']);

    }
}
