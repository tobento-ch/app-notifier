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
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\App\Notifier\Formatting\NotificationFormattersInterface;
use Tobento\App\Notifier\Formatting\NotificationFactoryInterface;
use Tobento\App\Notifier\NotificationsInterface;
use Tobento\App\Notifier\ReadNotificationResolverInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\QueueHandlerInterface;

class NotifierBootTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        return $app;
    }

    public function testInterfacesAreAvailable()
    {
        $app = $this->bootingApp();

        $this->assertInstanceof(NotifierInterface::class, $app->get(NotifierInterface::class));
        $this->assertInstanceof(ChannelsInterface::class, $app->get(ChannelsInterface::class));
        $this->assertInstanceof(QueueHandlerInterface::class, $app->get(QueueHandlerInterface::class));
        $this->assertInstanceof(AvailableChannelsInterface::class, $app->get(AvailableChannelsInterface::class));
        $this->assertInstanceof(NotificationFormattersInterface::class, $app->get(NotificationFormattersInterface::class));
        $this->assertInstanceof(NotificationFactoryInterface::class, $app->get(NotificationFactoryInterface::class));
        $this->assertInstanceof(NotificationsInterface::class, $app->get(NotificationsInterface::class));
        $this->assertInstanceof(GuestResolverInterface::class, $app->get(GuestResolverInterface::class));
        $this->assertInstanceof(ReadNotificationResolverInterface::class, $app->get(ReadNotificationResolverInterface::class));
    }
    
    public function testConsoleCommandsAreAvailable()
    {
        $app = $this->bootingApp();
        
        $console = $app->get(ConsoleInterface::class);
        $this->assertTrue($console->hasCommand('user:notifications:clear'));
    }
    
    public function testAllConfigChannelsWillBeAvailable()
    {
        $app = $this->bootingApp();
        
        $this->assertSame(
            ['mail', 'sms', 'storage', 'browser'],
            $app->get(AvailableChannelsInterface::class)->names()
        );
    }
}