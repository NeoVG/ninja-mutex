<?php
/**
 * This file is part of ninja-mutex.
 *
 * (C) leo108 <root@leo108.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NinjaMutex\Lock;

use \Redis;

/**
 * Lock implementor using PHPRedis
 *
 * @author leo108 <root@leo108.com>
 */
class PhpRedisLock extends LockAbstract
{
    /**
     * phpredis connection
     *
     * @var
     */
    protected $client;

    /**
     * @param $client Redis
     */
    public function __construct(Redis $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * @param  string $name
     * @param  bool   $blocking
     * @param  null|int $ttl
     * @return bool
     */
    protected function getLock($name, $blocking, $ttl = null)
    {
        if ($ttl !== null) {
            return $this->client->set($name, serialize($this->getLockInformation()), array('nx', 'ex' => $ttl));
        } else {
            return $this->client->setnx($name, serialize($this->getLockInformation()));
        }
    }

    /**
     * Release lock
     *
     * @param  string $name name of lock
     * @return bool
     */
    public function releaseLock($name)
    {
        if (
            isset($this->locks[$name])
            && (
                $this->client->del($name)
                || is_int($this->locks[$name])
                    && time() > $this->locks[$name] + $this->ttl[$name]
            )
        ) {
            unset($this->locks[$name]);
            unset($this->ttl[$name]);

            return true;
        }

        return false;
    }

    /**
     * Check if lock is locked
     *
     * @param  string $name name of lock
     * @return bool
     */
    public function isLocked($name)
    {
        return false !== $this->client->get($name);
    }
}
