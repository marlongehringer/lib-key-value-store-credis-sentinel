<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\DataPool\KeyValue\Credis;

use Credis_Cluster;
use LizardsAndPumpkins\DataPool\KeyValueStore\Exception\KeyNotFoundException;
use LizardsAndPumpkins\DataPool\KeyValueStore\KeyValueStore;

class CredisSentinelKeyValueStore implements KeyValueStore
{
    /**
     * @var Credis_Cluster
     */
    private $cluster;

    public function __construct(Credis_Cluster $cluster)
    {
        $this->cluster = $cluster;
    }

    public function get(string $key) : string
    {
        $value = $this->cluster->get($key);

        if (false === $value) {
            throw new KeyNotFoundException(sprintf('Key not found "%s"', $key));
        }

        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $this->cluster->set($key, $value);
    }

    public function has(string $key) : bool
    {
        return (bool) $this->cluster->exists($key);
    }

    /**
     * @param string[] $keys
     * @return mixed[]
     */
    public function multiGet(string ...$keys) : array
    {
        if (count($keys) === 0) {
            return [];
        }

        $values = $this->getClient()->mGet($keys);
        $items = array_combine($keys, $values);

        return array_filter($items);
    }

    /**
     * @param mixed[] $items
     */
    public function multiSet(array $items)
    {
        $this->getClient()->mSet($items);
    }

    /**
     * @return \Credis_Client
     */
    public function getClient()
    {
        $clients = $this->cluster->clients();
        $key = array_rand($clients);

        return $clients[$key];
    }
}
