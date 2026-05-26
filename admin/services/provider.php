<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

\defined('_JEXEC') or die;

use CWM\Component\Cwmconnect\Administrator\Extension\CwmconnectComponent;
use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Administrator\Service\Pairing\DatabaseMemberPairing;
use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client as PcClient;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CustomFieldWriterInterface as PcCustomFieldWriterInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseFieldMapRepository as PcDatabaseFieldMapRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\DatabaseMemberRepository as PcDatabaseMemberRepository;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ConfigurationException as PcConfigurationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldMapRepositoryInterface as PcFieldMapRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldsHelperWriter as PcFieldsHelperWriter;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MediaPhotoCache as PcMediaPhotoCache;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface as PcMemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper as PcPersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoCacheInterface as PcPhotoCacheInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine as PcSyncEngine;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new CategoryFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new MVCFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new RouterFactory('\\CWM\\Component\\Cwmconnect'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new CwmconnectComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
                $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );

        // Planning Center client. Resolves lazily — reads component params at
        // first request so config edits take effect without a service rebuild.
        // Throws PcConfigurationException if the token is empty; callers
        // (Phase C sync entry point) catch and surface a "not configured"
        // state instead of letting the exception bubble.
        $container->set(
            PcClient::class,
            static function (): PcClient {
                $params  = ComponentHelper::getParams('com_cwmconnect');
                $token   = (string) $params->get('pc_personal_access_token', '');
                $appId   = (string) $params->get('pc_application_id', '');
                $baseUrl = (string) $params->get('pc_api_base_url', PcClient::DEFAULT_BASE_URL);

                if ($token === '') {
                    throw new PcConfigurationException(
                        'Planning Center is not configured: personal access token is empty.',
                    );
                }

                return new PcClient(
                    http: HttpFactory::getHttp(),
                    personalAccessToken: $token,
                    applicationId: $appId,
                    baseUrl: $baseUrl !== '' ? $baseUrl : PcClient::DEFAULT_BASE_URL,
                );
            },
        );

        // PC sync support services: stateless mapper, DB-backed repository,
        // and the engine that ties them together. Resolved per request so
        // tests can override individual collaborators via the container.
        $container->set(
            PcPersonMapper::class,
            static fn(): PcPersonMapper => new PcPersonMapper(),
        );

        $container->set(
            PcMemberRepositoryInterface::class,
            static fn(Container $c): PcMemberRepositoryInterface
                => new PcDatabaseMemberRepository($c->get(DatabaseInterface::class)),
        );

        // Phase D: PC custom-field mapping repo + writer. Engine constructor
        // accepts them as nullable (Phase C parity), but in production we
        // always wire them so an admin who's saved mappings sees them used.
        $container->set(
            PcFieldMapRepositoryInterface::class,
            static fn(Container $c): PcFieldMapRepositoryInterface
                => new PcDatabaseFieldMapRepository($c->get(DatabaseInterface::class)),
        );

        $container->set(
            PcCustomFieldWriterInterface::class,
            static fn(): PcCustomFieldWriterInterface => new PcFieldsHelperWriter(),
        );

        // Phase E: avatar cache. Resolves the cache root at request time so
        // a relocated Joomla install (JPATH_ROOT change after a move) keeps
        // pointing at the right photos directory.
        $container->set(
            PcPhotoCacheInterface::class,
            static fn(): PcPhotoCacheInterface => new PcMediaPhotoCache(
                http: HttpFactory::getHttp(),
                cacheRoot: JPATH_ROOT . '/media/com_cwmconnect/photos',
            ),
        );

        // Phase J: KML feed token service shared with site KmlView.
        $container->set(
            FeedTokenService::class,
            static fn(Container $c): FeedTokenService
                => new FeedTokenService($c->get(DatabaseInterface::class)),
        );

        // Phase H: identity-binding pair service shared with plg_user_cwmconnect.
        $container->set(
            MemberPairingInterface::class,
            static fn(Container $c): MemberPairingInterface
                => new DatabaseMemberPairing($c->get(DatabaseInterface::class)),
        );

        $container->set(
            PcSyncEngine::class,
            static fn(Container $c): PcSyncEngine => new PcSyncEngine(
                client: $c->get(PcClient::class),
                repository: $c->get(PcMemberRepositoryInterface::class),
                mapper: $c->get(PcPersonMapper::class),
                fieldMapRepo: $c->get(PcFieldMapRepositoryInterface::class),
                fieldWriter: $c->get(PcCustomFieldWriterInterface::class),
                photoCache: $c->get(PcPhotoCacheInterface::class),
                pairing: $c->get(MemberPairingInterface::class),
            ),
        );
    }
};
