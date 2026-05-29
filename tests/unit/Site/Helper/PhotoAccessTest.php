<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Helper;

use CWM\Component\Cwmconnect\Site\Helper\PhotoAccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

if (!\defined('JPATH_ROOT')) {
    \define('JPATH_ROOT', sys_get_temp_dir() . '/cwm_photoaccess_root');
}

/**
 * Coverage for the photo-proxy gatekeeper decision + path logic.
 */
#[CoversClass(PhotoAccess::class)]
final class PhotoAccessTest extends TestCase
{
    private static function member(array $overrides): object
    {
        return (object) array_merge([
            'published' => 1, 'display_in_directory' => 1, 'directory_scope' => 'public', 'funitid' => 0,
        ], $overrides);
    }

    #[Test]
    public function managerSeesEveryPhotoIncludingHidden(): void
    {
        $hidden = self::member(['display_in_directory' => 0, 'directory_scope' => 'hidden']);
        self::assertTrue(PhotoAccess::canView(true, $hidden, null));
    }

    #[Test]
    public function missingMemberIsDenied(): void
    {
        self::assertFalse(PhotoAccess::canView(true, null, null));
        self::assertFalse(PhotoAccess::canView(false, null, 5));
    }

    #[Test]
    public function publicMemberIsVisibleToAnyMember(): void
    {
        self::assertTrue(PhotoAccess::canView(false, self::member([]), null));
    }

    #[Test]
    public function unpublishedOrHiddenMemberIsDeniedToMembers(): void
    {
        self::assertFalse(PhotoAccess::canView(false, self::member(['published' => 0]), 1));
        self::assertFalse(PhotoAccess::canView(false, self::member(['display_in_directory' => 0]), 1));
        self::assertFalse(PhotoAccess::canView(false, self::member(['directory_scope' => 'hidden']), 1));
    }

    #[Test]
    public function householdScopeRequiresSameHousehold(): void
    {
        $member = self::member(['directory_scope' => 'household', 'funitid' => 42]);

        self::assertTrue(PhotoAccess::canView(false, $member, 42), 'same household sees photo');
        self::assertFalse(PhotoAccess::canView(false, $member, 7), 'other household does not');
        self::assertFalse(PhotoAccess::canView(false, $member, null), 'no household does not');
    }

    #[Test]
    public function resolvePathRejectsBlankRemoteAndTraversal(): void
    {
        self::assertNull(PhotoAccess::resolvePath(''));
        self::assertNull(PhotoAccess::resolvePath('https://evil.example/x.jpg'));
        self::assertNull(PhotoAccess::resolvePath('../../configuration.php'));
        self::assertNull(PhotoAccess::resolvePath('does-not-exist.jpg'));
    }

    #[Test]
    public function resolvePathReturnsAbsolutePathForExistingFile(): void
    {
        $dir = JPATH_ROOT . '/media/com_cwmconnect/photos';
        @mkdir($dir, 0o755, true);
        $file = $dir . '/999.jpg';
        file_put_contents($file, 'x');

        self::assertSame($file, PhotoAccess::resolvePath('999.jpg'));

        @unlink($file);
    }

    #[Test]
    public function contentTypeMapsByExtension(): void
    {
        self::assertSame('image/png', PhotoAccess::contentType('/a/b.png'));
        self::assertSame('image/gif', PhotoAccess::contentType('/a/b.GIF'));
        self::assertSame('image/webp', PhotoAccess::contentType('/a/b.webp'));
        self::assertSame('image/jpeg', PhotoAccess::contentType('/a/b.jpg'));
        self::assertSame('image/jpeg', PhotoAccess::contentType('/a/b.unknown'));
    }
}
