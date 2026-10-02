<?php

declare(strict_types=1);

namespace Kylin987\ThinkOrm\RedisCache\Tests\Integration;

use Kylin987\ThinkOrm\RedisCache\Tests\Support\TestCase;
use Kylin987\ThinkOrm\RedisCache\traits\ThinkOrmCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use think\DbManager;
use think\Model;

final class CompositeCacheTest extends TestCase
{
    private DbManager $db;
    private ArrayAdapter $adapter;

    protected function setUp(): void
    {
        $this->loadConfig(['thinkorm.php' => ['cache_always' => true, 'cache_exptime' => 300]]);
        $this->adapter = new ArrayAdapter();
        CacheTestModel::$cache = new Psr16Cache($this->adapter);
        CacheTestModel::$deletedKeys = [];
        $this->db = new DbManager();
        $this->db->setConfig([
            'default' => 'sqlite',
            // 空文件名创建由 SQLite 自动回收的临时数据库，也避免旧 ORM
            // 用 :memory: 生成含 PSR 缓存保留字符的自动清理键。
            'connections' => ['sqlite' => ['type' => 'sqlite', 'database' => '']],
        ]);
        $this->db->setCache(CacheTestModel::$cache);
        Model::setDb($this->db);
        $this->db->execute('CREATE TABLE cache_users (id INTEGER PRIMARY KEY, tenant_id TEXT NOT NULL, user_id TEXT NOT NULL, name TEXT, UNIQUE (tenant_id, user_id))');
        $this->db->table('cache_users')->insertAll([
            ['id' => 1, 'tenant_id' => '10', 'user_id' => '20', 'name' => 'first'],
            ['id' => 2, 'tenant_id' => '11', 'user_id' => '20', 'name' => 'second'],
        ]);
    }

    public function testBothFieldsAreUsedAndExistingRecordIsCached(): void
    {
        self::assertSame('first', CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20])->name);
        self::assertSame('second', CompositeTestModel::getRedisCache(['tenant_id' => 11, 'user_id' => 20])->name);
        $this->db->table('cache_users')->where('id', 1)->update(['name' => 'changed in database']);
        // 字段顺序和整数/数字字符串差异不影响缓存命中。
        self::assertSame('first', CompositeTestModel::getRedisCache(['user_id' => '20', 'tenant_id' => '10'])->name);
    }

    public function testForceRefreshReadsDatabase(): void
    {
        CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]);
        $this->db->table('cache_users')->where('id', 1)->update(['name' => 'fresh']);
        self::assertSame('fresh', CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20], true)->name);
        self::assertCount(1, CacheTestModel::$deletedKeys);
    }

    public function testMissingRecordIsNotCachedEvenWithCacheAlwaysEnabled(): void
    {
        self::assertNull(CompositeTestModel::getRedisCache(['tenant_id' => 0, 'user_id' => 0]));
        self::assertSame([], $this->cachedValues());
        $this->db->table('cache_users')->insert(['id' => 3, 'tenant_id' => '0', 'user_id' => '0', 'name' => 'new']);
        self::assertSame('new', CompositeTestModel::getRedisCache(['tenant_id' => 0, 'user_id' => 0])->name);
    }

    public function testUpdateEventClearsOldAndNewCombinations(): void
    {
        $user = CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]);
        $oldKey = array_key_first($this->cachedValues());
        $user->save(['tenant_id' => '12', 'user_id' => '21', 'name' => 'moved']);

        self::assertCount(2, CacheTestModel::$deletedKeys);
        self::assertContains($oldKey, CacheTestModel::$deletedKeys);
        self::assertFalse(CacheTestModel::$cache->has($oldKey));
        self::assertNull(CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]));
        self::assertSame('moved', CompositeTestModel::getRedisCache(['tenant_id' => 12, 'user_id' => 21])->name);

        // 确认删除的另一个键就是新组合查询生成的键。
        self::assertContains(array_key_first($this->cachedValues()), CacheTestModel::$deletedKeys);
    }

    public function testUnchangedCombinationIsDeletedOnlyOnce(): void
    {
        $user = CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]);
        $user->save(['name' => 'updated']);
        self::assertCount(1, CacheTestModel::$deletedKeys);
        self::assertSame('updated', CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20])->name);
    }

    public function testDeleteEventClearsCache(): void
    {
        $user = CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]);
        $key = array_key_first($this->cachedValues());
        $user->delete();
        self::assertFalse(CacheTestModel::$cache->has($key));
        self::assertCount(1, CacheTestModel::$deletedKeys);
        self::assertNull(CompositeTestModel::getRedisCache(['tenant_id' => 10, 'user_id' => 20]));
    }

    public function testFieldBoundariesCannotCollide(): void
    {
        $this->db->table('cache_users')->insertAll([
            ['id' => 3, 'tenant_id' => 'a_b', 'user_id' => 'c', 'name' => 'left'],
            ['id' => 4, 'tenant_id' => 'a', 'user_id' => 'b_c', 'name' => 'right'],
        ]);
        self::assertSame('left', CompositeTestModel::getRedisCache(['tenant_id' => 'a_b', 'user_id' => 'c'])->name);
        self::assertSame('right', CompositeTestModel::getRedisCache(['tenant_id' => 'a', 'user_id' => 'b_c'])->name);
        self::assertCount(2, $this->cachedValues());
    }

    public function testInvalidParametersDoNotDeleteOrQuery(): void
    {
        $invalidValues = [
            10,
            ['tenant_id' => 10],
            ['tenant_id' => 10, 'user_id' => 20, 'name' => 'extra'],
            ['tenant_id' => 10, 'name' => 20],
            ['tenant_id' => null, 'user_id' => 20],
            ['tenant_id' => false, 'user_id' => 20],
            ['tenant_id' => 1.5, 'user_id' => 20],
            ['tenant_id' => [], 'user_id' => 20],
        ];
        foreach ($invalidValues as $values) {
            try {
                CompositeTestModel::getRedisCache($values, true);
                self::fail('Invalid composite parameters were accepted');
            } catch (\InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
        self::assertSame([], CacheTestModel::$deletedKeys);
        self::assertSame([], $this->cachedValues());
    }

    public function testPartialModelCannotSilentlyClearTheWrongKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CompositeTestModel::delCache(new CompositeTestModel(['id' => 1, 'user_id' => 20]));
    }

    public function testSingleFieldKeyAndEmptyCachingArePreserved(): void
    {
        $user = CacheTestModel::getRedisCache(1);
        self::assertSame('first', $user->name);
        self::assertTrue(CacheTestModel::$cache->has('orm_cache_users_id_1'));
        CacheTestModel::delCache($user);
        self::assertSame(['orm_cache_users_id_1'], CacheTestModel::$deletedKeys);
        self::assertNull(CacheTestModel::getRedisCache(99));
        self::assertTrue(CacheTestModel::$cache->has('orm_cache_users_id_99'));
    }

    private function cachedValues(): array
    {
        // Symfony 6 的 ArrayAdapter 会为读取未命中的键保留 null 占位。
        return array_filter($this->adapter->getValues(), static fn ($value) => $value !== null);
    }
}

class CacheTestModel extends Model
{
    use ThinkOrmCache;

    protected $table = 'cache_users';
    protected $autoWriteTimestamp = false;
    public static Psr16Cache $cache;
    public static array $deletedKeys = [];

    // 仅替换 Redis 删除传输，查询、缓存和模型事件均使用真实 ThinkORM。
    private static function delKey($key)
    {
        self::$deletedKeys[] = $key;
        $exists = self::$cache->has($key);
        self::$cache->delete($key);
        return (int) $exists;
    }

    public static function onAfterUpdate(Model $model): void
    {
        self::delCache($model);
    }

    public static function onAfterDelete(Model $model): void
    {
        self::delCache($model);
    }
}

class CompositeTestModel extends CacheTestModel
{
    protected $cachePk = ['tenant_id', 'user_id'];
}
