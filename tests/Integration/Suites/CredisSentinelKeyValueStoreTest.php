<?php

declare(strict_types=1);

namespace LizardsAndPumpkins;

use LizardsAndPumpkins\DataPool\KeyValue\Credis\CredisSentinelKeyValueStore;
use LizardsAndPumpkins\DataPool\KeyValueStore\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;

class CredisSentinelKeyValueStoreTest extends TestCase
{
    const SENTINEL_REDIS_HOST = 'localhost';

    const SENTINEL_REDIS_PORT = '26379';

    const SENTINEL_MASTER_NAME = 'mymaster';


    /**
     * @var CredisSentinelKeyValueStore
     */
    private $keyValueStore;

    protected function setUp()
    {
        $sentinel = new \Credis_Sentinel(new \Credis_Client(self::SENTINEL_REDIS_HOST, self::SENTINEL_REDIS_PORT));
        $cluster = $sentinel->getCluster(self::SENTINEL_MASTER_NAME);
        $cluster->del('foo');
        $cluster->del('key1');
        $cluster->del('key2');

        $this->keyValueStore = new CredisSentinelKeyValueStore($cluster);
    }

    public function testValueIsSetAndRetrieved()
    {
        $this->keyValueStore->set('foo', 'bar');
        $result = $this->keyValueStore->get('foo');

        $this->assertEquals('bar', $result);
    }

    public function testMultipleValuesAreSetAndRetrieved()
    {
        $items = ['key1' => 'foo', 'key2' => 'bar'];
        $keys = array_keys($items);

        $this->keyValueStore->multiSet($items);
        $result = $this->keyValueStore->multiGet(...$keys);

        $this->assertSame($items, $result);
    }

    public function testMissingValuesAreExcludedFromResultArray()
    {
        $items = ['key1' => 'foo', 'key2' => 'bar'];
        $keys = array_keys($items);

        $this->keyValueStore->multiSet($items);

        $keys[] = 'key3';
        $result = $this->keyValueStore->multiGet(...$keys);

        $this->assertSame($items, $result);
    }

    public function testFalseIsReturnedIfKeyDoesNotExist()
    {
        $this->assertFalse($this->keyValueStore->has('foo'));
    }

    public function testExceptionIsThrownIfValueIsNotSet()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->assertFalse($this->keyValueStore->get('not-set-value'));
    }
}
