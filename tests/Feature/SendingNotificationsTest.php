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
use Tobento\App\Notifier\Formatting\NotificationFormattersInterface;
use Tobento\App\Notifier\Formatting\NotificationFactoryInterface;
use Tobento\App\Notifier\NotificationsInterface;
use Tobento\App\Notifier\ReadNotificationResolverInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Notifier\Parameter\Queue;
use Tobento\Service\Queue\QueuesInterface;

class SendingNotificationsTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        return $app;
    }
    
    public function testSendsNotification()
    {
        $app = $this->bootingApp();
        
        $notification = new Notification(subject: 'Lorem', channels: ['email'])
            ->content('Lorem ipsum');

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
        );

        // Send the notification to the recipient:
        $messages = $app->get(NotifierInterface::class)->send($notification, $recipient);
        
        $this->assertTrue(true);
    }
    
    public function testSendNotificationUsesCustomNotificationIfExist()
    {
        $app = $this->bootingApp();
        
        $app->on(NotificationsInterface::class, function (NotificationsInterface $notifications) {
            $notification = new Notification(subject: 'Custom', channels: ['email'])
                ->content('Custom');
            
            $notifications->add(name: 'register', notification: $notification);
        });
        $app->booting();
        
        $notification = new Notification(subject: 'Lorem', channels: ['email'])
            ->content('Lorem ipsum')
            ->name('register');

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
        );

        // Send the notification to the recipient:
        $messages = $app->get(NotifierInterface::class)->send($notification, $recipient);
        
        $this->assertSame('Custom', $messages[0]?->notification()?->getSubject());
    }
    
    public function testNotificationGetsQueued()
    {
        $fakeQueue = $this->fakeQueue();
        $app = $this->bootingApp();
        $queue = $app->get(QueuesInterface::class)->queue('file');
        
        $this->assertSame(0, $queue->size());
        
        $notification = new Notification(subject: 'Lorem', channels: ['email'])
            ->content('Lorem ipsum')
            ->parameter(new Queue(name: 'file'));

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
        );

        // Send the notification to the recipient:
        $messages = $app->get(NotifierInterface::class)->send($notification, $recipient);
        
        $this->assertSame(1, $queue->size());
    }
}