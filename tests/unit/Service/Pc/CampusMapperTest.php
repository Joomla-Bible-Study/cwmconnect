<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\CampusMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * K.6 coverage for {@see CampusMapper::map()}.
 */
#[CoversClass(CampusMapper::class)]
final class CampusMapperTest extends TestCase
{
    #[Test]
    public function mapsAllCampusAttributes(): void
    {
        $out = new CampusMapper()->map([
            'id'         => '63468',
            'attributes' => [
                'name'                  => 'NFSDA Church',
                'street'                => '2800 Blair Boulevard',
                'city'                  => 'Nashville',
                'state'                 => 'TN',
                'zip'                   => '37213',
                'country'               => 'US',
                'phone_number'          => '(615) 555-0100',
                'contact_email_address' => 'office@example.com',
                'website'               => 'https://example.com',
            ],
        ]);

        self::assertSame([
            'pc_campus_id' => 63468,
            'name'         => 'NFSDA Church',
            'pc_street'    => '2800 Blair Boulevard',
            'pc_city'      => 'Nashville',
            'pc_state'     => 'TN',
            'pc_zip'       => '37213',
            'pc_country'   => 'US',
            'pc_phone'     => '(615) 555-0100',
            'pc_email'     => 'office@example.com',
            'pc_website'   => 'https://example.com',
        ], $out);
    }

    #[Test]
    public function returnsNullWhenIdMissingOrZero(): void
    {
        self::assertNull(new CampusMapper()->map(['attributes' => ['name' => 'X']]));
        self::assertNull(new CampusMapper()->map(['id' => '0', 'attributes' => []]));
    }

    #[Test]
    public function blankAttributesBecomeEmptyStrings(): void
    {
        $out = new CampusMapper()->map(['id' => 5, 'attributes' => ['name' => 'Solo']]);

        self::assertSame(5, $out['pc_campus_id']);
        self::assertSame('Solo', $out['name']);
        self::assertSame('', $out['pc_street']);
        self::assertSame('', $out['pc_phone']);
        self::assertSame('', $out['pc_website']);
    }

    #[Test]
    public function trimsWhitespaceFromValues(): void
    {
        $out = new CampusMapper()->map([
            'id'         => 7,
            'attributes' => ['name' => '  Trinity  ', 'city' => "  Reno\n"],
        ]);

        self::assertSame('Trinity', $out['name']);
        self::assertSame('Reno', $out['pc_city']);
    }
}
