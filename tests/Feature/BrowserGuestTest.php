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
use Tobento\App\AppInterface;
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\App\Notifier\Feature\BrowserGuest;
use Tobento\App\Seeding\User\UserFactory;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\GuestRecipient;
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\View\ViewInterface;

class BrowserGuestTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Addon\LanguagesAddon;
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
    
    protected function sendGuestNotification(array $subjects = ['Lorem']): void
    {
        $this->onCreateApp(function(AppInterface $app) use ($subjects) {
            $app->on(RouterInterface::class, function($router) use ($subjects) {
                $router->get('send', function (NotifierInterface $notifier, GuestResolverInterface $guestResolver) use ($subjects) {
                    foreach($subjects as $subject) {
                        $notifier->send(
                            notification: new Notification(subject: $subject, channels: ['browser'])->content('Lorem'),
                            recipient: new GuestRecipient(id: $guestResolver->resolveId()),
                        );                        
                    }
                    return 'ok';
                });
            });
        });

        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'send');
        $http->response()->assertStatus(200);
    }
    
    public function testDeliveryFailsIfAuthenticated()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);
        
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'notifications/browser-guest',
        );
        
        $app = $this->bootingApp();
        $user = UserFactory::new()->createOne();
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('Unauthorized.');
    }
    
    public function testDeliveryFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'notifications/browser-guest',
        );
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "notifications.browser" permission.');
    }
    
    public function testDeliverySuccessReturnsEmpty()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'notifications/browser-guest',
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertJson([
                'notifications' => [],
            ]);
    }
    
    public function testDeliverySuccessReturnsSpecificUserNotifcations()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $this->sendGuestNotification();
        
        $http->request(
            method: 'POST',
            uri: 'notifications/browser-guest',
        );
        
        $http->response()
            ->assertStatus(200)
            ->assertJson([
                'notifications' => [
                    [
                        'status' => 'success',
                        'text' => 'Lorem',
                        'autotimeout' => 5000,
                        'showCloseButton' => true,                        
                    ],
                ],
            ]);
    }
    
    public function testNotificationsAreMarkedAsReadAfterDelivery()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $this->sendGuestNotification();
        
        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $app = $this->bootingApp();

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
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $this->sendGuestNotification(subjects: ['Unread', 'Read']);
        
        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $app = $this->bootingApp();
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Mark second notification as read
        $all = $repo->findAll();
        $readId = $all[2]->get('id');

        $repo->updateById(
            id: $readId,
            attributes: ['read_at' => $app->get(ClockInterface::class)->now()],
        );

        // Call browser endpoint
        $http->response()
            ->assertStatus(200)
            ->assertJson([
                'notifications' => [
                    [
                        'status' => 'success',
                        'text' => 'Unread',
                        'autotimeout' => 5000,
                        'showCloseButton' => true,
                    ],
                ],
            ]);
    }
    
    public function testReturnsMaxFiveNotifications()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $this->sendGuestNotification();
        
        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $app = $this->bootingApp();
        $guestResolver = $app->get(GuestResolverInterface::class);
        $guestId = $guestResolver->resolveId(); // SAME ID for all notifications
        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();
        
        // Create 10 notifications with guaranteed increasing timestamps
        for ($i = 1; $i <= 10; $i++) {
            $repo->create([
                'recipient_id' => $guestId,
                'recipient_type' => GuestRecipient::class,
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

        $data = json_decode((string)$response->response()->getBody(), true);
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
    
    public function testReturnsOnlyNotificationsForCurrentGuest(): void
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $this->sendGuestNotification();

        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $app = $this->bootingApp();
        $guestResolver = $app->get(GuestResolverInterface::class);
        $guestA = $guestResolver->resolveId(); // current guest
        
        // Simulate a different guest (guest B)
        $guestB = 'guest:' . bin2hex(random_bytes(16));

        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Notification for guest A (should be returned)
        $repo->create([
            'recipient_id' => $guestA,
            'recipient_type' => GuestRecipient::class,
            'data' => ['subject' => 'A1', 'content' => 'CA1'],
            'created_at' => $clock->now()->modify('+1 second'),
            'read_at' => null,
        ]);

        // Notification for guest B (should NOT be returned)
        $repo->create([
            'recipient_id' => $guestB,
            'recipient_type' => GuestRecipient::class,
            'data' => ['subject' => 'B1', 'content' => 'CB1'],
            'created_at' => $clock->now()->modify('+2 seconds'),
            'read_at' => null,
        ]);

        // Call browser endpoint
        $response = $http->response()->assertStatus(200);

        $data = json_decode((string)$response->response()->getBody(), true);
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
                $router->get('send', function (NotifierInterface $notifier, GuestResolverInterface $guestResolver) {
                    $notifier->send(
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
                        recipient: new GuestRecipient(id: $guestResolver->resolveId()),
                    );
                    return 'ok';
                });
            });
        });
        
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'send');
        $http->response()->assertStatus(200);
        
        $http->request(method: 'POST', uri: 'notifications/browser-guest');
        
        // Call browser endpoint
        $response = $http->response()->assertStatus(200);

        $data = json_decode((string)$response->response()->getBody(), true);
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
    
    public function testBroadcastNotificationsAreReturned(): void
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'notifications/browser-guest');
        
        $app = $this->bootingApp();
        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Create a broadcast notification (recipient_id = null)
        $repo->create([
            'recipient_id' => null,
            'recipient_type' => GuestRecipient::class,
            'data' => [
                'subject' => 'Broadcast',
                'content' => 'Hello guests',
            ],
            'created_at' => $clock->now(),
            'expires_at' => null,
            'read_at' => null,
        ]);

        $response = $http->response()->assertStatus(200);

        $data = json_decode((string)$response->response()->getBody(), true);
        $notifications = $data['notifications'];

        $this->assertCount(1, $notifications);
        $this->assertSame('Broadcast', $notifications[0]['text']);
    }
    
    public function testBroadcastNotificationsAreNotReturnedAfterBeingRead(): void
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();

        // First request: trigger BrowserGuest (empty response)
        $http->request(method: 'POST', uri: 'notifications/browser-guest');
        $http->response()->assertStatus(200);

        $app = $this->bootingApp();
        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // Create a broadcast notification
        $repo->create([
            'recipient_id' => null,
            'recipient_type' => GuestRecipient::class,
            'data' => [
                'subject' => 'Broadcast',
                'content' => 'Hello guests',
            ],
            'created_at' => $clock->now(),
            'expires_at' => null,
            'read_at' => null,
        ]);

        // FIRST DELIVERY — should return the broadcast notification
        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $response = $http->response()->assertStatus(200);
        $data = json_decode((string)$response->response()->getBody(), true);

        $this->assertCount(1, $data['notifications']);
        $this->assertSame('Broadcast', $data['notifications'][0]['text']);

        // SECOND DELIVERY — should return NOTHING because it is now read
        $http->request(method: 'POST', uri: 'notifications/browser-guest');

        $response = $http->response()->assertStatus(200);
        $data = json_decode((string)$response->response()->getBody(), true);

        $this->assertCount(0, $data['notifications']);
    }
    
    public function testExpiredBroadcastNotificationsAreIgnored(): void
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(withAcl: false),
        ]);

        $http = $this->fakeHttp();

        // First request: trigger BrowserGuest (empty response)
        $http->request(method: 'POST', uri: 'notifications/browser-guest');
        $http->response()->assertStatus(200);

        $app = $this->bootingApp();
        $clock = $app->get(ClockInterface::class);
        $repo = $app->get(ChannelsInterface::class)->get('browser')->repository();

        // EXPIRED broadcast
        $repo->create([
            'recipient_id' => null,
            'recipient_type' => GuestRecipient::class,
            'data' => ['subject' => 'Expired', 'content' => 'X'],
            'created_at' => $clock->now()->modify('-10 minutes'),
            'expires_at' => $clock->now()->modify('-1 minute'),
            'read_at' => null,
        ]);

        // VALID broadcast
        $repo->create([
            'recipient_id' => null,
            'recipient_type' => GuestRecipient::class,
            'data' => ['subject' => 'Valid', 'content' => 'Y'],
            'created_at' => $clock->now(),
            'expires_at' => $clock->now()->modify('+10 minutes'),
            'read_at' => null,
        ]);

        // SECOND request: should return only the valid one
        $http->request(method: 'POST', uri: 'notifications/browser-guest');
        $response = $http->response()->assertStatus(200);

        $data = json_decode((string)$response->response()->getBody(), true);
        $this->assertCount(1, $data['notifications']);
        $this->assertSame('Valid', $data['notifications'][0]['text']);
    }
    
    public function testViewIsNotRenderedWhenAuthenticated()
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
    
    public function testViewIsNotRenderedWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
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
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('import initBrowserNotifier');
    }
    
    public function testViewIsNotRenderedIfNotAutoRendered()
    {
        $this->fakeConfig()->with('notifier.features', [
            new BrowserGuest(
                autoRenderOnView: null,
            ),
        ]);
        
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'team');
        
        $app = $this->bootingApp();
        $auth->addPermissions(['notifications.browser']);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('import initBrowserNotifier');
    }
}