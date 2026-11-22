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
use Tobento\App\Notifier\ReadNotificationResolverInterface;
use Tobento\Service\Cookie\CookiesProcessorInterface;
use Tobento\Service\Routing\RouterInterface;

class CookieReadNotificationResolverTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        $app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        
        $app->on(RouterInterface::class, function($router) {
            $router->get('read-ids', function (ReadNotificationResolverInterface $resolver) {
                return implode(',', $resolver->getReadIds());
            });
            
            $router->get('store-read-ids', function (ReadNotificationResolverInterface $resolver) {
                $resolver->storeReadIds(range(1, 60));
                return 'ok';
            });
        });
        
        return $app;
    }
    
    protected function whitelistReadCookie(): void
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(CookiesProcessorInterface::class, function(CookiesProcessorInterface $processor) {
                $processor->whitelistCookie(name: 'browser_read_notifications');
            });
        });
    }
    
    public function testReturnsEmptyIfNoCookieExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'read-ids');
        
        $http->response()->assertStatus(200)->assertBodySame('');
    }
    
    public function testReturnsIdsFromCookie()
    {
        $this->whitelistReadCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'read-ids', cookies: ['browser_read_notifications' => '1,2,,3']);
        
        $http->response()->assertStatus(200)->assertBodySame('1,2,3');
    }
    
    public function testReturnsEmptyIfCookieIsNotAString()
    {
        $this->whitelistReadCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'read-ids', cookies: ['browser_read_notifications' => ['1', '2']]);
        
        $http->response()->assertStatus(200)->assertBodySame('');
    }
    
    public function testReturnsEmptyIfCookieCannotBeDecrypted()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'read-ids', cookies: ['browser_read_notifications' => '1,2,3']);
        
        $http->response()->assertStatus(200)->assertBodySame('');
    }
    
    public function testStoresOnlyTheNewestIds()
    {
        $this->whitelistReadCookie();
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'store-read-ids');
        
        $response = $http->response()->assertStatus(200)->assertCookieExists('browser_read_notifications');
        
        $ids = explode(',', urldecode($response->cookies()['browser_read_notifications']));
        
        $this->assertSame(array_map('strval', range(11, 60)), $ids);
    }
}