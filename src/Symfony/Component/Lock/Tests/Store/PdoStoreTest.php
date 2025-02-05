<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Tests\Store;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Store\PdoStore;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @requires extension pdo_sqlite
 */
class PdoStoreTest extends AbstractStoreTest
{
    use ExpiringStoreTestTrait;

    protected static $dbFile;

    public static function setUpBeforeClass(): void
    {
        self::$dbFile = tempnam(sys_get_temp_dir(), 'sf_sqlite_lock');

        $store = new PdoStore('sqlite:'.self::$dbFile);
        $store->createTable();
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$dbFile);
    }

    /**
     * {@inheritdoc}
     */
    protected function getClockDelay()
    {
        return 1000000;
    }

    /**
     * {@inheritdoc}
     */
    public function getStore()
    {
        return new PdoStore('sqlite:'.self::$dbFile);
    }

    public function testAbortAfterExpiration()
    {
        $this->markTestSkipped('Pdo expects a TTL greater than 1 sec. Simulating a slow network is too hard');
    }

    /**
     * @expectedException \Symfony\Component\Lock\Exception\InvalidTtlException
     */
    public function testInvalidTtl()
    {
        $store = $this->getStore();
        $store->putOffExpiration(new Key('toto'), 0.1);
    }

    /**
     * @expectedException \Symfony\Component\Lock\Exception\InvalidTtlException
     */
    public function testInvalidTtlConstruct()
    {
        return new PdoStore('sqlite:'.self::$dbFile, [], 0.1, 0.1);
    }
}
