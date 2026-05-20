<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Helper;

use CWM\Component\Cwmconnect\Site\Helper\LoginWall;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase G coverage for the login-wall decision matrix.
 */
#[CoversClass(LoginWall::class)]
final class LoginWallTest extends TestCase
{
    #[Test]
    public function loggedInUserIsLetThrough(): void
    {
        self::assertNull(LoginWall::redirectForGuest(42, 'index.php?option=com_cwmconnect&view=members'));
    }

    #[Test]
    public function guestIsRedirectedToLoginWithBase64EncodedReturnUrl(): void
    {
        $current = 'index.php?option=com_cwmconnect&view=members';

        $redirect = LoginWall::redirectForGuest(0, $current);

        self::assertNotNull($redirect);
        self::assertStringContainsString('option=com_users', $redirect);
        self::assertStringContainsString('view=login', $redirect);
        self::assertStringContainsString('return=' . base64_encode($current), $redirect);
    }

    #[Test]
    public function negativeUserIdsAreTreatedAsGuests(): void
    {
        self::assertNotNull(LoginWall::redirectForGuest(-1, 'index.php?option=com_cwmconnect'));
    }
}
