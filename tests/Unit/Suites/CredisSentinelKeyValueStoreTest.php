<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\DataPool\KeyValue\Credis;

use Credis_Cluster;
use LizardsAndPumpkins\DataPool\KeyValueStore\Exception\KeyNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\DataPool\KeyValue\Credis\CredisSentinelKeyValueStore
 */
class CredisSentinelKeyValueStoreTest extends TestCase
{
    /**
     * @var CredisSentinelKeyValueStore
     */
    private $store;

    /**
     * @var \Credis_Cluster|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockCluster;

    public function setUp()
    {
        $this->mockCluster = $this->getMockBuilder(Credis_Cluster::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'set', 'exists', 'mGet', 'mSet'])
            ->getMock();
        $this->store = new CredisSentinelKeyValueStore($this->mockCluster);
    }

    public function testSettingValueIsDelegatedToClient()
    {
        $key = 'key';
        $value = 'value';

        $this->mockCluster->expects($this->once())->method('set')->with($key, $value);
        $this->store->set($key, $value);
    }

    public function testExceptionIsThrownDuringAttemptToGetAValueWhichIsNotSet()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->mockCluster->method('get')->willReturn(false);
        $this->store->get('not set key');
    }

    public function testGettingValueIsDelegatedToClient()
    {
        $key = 'key';
        $value = 'value';

        $this->mockCluster->method('get')->with($key)->willReturn($value);

        $this->assertEquals($value, $this->store->get($key));
    }

    public function testCheckingKeyExistenceIsDelegatedToClient()
    {
        $key = 'key';
        $this->mockCluster->method('exists')->with($key)->willReturn(true);

        $this->assertTrue($this->store->has($key));
    }

    public function testSettingMultipleKeysIsDelegatedToClient()
    {
        $items = ['key1' => 'foo', 'key2' => 'bar'];

        $this->mockCluster->expects($this->once())->method('mSet')->with($items);
        $this->store->multiSet($items);
    }

    public function testEmptyArrayIsReturnedIfRequestedSnippetKeysArrayIsEmpty()
    {
        $this->assertSame([], $this->store->multiGet(...[]));
    }

    public function testGettingMultipleKeysIsDelegatedToClient()
    {
        $items = ['key1' => 'foo', 'key2' => 'bar'];
        $keys = array_keys($items);

        $this->mockCluster->expects($this->once())->method('mGet')->with($keys)->willReturn($items);

        $this->assertSame($items, $this->store->multiGet(...$keys));
    }
}
