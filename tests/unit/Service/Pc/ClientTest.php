<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ApiException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\AuthenticationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ConfigurationException;
use Joomla\CMS\Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    #[Test]
    public function constructorRejectsEmptyToken(): void
    {
        $this->expectException(ConfigurationException::class);

        new Client($this->fakeHttp(), '');
    }

    #[Test]
    public function meReturnsDecodedDataPayload(): void
    {
        $http = $this->fakeHttp(200, json_encode([
            'data' => [
                'type'       => 'Person',
                'id'         => '12345',
                'attributes' => ['first_name' => 'Brent', 'last_name' => 'Cordis'],
            ],
        ]));

        $client = new Client($http, 'test-token');
        $me     = $client->me();

        self::assertSame('Person', $me['type']);
        self::assertSame('12345', $me['id']);
        self::assertSame('Brent', $me['attributes']['first_name']);
    }

    #[Test]
    public function authorizationHeaderUsesBearerScheme(): void
    {
        $http = $this->fakeHttp(200, '{"data":{}}');

        $client = new Client($http, 'sekret');
        $client->me();

        self::assertSame('Bearer sekret', $http->capturedHeaders['Authorization'] ?? null);
    }

    #[Test]
    public function apiVersionHeaderIsPinned(): void
    {
        $http = $this->fakeHttp(200, '{"data":{}}');

        $client = new Client($http, 't');
        $client->me();

        self::assertSame(Client::API_VERSION, $http->capturedHeaders['X-PCO-API-Version'] ?? null);
    }

    #[Test]
    public function applicationIdHeaderIsOmittedWhenEmpty(): void
    {
        $http = $this->fakeHttp(200, '{"data":{}}');

        $client = new Client($http, 't');
        $client->me();

        self::assertArrayNotHasKey('X-PCO-Application-Id', $http->capturedHeaders);
    }

    #[Test]
    public function applicationIdHeaderIsSentWhenProvided(): void
    {
        $http = $this->fakeHttp(200, '{"data":{}}');

        $client = new Client($http, 't', 'app-id-here');
        $client->me();

        self::assertSame('app-id-here', $http->capturedHeaders['X-PCO-Application-Id'] ?? null);
    }

    #[Test]
    public function unauthorisedResponseRaisesAuthenticationException(): void
    {
        $http = $this->fakeHttp(401, '{"errors":[{"detail":"bad token"}]}');

        $client = new Client($http, 'bad-token');

        $this->expectException(AuthenticationException::class);

        $client->me();
    }

    #[Test]
    public function forbiddenResponseAlsoRaisesAuthenticationException(): void
    {
        $http = $this->fakeHttp(403, '{}');

        $client = new Client($http, 't');

        $this->expectException(AuthenticationException::class);

        $client->me();
    }

    #[Test]
    public function serverErrorRaisesApiExceptionWithStatusCode(): void
    {
        $http = $this->fakeHttp(503, 'Service Unavailable');

        $client = new Client($http, 't');

        try {
            $client->me();
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(503, $e->statusCode);
            self::assertSame('Service Unavailable', $e->responseBody);
        }
    }

    #[Test]
    public function malformedJsonRaisesApiException(): void
    {
        $http = $this->fakeHttp(200, 'not json');

        $client = new Client($http, 't');

        $this->expectException(ApiException::class);

        $client->me();
    }

    #[Test]
    public function missingDataEnvelopeRaisesApiException(): void
    {
        $http = $this->fakeHttp(200, '{"unexpected":"shape"}');

        $client = new Client($http, 't');

        $this->expectException(ApiException::class);

        $client->me();
    }

    #[Test]
    public function transportFailureWrapsAsApiException(): void
    {
        $http = new class extends Http {
            public function get(string $url, array $headers = [], ?int $timeout = null): mixed
            {
                throw new \RuntimeException('connection refused');
            }
        };

        $client = new Client($http, 't');

        try {
            $client->me();
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertStringContainsString('connection refused', $e->getMessage());
        }
    }

    #[Test]
    public function getJsonAppendsQueryStringWhenProvided(): void
    {
        $http = $this->fakeHttp(200, '{"data":[]}');

        $client = new Client($http, 't');
        $client->getJson('/people/v2/people', ['per_page' => '25', 'where[status]' => 'active']);

        self::assertStringContainsString('per_page=25', $http->capturedUrl);
        self::assertStringContainsString('where', $http->capturedUrl);
    }

    /**
     * Build an anonymous Http subclass that captures the call args and returns
     * a stub Response with the given code + body.
     */
    private function fakeHttp(int $code = 200, string $body = '{"data":{}}'): Http
    {
        return new class ($code, $body) extends Http {
            public string $capturedUrl = '';

            /** @var array<string, string> */
            public array $capturedHeaders = [];

            public function __construct(private readonly int $code, private readonly string $body) {}

            public function get(string $url, array $headers = [], ?int $timeout = null): object
            {
                $this->capturedUrl     = $url;
                $this->capturedHeaders = $headers;

                $code = $this->code;
                $body = $this->body;

                return new class ($code, $body) {
                    public function __construct(public readonly int $code, public readonly string $body) {}
                };
            }
        };
    }
}
