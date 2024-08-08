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

namespace Tobento\App\Notifier\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\NotificationFactoryInterface;
use Tobento\App\Notifier\Notifications;
use Tobento\App\Notifier\NotificationsInterface;
use Tobento\Service\Container\Container;
use Tobento\Service\Notifier\AbstractNotification;
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\RecipientInterface;

class NotificationsTest extends TestCase
{
    public function testImplementsInterface()
    {
        $notifications = new Notifications(container: new Container(), notifications: []);
        
        $this->assertInstanceof(NotificationsInterface::class, $notifications);
    }
    
    public function testAddMethod()
    {
        $notifications = new Notifications(container: new Container(), notifications: []);
        
        $notifications->add(name: UserRegisterNotification::class, notification: CustomUserRegisterNotification::class);

        $this->assertInstanceof(CustomUserRegisterNotification::class, $notifications->get(new UserRegisterNotification()));
    }
    
    public function testHasMethod()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            'register' => 'class-string',
        ]);
        
        $this->assertTrue($notifications->has('register'));
        $this->assertFalse($notifications->has('foo'));
    }
    
    public function testGetMethodReturnsGivenNotificationIfNoCustomExists()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            'register' => 'class-string',
        ]);
        
        $orgNotification = (new Notification())->name('foo');
        
        $notification = $notifications->get($orgNotification);
        
        $this->assertTrue($orgNotification === $notification);
    }
    
    public function testGetMethodReturnsGivenNotificationIfClosureNotReturnsSupportedType()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            'register' => function (NotificationInterface $notification) {
                return [];
            },
        ]);
        
        $orgNotification = (new Notification())->name('register');
        
        $notification = $notifications->get($orgNotification);
        
        $this->assertTrue($orgNotification === $notification);
    }
    
    public function testGetMethodReturnsGivenNotificationIfClosureReturnsGiven()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            'register' => function (NotificationInterface $notification) {
                return $notification;
            },
        ]);
        
        $orgNotification = (new Notification())->name('register');
        
        $notification = $notifications->get($orgNotification);
        
        $this->assertTrue($orgNotification === $notification);
    }
    
    public function testGetMethodUsingCustomNotification()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            UserRegisterNotification::class => CustomUserRegisterNotification::class,
        ]);
        
        $orgNotification = new UserRegisterNotification();
        
        $notification = $notifications->get($orgNotification);
        
        $this->assertInstanceof(CustomUserRegisterNotification::class, $notification);
    }
    
    public function testGetMethodUsingNotificationFactory()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            UserRegisterNotification::class => UserRegisterNotificationFactory::class,
        ]);
        
        $orgNotification = new UserRegisterNotification();
        
        $notification = $notifications->get($orgNotification);
        
        $this->assertInstanceof(CustomUserRegisterNotification::class, $notification);
    }
    
    public function testAllMethod()
    {
        $data = [
            'foo' => 'class-string',
            'bar' => function () {},
        ];
        
        $notifications = new Notifications(container: new Container(), notifications: $data);
        
        $this->assertSame($data, $notifications->all());
    }
    
    public function testNamesMethod()
    {
        $notifications = new Notifications(container: new Container(), notifications: [
            'foo' => 'class-string',
            'bar' => function () {},
        ]);
        
        $this->assertSame(
            ['foo', 'bar'],
            $notifications->names()
        );
    }
}

class UserRegisterNotification extends AbstractNotification implements Message\ToSms
{
    public function toSms(RecipientInterface $recipient, string $channel): Message\SmsInterface
    {
        return new Message\Sms(
            subject: 'Thanks for your registration',
        );
    }
}

class CustomUserRegisterNotification extends AbstractNotification implements Message\ToSms
{
    public function __construct(
        protected UserRegisterNotification $notification,
    ) {}
    
    public function toSms(RecipientInterface $recipient, string $channel): Message\SmsInterface
    {
        return new Message\Sms(
            subject: 'Custom registration',
        );
    }
}

class UserRegisterNotificationFactory implements NotificationFactoryInterface
{
    public function createNotification(NotificationInterface $notification): NotificationInterface
    {
        return new CustomUserRegisterNotification($notification);
    }
}