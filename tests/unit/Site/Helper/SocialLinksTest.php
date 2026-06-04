<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Helper;

use CWM\Component\Cwmconnect\Site\Helper\SocialLinks;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for decoding the `pc_social` column into render-ready links.
 */
#[CoversClass(SocialLinks::class)]
final class SocialLinksTest extends TestCase
{
    #[Test]
    public function blankOrInvalidJsonYieldsEmptyList(): void
    {
        self::assertSame([], SocialLinks::fromJson(null));
        self::assertSame([], SocialLinks::fromJson(''));
        self::assertSame([], SocialLinks::fromJson('not json'));
        self::assertSame([], SocialLinks::fromJson('"a string"'));
    }

    #[Test]
    public function decodesRealPcPayloadWithNormalisedKeysAndLabels(): void
    {
        $json = json_encode([
            ['site' => 'Twitter', 'url' => 'https://twitter.com/bcordis'],
            ['site' => 'Facebook', 'url' => 'https://www.facebook.com/bcordis'],
            ['site' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/bcordis'],
            ['site' => 'Instagram', 'url' => 'https://www.instagram.com/bcordis'],
        ]);

        $links = SocialLinks::fromJson($json);

        self::assertCount(4, $links);
        // Twitter normalises to the 'x' platform key + "X" label.
        self::assertSame('x', $links[0]['key']);
        self::assertSame('X', $links[0]['label']);
        self::assertSame('https://twitter.com/bcordis', $links[0]['url']);
        self::assertSame('facebook', $links[1]['key']);
        self::assertSame('linkedin', $links[2]['key']);
        self::assertSame('instagram', $links[3]['key']);
    }

    #[Test]
    public function dropsEntriesWithoutAnHttpUrl(): void
    {
        $json = json_encode([
            ['site' => 'Facebook', 'url' => ''],
            ['site' => 'Twitter', 'url' => 'javascript:alert(1)'],
            ['site' => 'Instagram', 'url' => 'https://instagram.com/ok'],
            'not-an-array',
        ]);

        $links = SocialLinks::fromJson($json);

        self::assertCount(1, $links);
        self::assertSame('instagram', $links[0]['key']);
    }

    #[Test]
    public function unknownPlatformFallsBackToSlugAndSiteLabel(): void
    {
        $json = json_encode([
            ['site' => 'My Blog', 'url' => 'https://example.com/blog'],
            ['site' => '', 'url' => 'https://example.org'],
        ]);

        $links = SocialLinks::fromJson($json);

        self::assertSame('my-blog', $links[0]['key']);
        self::assertSame('My Blog', $links[0]['label']);
        // No site label → generic 'website' key + label.
        self::assertSame('website', $links[1]['key']);
        self::assertSame('Website', $links[1]['label']);
    }

    #[Test]
    public function resolvesPlatformFromUrlHostWhenSiteLabelIsAmbiguous(): void
    {
        $json = json_encode([
            ['site' => 'Profile', 'url' => 'https://www.linkedin.com/in/someone'],
        ]);

        $links = SocialLinks::fromJson($json);

        self::assertSame('linkedin', $links[0]['key']);
        self::assertSame('LinkedIn', $links[0]['label']);
    }
}
