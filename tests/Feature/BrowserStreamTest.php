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

use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Notifier\Feature\BrowserStream;
use Tobento\App\Seeding\User\UserFactory;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Routing\RouterInterface;

class BrowserStreamTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Addon\LanguagesAddon;
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        return $app;
    }
    
    protected function extractSseJson(ResponseInterface $response): array
    {
        $raw = (string)$response->getBody();

        // SSE format: "data: {...}\n\n"
        if (!str_starts_with($raw, 'data: ')) {
            throw new \RuntimeException('Invalid SSE response format.');
        }

        $json = trim(substr($raw, strlen('data: ')));

        return json_decode($json, true);
    }
    
    public function testDeliveryFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'notifications/browser-stream',
        );
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "notifications.browser" permission.');
    }
    
    public function testDeliveryFailsIfNotAuthenticated()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'notifications/browser-stream',
        );
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('Unauthorized.');
    }
    
    public function testDeliverySuccessReturnsEmpty()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);
        
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'notifications/browser-stream',
        );
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertHasHeader(name: 'Content-type', value: 'text/event-stream')
            ->assertHasHeader(name: 'Cache-Control', value: 'no-cache')
            ->assertHasHeader(name: 'Connection', value: 'keep-alive');
        
        $data = $this->extractSseJson($response->response());

        $this->assertSame([
            'notifications' => [],
        ], $data);
    }
    
    public function testDeliverySuccessReturnsSpecificUserNotifcations()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);
        
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'notifications/browser-stream',
        );
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        
        $app->get(NotifierInterface::class)->send(
            notification: new Notification(subject: 'Lorem', channels: ['browser'])->content('Lorem ipsum'),
            recipient: new Recipient(id: $user->id()),
        );
        
        $response = $http->response()->assertStatus(200);
        
        $data = $this->extractSseJson($response->response());
        
        $this->assertSame([
            'notifications' => [
                [
                    'status' => 'success',
                    'text' => 'Lorem',
                    'autotimeout' => 5000,
                    'showCloseButton' => true,                        
                ],
            ],
        ], $data);
    }
    
    public function testNotificationsAreMarkedAsReadAfterDelivery()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);

        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'notifications/browser-stream');

        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);

        // Send a browser notification
        $app->get(NotifierInterface::class)->send(
            notification: new Notification(subject: 'Lorem', channels: ['browser'])->content('Lorem ipsum'),
            recipient: new Recipient(id: $user->id()),
        );

        // Ensure unread before request
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();
        $this->assertSame(1, $repo->count(where: ['read_at' => ['null']]));

        // Call browser endpoint
        $http->response()
            ->assertStatus(200);

        // Now notifications must be marked as read
        $this->assertSame(0, $repo->count(where: ['read_at' => ['null']]));
        $this->assertSame(1, $repo->count(where: ['read_at' => ['not null']]));
    }
    
    public function testOnlyUnreadNotificationsAreReturned()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);

        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'notifications/browser-stream');

        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);

        $notifier = $app->get(NotifierInterface::class);

        // Send unread notification
        $notifier->send(
            notification: new Notification(subject: 'Unread', channels: ['browser'])->content('Unread content'),
            recipient: new Recipient(id: $user->id()),
        );

        // Send another notification and manually mark it as read
        $notifier->send(
            notification: new Notification(subject: 'Read', channels: ['browser'])->content('Read content'),
            recipient: new Recipient(id: $user->id()),
        );

        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Mark second notification as read
        $all = $repo->findAll();
        $readId = $all[2]->get('id');

        $repo->updateById(
            id: $readId,
            attributes: ['read_at' => $app->get(ClockInterface::class)->now()],
        );

        // Call browser endpoint
        $response = $http->response()->assertStatus(200);
        
        $data = $this->extractSseJson($response->response());
        
        $this->assertSame([
            'notifications' => [
                [
                    'status' => 'success',
                    'text' => 'Unread',
                    'autotimeout' => 5000,
                    'showCloseButton' => true,
                ],
            ],
        ], $data);
    }
    
    public function testReturnsMaxFiveNotifications()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);

        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'notifications/browser-stream');

        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);

        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();
        
        // Create 10 notifications with guaranteed increasing timestamps
        for ($i = 1; $i <= 10; $i++) {
            $repo->create([
                'recipient_id' => $user->id(),
                'recipient_type' => 'user',
                'data' => [
                    'subject' => 'N'.$i,
                    'content' => 'C'.$i,
                ],
                'created_at' => $clock->now()->modify('+'.$i.' seconds'),
                'read_at' => null,
            ]);
        }

        // Call browser endpoint
        $response = $http->response()->assertStatus(200);
        
        $data = $this->extractSseJson($response->response());
        
        $notifications = $data['notifications'];

        // Assert only 5 returned
        $this->assertCount(5, $notifications);

        // Assert they are sorted newest → oldest
        // Newest notification is N10
        $this->assertSame('N10', $notifications[0]['text']);
        $this->assertSame('N9',  $notifications[1]['text']);
        $this->assertSame('N8',  $notifications[2]['text']);
        $this->assertSame('N7',  $notifications[3]['text']);
        $this->assertSame('N6',  $notifications[4]['text']);
    }
    
    public function testReturnsOnlyNotificationsForAuthenticatedUser(): void
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);

        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'notifications/browser-stream');

        $app = $this->bootingApp();

        // Two users
        $userA = UserFactory::new()->createOne();
        $userB = UserFactory::new()->createOne();

        $auth->authenticatedAs($userA);

        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Notifications for user A (should be returned)
        $repo->create([
            'recipient_id' => $userA->id(),
            'recipient_type' => 'user',
            'data' => ['subject' => 'A1', 'content' => 'CA1'],
            'created_at' => $clock->now()->modify('+1 second'),
            'read_at' => null,
        ]);

        // Notifications for user B (should NOT be returned)
        $repo->create([
            'recipient_id' => $userB->id(),
            'recipient_type' => 'user',
            'data' => ['subject' => 'B1', 'content' => 'CB1'],
            'created_at' => $clock->now()->modify('+2 seconds'),
            'read_at' => null,
        ]);

        // Call browser endpoint
        $response = $http->response()->assertStatus(200);
        
        $data = $this->extractSseJson($response->response());
        $notifications = $data['notifications'];

        // Only user A's notification should appear
        $this->assertCount(1, $notifications);
        $this->assertSame('A1', $notifications[0]['text']);
    }
    
    public function testNotificationIsMappedCorrectly(): void
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(RouterInterface::class, function($router) {
                $router->get('orders/{id}', fn() => 'ok')->name('orders.view');
            });
        });
        
        $this->fakeConfig()->with('notifier.features', [
            new BrowserStream(withAcl: false),
        ]);

        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'notifications/browser-stream');

        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);

        // Send a browser notification
        $app->get(NotifierInterface::class)->send(
            notification: new Notification(subject: 'Lorem', channels: ['browser'])
                ->addMessage('browser', new Message\Browser([
                    'title' => 'Lorem',
                    'message' => 'You received a new order.',
                    'status' => 'warning',
                    'autotimeout' => 1000,
                    'showCloseButton' => false,
                    'action_text' => 'View Order',
                    'action_route' => 'orders.view',
                    'action_route_parameters' => ['id' => 55],
                    'action_attributes' => ['class' => 'button'],
                ])),
            recipient: new Recipient(id: $user->id()),
        );
        
        // Call browser endpoint
        $response = $http->response()->assertStatus(200);
        
        $data = $this->extractSseJson($response->response());
        $notifications = $data['notifications'];

        $this->assertCount(1, $notifications);

        $mapped = $notifications[0];
        
        $this->assertSame([
            'status' => 'warning',
            'text' => 'You received a new order.',
            'autotimeout' => 1000,
            'showCloseButton' => false,
            'title' => 'Lorem',
            'action' => [
                'title' => 'View Order',
                'url' => 'http://localhost/orders/55',
                'classes' => ['button'],
            ],
        ], $mapped);
    }
}