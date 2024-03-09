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

namespace Tobento\App\Notifier\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\Boot\Notifier;
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\Notifier\Storage\NotificationFormattersInterface;
use Tobento\App\Notifier\Storage\NotificationFactoryInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Notifier\Parameter\Queue;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Boot;
use Tobento\Service\Filesystem\Dir;

class NotifierTest extends TestCase
{    
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../../'), 'root')
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10)
            // for testing only we add public within app dir.
            ->dir($app->dir('app').'public', 'public')
            ->dir($app->dir('root').'vendor', 'vendor');
        
        $app->dirs()->dir(realpath(__DIR__.'/../config/mail/'), 'config-mail', group: 'config', priority: 20);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Notifier::class);
        $app->booting();
        
        $this->assertInstanceof(NotifierInterface::class, $app->get(NotifierInterface::class));
        $this->assertInstanceof(ChannelsInterface::class, $app->get(ChannelsInterface::class));
        $this->assertInstanceof(QueueHandlerInterface::class, $app->get(QueueHandlerInterface::class));
        $this->assertInstanceof(AvailableChannelsInterface::class, $app->get(AvailableChannelsInterface::class));
        $this->assertInstanceof(NotificationFormattersInterface::class, $app->get(NotificationFormattersInterface::class));
        $this->assertInstanceof(NotificationFactoryInterface::class, $app->get(NotificationFactoryInterface::class));
    }

    public function testSendNotification()
    {
        $app = $this->createApp();
        $app->boot(Notifier::class);
        $app->booting();
        
        $notification = (new Notification(subject: 'Lorem', channels: ['email']))
            ->content('Lorem ipsum');

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
        );

        // Send the notification to the recipient:
        $messages = $app->get(NotifierInterface::class)->send($notification, $recipient);
        
        $this->assertTrue(true);
    }
    
    public function testNotificationGetsQueued()
    {
        $app = $this->createApp();
        $app->boot(Notifier::class);
        $app->booting();
        
        $queue = $app->get(QueuesInterface::class)->queue('file');
        
        $this->assertSame(0, $queue->size());
        
        $notification = (new Notification(subject: 'Lorem', channels: ['email']))
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
    
    public function testAllConfigChannelsWillBeAvailable()
    {
        $app = $this->createApp();
        $app->boot(Notifier::class);
        $app->booting();
        
        $this->assertSame(
            ['mail', 'sms', 'storage'],
            $app->get(AvailableChannelsInterface::class)->names()
        );
    }
}