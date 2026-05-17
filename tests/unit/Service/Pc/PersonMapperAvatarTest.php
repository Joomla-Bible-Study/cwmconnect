<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase E coverage for {@see PersonMapper::extractAvatarUrl()}.
 */
#[CoversClass(PersonMapper::class)]
final class PersonMapperAvatarTest extends TestCase
{
    #[Test]
    public function returnsAvatarAttributeWhenSet(): void
    {
        $url = 'https://avatars.planningcenteronline.com/uploads/abc.png';
        $out = new PersonMapper()->extractAvatarUrl([
            'attributes' => ['avatar' => $url],
        ]);

        self::assertSame($url, $out);
    }

    #[Test]
    public function returnsNullWhenAvatarMissing(): void
    {
        self::assertNull(new PersonMapper()->extractAvatarUrl(['attributes' => []]));
    }

    #[Test]
    public function returnsNullWhenAvatarEmptyString(): void
    {
        self::assertNull(
            new PersonMapper()->extractAvatarUrl(['attributes' => ['avatar' => '']]),
        );
    }

    #[Test]
    public function doesNotFallBackToDemographicAvatarUrl(): void
    {
        // Decision: `demographic_avatar_url` is PC's generic placeholder;
        // we never treat it as a real photo. The cache layer also detects
        // placeholders defensively, but the mapper is the first filter.
        $out = new PersonMapper()->extractAvatarUrl([
            'attributes' => [
                'avatar'                 => null,
                'demographic_avatar_url' => 'https://avatars.planningcenteronline.com/static/demographic_avatar_42.png',
            ],
        ]);

        self::assertNull($out);
    }
}
