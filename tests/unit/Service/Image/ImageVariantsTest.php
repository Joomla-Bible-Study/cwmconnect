<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Image;

use CWM\Component\Cwmconnect\Administrator\Service\Image\ImageVariants;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for {@see ImageVariants}.
 */
#[CoversClass(ImageVariants::class)]
final class ImageVariantsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        if (!\function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension not available.');
        }

        $this->dir = sys_get_temp_dir() . '/cwm_variants_' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($this->dir);
    }

    private function makeSource(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 140, 90));
        $path = $this->dir . '/src.jpg';
        imagejpeg($im, $path, 90);
        imagedestroy($im);

        return $path;
    }

    #[Test]
    public function generatesEverySizeAndFormatAtTheRightDimensions(): void
    {
        $written = new ImageVariants()->generate($this->makeSource(1000, 1000), $this->dir, '42');

        $expectWebp = \function_exists('imagewebp');
        $expectedCount = $expectWebp ? 4 : 2;

        self::assertCount($expectedCount, $written);

        // The JPEG fallbacks always exist and carry the configured dimensions.
        foreach (ImageVariants::SIZES as $size => [$w, $h]) {
            $jpg = $this->dir . '/' . ImageVariants::variantFilename('42', $size, 'jpg');
            self::assertFileExists($jpg);

            [$gotW, $gotH] = getimagesize($jpg);
            self::assertSame($w, $gotW, "$size width");
            self::assertSame($h, $gotH, "$size height");
        }
    }

    #[Test]
    public function variantFilenameIsDeterministic(): void
    {
        self::assertSame('99-thumb.webp', ImageVariants::variantFilename('99', 'thumb', 'webp'));
        self::assertSame('99-medium.jpg', ImageVariants::variantFilename('99', 'medium', 'jpg'));
    }
}
