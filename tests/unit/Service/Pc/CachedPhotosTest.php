<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\CachedPhotos;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forgetting a person deletes their photo, its web copies and its thumbnail,
 * matching the id whole: person 12's files go, and 120's, 123's and the
 * household photos stay.
 */
#[CoversClass(CachedPhotos::class)]
final class CachedPhotosTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/cwm-photos-' . bin2hex(random_bytes(4));

        foreach (['web', 'thumb', 'households'] as $dir) {
            mkdir($this->root . '/' . $dir, 0o777, true);
        }

        foreach ([
            '12.jpg', '12.png', 'web/12-medium.jpg', 'web/12-medium.webp', 'web/12-thumb.jpg', 'thumb/12.jpg',
            '120.jpg', '123.jpg', 'web/120-thumb.jpg', 'web/123-medium.webp', 'thumb/123.jpg', 'households/12.jpg',
        ] as $file) {
            touch($this->root . '/' . $file);
        }

        // A link is followed by neither glob nor unlink here: only the link
        // would go, and it was never the sync's to delete.
        symlink($this->root . '/123.jpg', $this->root . '/web/12-linked.jpg');
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }

    #[Test]
    public function forgettingAPersonDeletesOnlyTheirFiles(): void
    {
        self::assertSame(6, new CachedPhotos($this->root)->forget(12));

        self::assertSame(
            ['120.jpg', '123.jpg', 'households/12.jpg', 'thumb/123.jpg', 'web/12-linked.jpg', 'web/120-thumb.jpg', 'web/123-medium.webp'],
            $this->remaining(),
        );
    }

    #[Test]
    public function aNonPositiveIdDeletesNothing(): void
    {
        self::assertSame(0, new CachedPhotos($this->root)->forget(0));
        self::assertCount(13, $this->remaining());
    }

    /**
     * @return list<string>  Every file left, relative to the root, sorted.
     */
    private function remaining(): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[] = substr($file->getPathname(), \strlen($this->root) + 1);
        }

        sort($files);

        return $files;
    }
}
