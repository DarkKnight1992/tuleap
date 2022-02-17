<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Psr\Log\LoggerInterface;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\OAuth2Server\Administration\OAuth2AppProjectVerifier;
use Tuleap\OAuth2Server\Administration\ProjectAdmin\ListAppsController;
use Tuleap\OAuth2Server\Administration\SiteAdmin\SiteAdminListAppsController;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecretStore;
use Tuleap\OAuth2Server\App\OAuth2AppRemover;
use Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2Server\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2Server\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantController;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2ServerCore\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2ServerCore\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeVerifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\Grant\OAuth2ClientAuthenticationMiddleware;
use Tuleap\OAuth2Server\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\JWTBuilderFactory;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\OpenIDConnectIDTokenCreator;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2ServerCore\OAuth2ServerRoutes;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\OpenIDConnectSigningKeyDAO;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\OpenIDConnectSigningKeyFactory;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectProfileScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenCreator;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenDAO;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2Server\REST\Specification\Swagger\SwaggerJsonOAuth2SecurityDefinition;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;
use Tuleap\OAuth2Server\User\Account\AccountAppsController;
use Tuleap\OAuth2Server\User\AuthorizationDao;
use Tuleap\OAuth2ServerCore\RefreshToken\PrefixOAuth2RefreshToken;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinitionsCollection;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\PasswordUserPostUpdateEvent;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\AccessToken\VerifyOAuth2AccessTokenEvent;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class oauth2_serverPlugin extends Plugin
{
    public const SERVICE_NAME_INSTRUMENTATION = 'oauth2_server';
    public const CSRF_TOKEN_APP_EDITION       = 'oauth2_server_app_edition';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-oauth2_server', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(NavigationPresenter::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
        $this->addHook(VerifyOAuth2AccessTokenEvent::NAME);
        $this->addHook(OAuth2ScopeBuilderCollector::NAME);
        $this->addHook(SwaggerJsonSecurityDefinitionsCollection::NAME);
        $this->addHook(PasswordUserPostUpdateEvent::NAME);
        $this->addHook('codendi_daily_start', 'dailyCleanup');
        $this->addHook('project_is_deleted', 'projectIsDeleted');
        $this->addHook(SiteAdministrationAddOption::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
                    '',
                    dgettext('tuleap-oauth2_server', 'Delegate access to Tuleap resources')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter): void
    {
        $project_id = urlencode((string) $presenter->getProjectId());
        $html_url   = $this->getPluginPath() . "/project/$project_id/admin";
        $presenter->addDropdownItem(
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-oauth2_server', 'OAuth2 Apps'),
                $html_url
            )
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $route_collector = $routes->getRouteCollector();
        $route_collector->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r): void {
                $r->get(
                    '/project/{project_id:\d+}/admin',
                    $this->getRouteHandler('routeGetProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/add-app',
                    $this->getRouteHandler('routePostProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/delete-app',
                    $this->getRouteHandler('routeDeleteProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/new-client-secret',
                    $this->getRouteHandler('routeNewClientSecretProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/edit-app',
                    $this->getRouteHandler('routeEditProjectApp')
                );
                $r->get('/account/apps', $this->getRouteHandler('routeGetAccountApps'));
                $r->post('/account/apps/revoke', $this->getRouteHandler('routePostAccountAppRevoke'));
                $r->get(
                    '/admin',
                    $this->getRouteHandler('routeGetSiteAdmin')
                );
                $r->post(
                    '/admin/add-app',
                    $this->getRouteHandler('routePostSiteAdmin')
                );
                $r->post(
                    '/admin/delete-app',
                    $this->getRouteHandler('routeDeleteSiteAdmin')
                );
                $r->post(
                    '/admin/edit-app',
                    $this->getRouteHandler('routeEditSiteApp')
                );
                $r->post(
                    '/admin/new-client-secret',
                    $this->getRouteHandler('routeNewClientSecretSiteAdmin')
                );
            }
        );
        $route_collector->addGroup(
            '/oauth2',
            function (FastRoute\RouteCollector $r): void {
                $r->addRoute(
                    ['GET', 'POST'],
                    '/authorize',
                    $this->getRouteHandler('routeAuthorizationEndpoint')
                );
                $r->post(
                    '/authorize-process-consent',
                    $this->getRouteHandler('routeAuthorizationProcessConsentEndpoint')
                );
                $r->post('/token', $this->getRouteHandler('routeAccessTokenCreation'));
            }
        );
        $route_collector->addRoute('GET', '/.well-known/openid-configuration', $this->getRouteHandler('routeDiscovery'));
    }

    public function routeGetProjectAdmin(): DispatchableWithRequest
    {
        return ListAppsController::buildSelf();
    }

    public function routeGetSiteAdmin(): DispatchableWithRequest
    {
        return new SiteAdminListAppsController(
            new AdminPageRenderer(),
            UserManager::instance(),
            \Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder::buildSelf(),
            new IncludeAssets(__DIR__ . '/../../../src/www/assets/oauth2_server', '/assets/oauth2_server'),
            new CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION)
        );
    }

    public function routePostProjectAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\Administration\AddAppController(
            $response_factory,
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastGeneratedClientSecretStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routePostSiteAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\Administration\AddAppController(
            $response_factory,
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastGeneratedClientSecretStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeDeleteProjectAdmin(): DispatchableWithRequest
    {
        $app_dao = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\DeleteAppController(
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new OAuth2AppRemover(
                $app_dao,
                new OAuth2AuthorizationCodeDAO(),
                new AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeDeleteSiteAdmin(): DispatchableWithRequest
    {
        $app_dao = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\DeleteAppController(
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new OAuth2AppRemover(
                $app_dao,
                new OAuth2AuthorizationCodeDAO(),
                new AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeNewClientSecretProjectAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\NewClientSecretController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new \Tuleap\OAuth2Server\App\ClientSecretUpdater(
                new SplitTokenVerificationStringHasher(),
                $app_dao,
                new LastGeneratedClientSecretStore(
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                    (new KeyFactory())->getEncryptionKey(),
                    $storage
                )
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeNewClientSecretSiteAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\NewClientSecretController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new \Tuleap\OAuth2Server\App\ClientSecretUpdater(
                new SplitTokenVerificationStringHasher(),
                $app_dao,
                new LastGeneratedClientSecretStore(
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                    (new KeyFactory())->getEncryptionKey(),
                    $storage
                )
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeEditProjectApp(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\EditAppController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            $app_dao,
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeEditSiteApp(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\EditAppController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            $app_dao,
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeAuthorizationEndpoint(): DispatchableWithRequest
    {
        $response_factory     = HTTPFactoryBuilder::responseFactory();
        $stream_factory       = HTTPFactoryBuilder::streamFactory();
        $redirect_uri_builder = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $scope_builder        = $this->buildScopeBuilder();
        return new AuthorizationEndpointController(
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormRenderer(
                $response_factory,
                $stream_factory,
                TemplateRendererFactory::build(),
                new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormPresenterBuilder($redirect_uri_builder)
            ),
            \UserManager::instance(),
            new \Tuleap\OAuth2ServerCore\App\AppFactory(new AppDao(), \ProjectManager::instance()),
            new ScopeExtractor($scope_builder),
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                $redirect_uri_builder,
                new \URLRedirect(\EventManager::instance()),
                HTTPFactoryBuilder::URIFactory()
            ),
            new \Tuleap\OAuth2Server\User\AuthorizationComparator(
                new \Tuleap\OAuth2Server\User\AuthorizedScopeFactory(
                    new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                    new \Tuleap\OAuth2Server\User\AuthorizationScopeDao(),
                    $scope_builder
                )
            ),
            new PKCEInformationExtractor(),
            new PromptParameterValuesExtractor(),
            OAuth2OfflineAccessScope::fromItself(),
            $this->getLogger(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    public function routeAuthorizationProcessConsentEndpoint(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointProcessConsentController(
            \UserManager::instance(),
            new \Tuleap\OAuth2ServerCore\App\AppFactory(new AppDao(), ProjectManager::instance()),
            $this->buildScopeBuilder(),
            new \Tuleap\OAuth2Server\User\AuthorizationCreator(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                new \Tuleap\OAuth2Server\User\AuthorizationScopeDao()
            ),
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
                new \URLRedirect(\EventManager::instance()),
                HTTPFactoryBuilder::URIFactory()
            ),
            new \CSRFSynchronizerToken(AuthorizationEndpointController::CSRF_TOKEN),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, HTTPFactoryBuilder::streamFactory()),
            new DisableCacheMiddleware()
        );
    }

    public function routeAccessTokenCreation(): AccessTokenGrantController
    {
        $response_factory                          = HTTPFactoryBuilder::responseFactory();
        $stream_factory                            = HTTPFactoryBuilder::streamFactory();
        $app_dao                                   = new AppDao();
        $access_token_grant_error_response_builder = new AccessTokenGrantErrorResponseBuilder(
            $response_factory,
            $stream_factory
        );
        $logger                                    = $this->getLogger();
        $access_token_grant_representation_builder = new AccessTokenGrantRepresentationBuilder(
            new OAuth2AccessTokenCreator(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2AccessTokenDAO(),
                new OAuth2ScopeSaver(new Tuleap\OAuth2ServerCore\AccessToken\Scope\OAuth2AccessTokenScopeDAO()),
                new DateInterval('PT1H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new OAuth2RefreshTokenCreator(
                OAuth2OfflineAccessScope::fromItself(),
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2RefreshTokenDAO(),
                new OAuth2ScopeSaver(new Tuleap\OAuth2ServerCore\RefreshToken\Scope\OAuth2RefreshTokenScopeDAO()),
                new DateInterval('PT6H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new OpenIDConnectIDTokenCreator(
                OAuth2SignInScope::fromItself(),
                new JWTBuilderFactory(),
                new DateInterval(OAuth2ServerRoutes::ID_TOKEN_EXPIRATION_DELAY),
                new OpenIDConnectSigningKeyFactory(
                    new KeyFactory(),
                    new OpenIDConnectSigningKeyDAO(),
                    new DateInterval(OAuth2ServerRoutes::SIGNING_KEY_EXPIRATION_DELAY),
                    new DateInterval(OAuth2ServerRoutes::ID_TOKEN_EXPIRATION_DELAY),
                ),
                new Sha256(),
                UserManager::instance()
            )
        );
        return new AccessTokenGrantController(
            $access_token_grant_error_response_builder,
            new OAuth2GrantAccessTokenFromAuthorizationCode(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                $access_token_grant_representation_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
                new OAuth2AuthorizationCodeVerifier(
                    new SplitTokenVerificationStringHasher(),
                    UserManager::instance(),
                    new OAuth2AuthorizationCodeDAO(),
                    new OAuth2ScopeRetriever(new OAuth2AuthorizationCodeScopeDAO(), $this->buildScopeBuilder()),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                new PKCECodeVerifier(),
                $logger
            ),
            new OAuth2GrantAccessTokenFromRefreshToken(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new OAuth2RefreshTokenVerifier(
                    new SplitTokenVerificationStringHasher(),
                    new OAuth2RefreshTokenDAO(),
                    new OAuth2ScopeRetriever(new Tuleap\OAuth2ServerCore\RefreshToken\Scope\OAuth2RefreshTokenScopeDAO(), $this->buildScopeBuilder()),
                    new OAuth2AuthorizationCodeRevoker(new OAuth2AuthorizationCodeDAO()),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                $access_token_grant_representation_builder,
                new ScopeExtractor($this->buildScopeBuilder()),
                $logger
            ),
            $logger,
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware(),
            new OAuth2ClientAuthenticationMiddleware(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                new OAuth2AppCredentialVerifier(
                    new \Tuleap\OAuth2ServerCore\App\AppFactory($app_dao, ProjectManager::instance()),
                    $app_dao,
                    new SplitTokenVerificationStringHasher()
                ),
                new BasicAuthLoginExtractor(),
                $logger
            )
        );
    }

    public function routeGetAccountApps(): DispatchableWithRequest
    {
        return new AccountAppsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new \Tuleap\OAuth2Server\User\Account\AppsPresenterBuilder(
                EventManager::instance(),
                new AppFactory(new AppDao(), \ProjectManager::instance()),
                new \Tuleap\OAuth2Server\User\AuthorizedScopeFactory(
                    new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                    new \Tuleap\OAuth2Server\User\AuthorizationScopeDao(),
                    $this->buildScopeBuilder()
                )
            ),
            TemplateRendererFactory::build(),
            UserManager::instance(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION)
        );
    }

    public function routePostAccountAppRevoke(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new Tuleap\OAuth2Server\User\Account\AppRevocationController(
            $response_factory,
            AccountAppsController::getCSRFToken(),
            UserManager::instance(),
            new \Tuleap\OAuth2Server\User\AuthorizationRevoker(
                new OAuth2AuthorizationCodeDAO(),
                new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION)
        );
    }

    public function routeDiscovery(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        return new \Tuleap\OAuth2Server\OpenIDConnect\Discovery\DiscoveryController(
            new \Tuleap\OAuth2Server\OpenIDConnect\Discovery\ConfigurationResponseRepresentationBuilder(
                new \BaseLanguageFactory(),
                $this->buildScopeBuilder()
            ),
            new JSONResponseBuilder($response_factory, $stream_factory),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory)
        );
    }

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new \Tuleap\OAuth2Server\User\Account\AppsTabAdder())->addTabs($collection);
    }

    public function verifyAccessToken(VerifyOAuth2AccessTokenEvent $event): void
    {
        $verifier = new OAuth2AccessTokenVerifier(
            new OAuth2AccessTokenDAO(),
            new OAuth2ScopeRetriever(
                new Tuleap\OAuth2ServerCore\AccessToken\Scope\OAuth2AccessTokenScopeDAO(),
                $this->buildScopeBuilder()
            ),
            UserManager::instance(),
            new SplitTokenVerificationStringHasher()
        );

        $granted_authorization = $verifier->getGrantedAuthorization(
            $event->getAccessToken(),
            $event->getRequiredScope()
        );
        $event->setGrantedAuthorization($granted_authorization);
    }

    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2OfflineAccessScope::class,
                OAuth2SignInScope::class,
                OpenIDConnectEmailScope::class,
                OpenIDConnectProfileScope::class
            )
        );
    }

    private function buildScopeBuilder(): AuthenticationScopeBuilder
    {
        return AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
        );
    }

    private function buildOAuth2AuthorizationCodeCreator(): OAuth2AuthorizationCodeCreator
    {
        return new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2ScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }

    public function dailyCleanup(): void
    {
        $current_time = (new DateTimeImmutable())->getTimestamp();
        (new OAuth2AccessTokenDAO())->deleteByExpirationDate($current_time);
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeByExpirationDate($current_time);
    }

    public function projectIsDeleted(): void
    {
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeInNonExistingOrDeletedProject();
        (new AuthorizationDao())->deleteAuthorizationsInNonExistingOrDeletedProject();
        (new AppDao())->deleteAppsInNonExistingOrDeletedProject();
    }

    public function retrieveRESTSwaggerJsonSecurityDefinitions(SwaggerJsonSecurityDefinitionsCollection $collection): void
    {
        $collection->addSecurityDefinition(
            SwaggerJsonSecurityDefinitionsCollection::TYPE_NAME_OAUTH2,
            new SwaggerJsonOAuth2SecurityDefinition(
                $this->buildScopeBuilder(),
                new LocaleSwitcher()
            )
        );
    }

    public function passwordUserPostUpdateEvent(PasswordUserPostUpdateEvent $password_user_post_update_event): void
    {
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeByUser($password_user_post_update_event->getUser());
    }

    private function getLogger(): LoggerInterface
    {
        return \BackendLogger::getDefaultLogger('oauth2_server.log');
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
                $this->getPluginPath() . '/admin'
            )
        );
    }
}
