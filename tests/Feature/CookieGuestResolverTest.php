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
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\Service\Cookie\CookiesProcessorInterface;
use Tobento\Service\Routing\RouterInterface;

class CookieGuestResolverTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        $app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        
        $app->on(RouterInterface::class, function($router) {
            $router->get('guest-id', function (GuestResolverInterface $guestResolver) {
                return $guestResolver->resolveId();
            });
        });
        
        return $app;
    }
    
    protected function whitelistGuestCookie(): void
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(CookiesProcessorInterface::class, function(CookiesProcessorInterface $processor) {
                $processor->whitelistCookie(name: 'browser_guest_id');
            });
        });
    }
    
    public function testCreatesIdAndCookieIfNoCookieExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'guest-id');
        
        $response = $http->response()->assertStatus(200)->assertCookieExists('browser_guest_id');
        
        $this->assertMatchesRegularExpression('/^guest:[a-f0-9]{32}$/', $response->response()->getBody()->__toString());
    }
    
    public function testReturnsIdFromCookie()
    {
        $this->whitelistGuestCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'guest-id', cookies: ['browser_guest_id' => 'guest:abc']);
        
        $http->response()->assertStatus(200)->assertBodySame('guest:abc');
    }
    
    public function testCreatesIdIfCookieHasNotThePrefix()
    {
        $this->whitelistGuestCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'guest-id', cookies: ['browser_guest_id' => 'abc']);
        
        $response = $http->response()->assertStatus(200);
        
        $this->assertMatchesRegularExpression('/^guest:[a-f0-9]{32}$/', $response->response()->getBody()->__toString());
    }
    
    public function testCreatesIdIfCookieIsNotAString()
    {
        $this->whitelistGuestCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'guest-id', cookies: ['browser_guest_id' => ['guest:abc']]);
        
        $response = $http->response()->assertStatus(200);
        
        $this->assertMatchesRegularExpression('/^guest:[a-f0-9]{32}$/', $response->response()->getBody()->__toString());
    }
    
    public function testCreatesIdIfCookieCannotBeDecrypted()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'guest-id', cookies: ['browser_guest_id' => 'guest:abc']);
        
        $response = $http->response()->assertStatus(200);
        
        $this->assertMatchesRegularExpression('/^guest:[a-f0-9]{32}$/', $response->response()->getBody()->__toString());
    }
}