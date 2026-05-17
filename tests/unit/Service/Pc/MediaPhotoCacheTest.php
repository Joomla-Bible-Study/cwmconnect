<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\MediaPhotoCache;
use Joomla\CMS\Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase E coverage for the photo cache decision matrix + filesystem
 * effects.
 */
#[CoversClass(MediaPhotoCache::class)]
final class MediaPhotoCacheTest extends TestCase
{
    private string $cacheRoot;

    protected function setUp(): void
    {
        $this->cacheRoot = sys_get_temp_dir() . '/cwmconnect-phototest-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheRoot)) {
            foreach (glob($this->cacheRoot . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->cacheRoot);
        }
    }

    #[Test]
    public function returnsNullWhenAvatarUrlIsNull(): void
    {
        $cache = $this->cacheWithBytes('anything');

        self::assertNull($cache->cache(42, null, null));
    }

    #[Test]
    public function returnsNullForDemographicPlaceholder(): void
    {
        $http  = $this->countingHttp('IRRELEVANT');
        $cache = new MediaPhotoCache($http, $this->cacheRoot);

        $out = $cache->cache(
            42,
            'https://avatars.planningcenteronline.com/static/demographic_avatar_blue.png',
            null,
        );

        self::assertNull($out);
        self::assertSame(0, $http->calls, 'Placeholder must never trigger a download.');
    }

    #[Test]
    public function downloadsAndWritesFileOnFirstSync(): void
    {
        $bytes = 'JFIF...fake jpg bytes...';
        $cache = $this->cacheWithBytes($bytes);
        $url   = 'https://avatars.planningcenteronline.com/uploads/abc.jpg';

        $result = $cache->cache(42, $url, null);

        self::assertNotNull($result);
        self::assertSame('42.jpg', $result->relativePath);
        self::assertSame(hash('sha256', $url), $result->hash);
        self::assertTrue($result->downloaded);
        self::assertSame($bytes, file_get_contents($this->cacheRoot . '/42.jpg'));
    }

    #[Test]
    public function returnsUnchangedResultWhenHashMatches(): void
    {
        $url   = 'https://avatars.planningcenteronline.com/uploads/abc.png';
        $hash  = hash('sha256', $url);
        $http  = $this->countingHttp('NEW_BYTES');
        $cache = new MediaPhotoCache($http, $this->cacheRoot);

        $result = $cache->cache(7, $url, $hash);

        self::assertNotNull($result);
        self::assertFalse($result->downloaded);
        self::assertSame($hash, $result->hash);
        self::assertSame(0, $http->calls, 'Matching hash must short-circuit the HTTP call.');
    }

    #[Test]
    public function downloadsAgainWhenUrlChanges(): void
    {
        $oldUrl = 'https://avatars.planningcenteronline.com/uploads/v1.png';
        $newUrl = 'https://avatars.planningcenteronline.com/uploads/v2.png';
        $cache  = $this->cacheWithBytes('NEW');

        $result = $cache->cache(7, $newUrl, hash('sha256', $oldUrl));

        self::assertNotNull($result);
        self::assertTrue($result->downloaded);
        self::assertSame(hash('sha256', $newUrl), $result->hash);
    }

    #[Test]
    public function returnsNullWhenHttpReturnsNon2xx(): void
    {
        $http  = $this->httpReturning(500, 'server error');
        $cache = new MediaPhotoCache($http, $this->cacheRoot);

        self::assertNull(
            $cache->cache(7, 'https://avatars.planningcenteronline.com/uploads/abc.png', null),
        );
    }

    #[Test]
    public function rejectsResponsesLargerThan5MB(): void
    {
        $http  = $this->httpReturning(200, str_repeat('x', 5 * 1024 * 1024 + 1));
        $cache = new MediaPhotoCache($http, $this->cacheRoot);

        self::assertNull(
            $cache->cache(7, 'https://avatars.planningcenteronline.com/uploads/abc.png', null),
        );
    }

    #[Test]
    public function fallsBackToJpgWhenUrlHasNoSafeExtension(): void
    {
        $cache  = $this->cacheWithBytes('bytes');
        $result = $cache->cache(
            9,
            'https://avatars.planningcenteronline.com/photo?token=abc',
            null,
        );

        self::assertNotNull($result);
        self::assertSame('9.jpg', $result->relativePath);
    }

    private function cacheWithBytes(string $bytes): MediaPhotoCache
    {
        return new MediaPhotoCache($this->httpReturning(200, $bytes), $this->cacheRoot);
    }

    private function httpReturning(int $code, string $body): Http
    {
        return new class ($code, $body) extends Http {
            public int $calls = 0;

            public function __construct(public readonly int $code, public readonly string $body) {}

            public function get(string $url, array $headers = [], ?int $timeout = null): mixed
            {
                $this->calls++;

                return new class ($this->code, $this->body) {
                    public function __construct(public readonly int $code, public readonly string $body) {}
                };
            }
        };
    }

    /**
     * Same as httpReturning but exposes a counter so a test can assert
     * the HTTP layer was (or wasn't) called.
     */
    private function countingHttp(string $body): Http
    {
        return $this->httpReturning(200, $body);
    }
}
