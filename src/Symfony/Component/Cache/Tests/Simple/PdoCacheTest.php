<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Simple;

use Symfony\Bridge\PhpUnit\ForwardCompatTestTrait;
use Symfony\Component\Cache\Simple\PdoCache;
use Symfony\Component\Cache\Tests\Traits\PdoPruneableTrait;

/**
 * @group time-sensitive
 * @group legacy
 */
class PdoCacheTest extends CacheTestCase
{
    use ForwardCompatTestTrait;
    use PdoPruneableTrait;

    protected static $dbFile;

    private static function doSetUpBeforeClass()
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('Extension pdo_sqlite required.');
        }

        self::$dbFile = tempnam(sys_get_temp_dir(), 'sf_sqlite_cache');

        $pool = new PdoCache('sqlite:'.self::$dbFile);
        $pool->createTable();
    }

    private static function doTearDownAfterClass()
    {
        @unlink(self::$dbFile);
    }

    public function createSimpleCache($defaultLifetime = 0)
    {
        return new PdoCache('sqlite:'.self::$dbFile, 'ns', $defaultLifetime);
    }
}
