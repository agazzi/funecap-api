<?php

namespace App\Service;

use Predis\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisService
{
    /**
     * 1 hour in seconds
     *
     * @var int
     */
    public const DEFAULT_TTL = 3600;

    /**
     * @var string
     */
    private string $prefix;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @param Client $client
     * @param ParameterBagInterface $bag
     */
    public function __construct(Client $client, ParameterBagInterface $bag)
    {
        $this->prefix = $bag->get('redis')['prefix'];
        $this->client = $client;
    }

    /**
     * @param string $key
     *
     * @return null|string|array
     */
    public function get(string $key): null|array|string
    {
        $key = md5(sprintf('%s.%s', $this->prefix, $key));

        if (!$this->client->exists($key)) {
            return null;
        }

        return json_decode($this->client->get($key), true);
    }

    /**
     * Function that create a new redis key
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expire
     *
     * @return void
     */
    public function set(string $key, mixed $value, int $expire = null): void
    {
        $keycode = md5(sprintf('%s.%s', $this->prefix, $key));

        if ($this->has($key)) {
            $this->delete($key);
        }

        $this->client->set($keycode, json_encode($value));

        if ($expire) {
            $this->setExpireTime($key, $expire);
        }
    }

    /**
     * Function that check a redis key exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $keycode = md5(sprintf('%s.%s', $this->prefix, $key));

        return $this->client->exists($keycode) ?? false;
    }

    /**
     * Function that remove a redis key
     *
     * @param string $key
     *
     * @return void
     */
    public function delete(string $key): void
    {
        $keycode = md5(sprintf('%s.%s', $this->prefix, $key));

        if (!$this->has($key)) {
            return;
        }

        $this->client->del([$keycode]);
    }

    /**
     * Function that set an expiry time to the key
     *
     * @param string $key
     * @param int $seconds
     *
     * @return void
     */
    private function setExpireTime(string $key, int $seconds = self::DEFAULT_TTL): void
    {
        $keycode = md5(sprintf('%s.%s', $this->prefix, $key));

        $this->client->expire($keycode, $seconds);
    }
}
