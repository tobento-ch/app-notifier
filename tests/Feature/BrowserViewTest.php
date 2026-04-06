<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Notifier\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Notifier\Feature\BrowserView;
use Tobento\App\Seeding\User\UserFactory;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\View\ViewInterface;

class BrowserViewTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);

        $app->dirs()->dir(
            dir: realpath(__DIR__.'/../views/'),
            name: 'custom',
            group: 'views',
            priority: 500,
        );
        
        $app->on(RouterInterface::class, function($router) {
            $router->get('team', function (ViewInterface $view) {
                return $view->render(view: 'team');
            });
        });
        
        return $app;
    }

    public function testViewIsNotRenderedWhenUnauthenticated()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('import initBrowserNotifier');
    }
    
    public function testViewIsNotRenderedWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('import initBrowserNotifier');
    }
    
    public function testViewIsRendered()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('import initBrowserNotifier');
    }
    
    public function testViewIsNotRenderedIfNotAutoRendered()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserView(
                autoRenderOnView: null,
            ),
        ]);
        
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('import initBrowserNotifier');
    }
    
    public function testViewIsNotRenderedIfByUserPreference()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $user = UserFactory::new([
            'settings' => [
                'preferred_notification_channels' => ['mail'],
            ],
        ])->createOne();
        
        $auth->authenticatedAs($user);
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('import initBrowserNotifier');
    }
    
    public function testViewIsRenderedWithEmptyPreferences()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $user = UserFactory::new([
            'settings' => [
                'preferred_notification_channels' => [],
            ],
        ])->createOne();
        
        $auth->authenticatedAs($user);
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('import initBrowserNotifier');
    }
}