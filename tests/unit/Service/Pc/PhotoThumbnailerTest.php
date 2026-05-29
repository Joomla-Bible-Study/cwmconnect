<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoThumbnailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * K.7 coverage for {@see PhotoThumbnailer}.
 */
#[CoversClass(PhotoThumbnailer::class)]
final class PhotoThumbnailerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        if (!\function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension not available.');
        }

        $this->dir = sys_get_temp_dir() . '/cwm_thumb_' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir . '/thumb');
        @rmdir($this->dir);
    }

    private function makeSource(int $w, int $h): string
    {
        $im   = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 10, 120, 200));
        $path = $this->dir . "/src_{$w}x{$h}.jpg";
        imagejpeg($im, $path, 90);
        imagedestroy($im);

        return $path;
    }

    #[Test]
    public function producesFixedAspectThumbnailFromAnySource(): void
    {
        $thumbnailer = new PhotoThumbnailer(300, 400);

        foreach ([[1200, 300], [300, 1200], [500, 500]] as [$w, $h]) {
            $src  = $this->makeSource($w, $h);
            $dest = $this->dir . "/out_{$w}x{$h}.jpg";

            self::assertTrue($thumbnailer->generate($src, $dest), "generate {$w}x{$h}");

            [$ow, $oh] = getimagesize($dest);
            self::assertSame(300, $ow, "width {$w}x{$h}");
            self::assertSame(400, $oh, "height {$w}x{$h}");
        }
    }

    #[Test]
    public function createsDestinationDirectory(): void
    {
        $src  = $this->makeSource(400, 400);
        $dest = $this->dir . '/thumb/nested.jpg';

        self::assertTrue(new PhotoThumbnailer()->generate($src, $dest));
        self::assertFileExists($dest);
    }

    #[Test]
    public function returnsFalseForMissingOrInvalidSource(): void
    {
        self::assertFalse(new PhotoThumbnailer()->generate($this->dir . '/nope.jpg', $this->dir . '/out.jpg'));

        $notImage = $this->dir . '/notimage.jpg';
        file_put_contents($notImage, 'this is not an image');
        self::assertFalse(new PhotoThumbnailer()->generate($notImage, $this->dir . '/out.jpg'));
    }

    #[Test]
    public function placeholderProducesFixedSizeImage(): void
    {
        $dest = $this->dir . '/ph.jpg';

        self::assertTrue(new PhotoThumbnailer(300, 400)->placeholder('SB', $dest));

        [$w, $h] = getimagesize($dest);
        self::assertSame(300, $w);
        self::assertSame(400, $h);
    }

    #[Test]
    public function thumbFilenameKeepsBareStemAndHashesPaths(): void
    {
        self::assertSame('123.jpg', PhotoThumbnailer::thumbFilename('123.jpg'));
        self::assertSame('123.jpg', PhotoThumbnailer::thumbFilename('123.png'));
        self::assertSame('', PhotoThumbnailer::thumbFilename(''));

        $hashed = PhotoThumbnailer::thumbFilename('images/members/foo.jpg');
        self::assertMatchesRegularExpression('/^[0-9a-f]{40}\.jpg$/', $hashed);
    }
}
