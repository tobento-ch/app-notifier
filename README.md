# App Notifier

Notifier support for the app using the [Notifier Service](https://github.com/tobento-ch/service-notifier).

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Notifier Boot](#notifier-boot)
        - [Notifier Config](#notifier-config)
        - [Creating And Sending Notifications](#creating-and-sending-notifications)
        - [Supported Channels](#supported-channels)
        - [Queuing Notifications](#queuing-notifications)
        - [Available Channels](#available-channels)
        - [Storage Notification Formatters](#storage-notification-formatters)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app notifier project running this command.

```
composer require tobento/app-notifier
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Notifier Boot

The notifier boot does the following:

* installs and loads notifier config file
* implements notifier interfaces

```php
use Tobento\App\AppFactory;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\Notifier\Storage\NotificationFormattersInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
$app->booting();

// Implemented interfaces:
$notifier = $app->get(NotifierInterface::class);
$channels = $app->get(ChannelsInterface::class);
$queueHandler = $app->get(QueueHandlerInterface::class);
$availableChannels = $app->get(AvailableChannelsInterface::class);
$notificationFormatters = $app->get(NotificationFormattersInterface::class);

// Run the app
$app->run();
```

### Notifier Config

The configuration for the notifier is located in the ```app/config/notifier.php``` file at the default [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) config location where you can specify the notification channels for your application.

### Creating And Sending Notifications

```php
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;

class SomeService
{
    public function send(NotifierInterface $notifier): void
    {
        // Create a Notification that has to be sent:
        // using the "email" and "sms" channel
        $notification = new Notification(
            subject: 'New Invoice',
            content: 'You got a new invoice for 15 EUR.',
            channels: ['mail', 'sms'],
        );

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
            phone: '15556666666',
        );

        // Send the notification to the recipient:
        $notifier->send($notification, $recipient);
    }
}
```

Check out the [Notifier Service - Creating And Sending Notifications](https://github.com/tobento-ch/service-notifier#creating-and-sending-notifications) section to learn more about it.

### Supported Channels

By default, the following channels are supported:

* [Mail Channel](https://github.com/tobento-ch/service-notifier#mail-channel), you will only need to configure your mailers in the [Mail Config](https://github.com/tobento-ch/app-mail#mail-config) file.

* [Sms Channel - Vonage](https://github.com/tobento-ch/service-notifier#sms-channel), you will only need to configure the dns in the [Notifier Config](#notifier-config) file.

* [Storage Channel](https://github.com/tobento-ch/service-notifier#storage-channel)

### Queuing Notifications

Sending notifications can be a time-consuming task, you may queue notification messages for background sending to mitigate this issue.

To queue notification messages, simply add the [Queue Parameter](https://github.com/tobento-ch/service-notifier#queue) to your message:

**Example**

```php
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Notifier\Parameter\Queue;

class SomeService
{
    public function send(NotifierInterface $notifier): void
    {
        // Create a Notification that has to be sent:
        // using the "email" and "sms" channel
        $notification = (new Notification(
            subject: 'New Invoice',
            content: 'You got a new invoice for 15 EUR.',
            channels: ['mail', 'sms'],
        ))->parameter(new Queue(
            // you may specify the queue to be used:
            name: 'secondary',
            // you may specify a delay in seconds:
            delay: 30,
            // you may specify how many times to retry:
            retry: 3,
            // you may specify a priority:
            priority: 100,
            // you may specify if you want to encrypt the message:
            encrypt: true,
        ));

        // The receiver of the notification:
        $recipient = new Recipient(
            email: 'mail@example.com',
            phone: '15556666666',
        );

        // Send the notification to the recipient:
        $notifier->send($notification, $recipient);
    }
}
```

The [Notifier Boot](#notifier-boot) automatically boots the [App Queue Boot](https://github.com/tobento-ch/app-queue#queue-boot) to support queuing messages out of the box.

You will only need to configure your queues in the [Queue Config](https://github.com/tobento-ch/app-queue#queue-config) file.

### Available Channels

The available channels may be used to restrict channels for certain services or to display its names and/or titles. By default all channels specified in the ```app/config/notifier.php``` file will be available.

```php
use Tobento\App\Notifier\AvailableChannelsInterface;

$channels = $app->get(AvailableChannelsInterface::class);

var_dump($channels->has(channel: 'sms'));
// bool(true)

var_dump($channels->titleFor(channel: 'sms'));
// string(3) "Sms"

var_dump($channels->names());
// array(3) {[0]=> string(4) "mail" [1]=> string(3) "sms" [2]=> string(7) "storage"}

var_dump($channels->titles());
// array(3) {[0]=> string(4) "Mail" [1]=> string(3) "Sms" [2]=> string(7) "Storage"} 

var_dump($channels->titlesToString(separator: ', '));
// string(18) "Mail, Sms, Storage"

// Add a new title for a channel returning a new instance:
$channels = $channels->withTitle(channel: 'sms', title: 'SMS Channel');
var_dump($channels->titleFor(channel: 'sms'));
// string(11) "SMS Channel"

// Returns a new instance with the channels mapped.
$channels = $channels->map(fn($title, $name) => strtoupper($title));
var_dump($channels->titlesToString(separator: ', '));
// string(26) "MAIL, SMS CHANNEL, STORAGE"

// Returns a new instance with the channels sorted by its name:
$channels = $channels->sortByName();

// Returns a new instance with the channels sorted by its title:
$channels = $channels->sortByTitle();

// Count channels:
var_dump($channels->count());
// int(3)

// Returns a new instance with with only the channels specified:
$channels = $channels->only(['sms', 'mail']);

// Returns a new instance with the channels except those specified:
$channels = $channels->except(['sms', 'mail']);

// Iteration:
foreach($channels->all() as $name => $title) {}
// or just:
foreach($channels as $name => $title) {}
```

### Storage Notification Formatters

Storage notification formatters may be used to format notifications stored by the [Storage Channel](https://github.com/tobento-ch/service-notifier#storage-channel).

**General Formatter**

You may use the general formatter which uses the following storage message data:

```php
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Message;

$notification = (new Notification())
    ->addMessage('storage', new Message\Storage([
        'message' => 'You received a new order.',
        'action_text' => 'View Order',
        'action_route' => 'orders.view',
        'action_route_parameters' => ['id' => 555],
    ]));
```

In ```app/config/notifier.php```:

```php
'formatters' => [
    \Tobento\App\Notifier\Storage\GeneralNotificationFormatter::class,
],
```

The ```action_route``` and ```action_route_parameters``` data will used to generate the message url if you have installed the [App Http - Routing Boot](https://github.com/tobento-ch/app-http#routing-boot).

The ```message``` and ```action_text``` data will be translated by the formatter if you have installed the [App Translation Boot](https://github.com/tobento-ch/app-translation#translation-boot).

**General Formatter Example**

You may create your own general formatter for notifications:

```php
use Tobento\App\Notifier\Storage\NotificationFormatterInterface;
use Tobento\App\Notifier\Storage\Notification;

class GeneralNotificationFormatter implements NotificationFormatterInterface
{
    public function format(Notification $notification): Notification
    {
        // General data available:
        $id = $notification->id();
        $name = $notification->name();
        $recipientId = $notification->recipientId();
        $recipientType = $notification->recipientType();
        $readAt = $notification->get('read_at');
        $createdAt = $notification->get('created_at');
        $subject = $notification->get('data.subject', '');
        $content = $notification->get('data.content', '');
        
        // Format:
        return $notification
            ->withMessage($subject.': '.$content);
    }
}
```

In ```app/config/notifier.php```:

```php
'formatters' => [
    GeneralNotificationFormatter::class,
],
```

**Specific Formatter Example**

You may create a specific formatter to format specific notifications only:

```php
use Tobento\App\Notifier\Storage\NotificationFormatterInterface;
use Tobento\App\Notifier\Storage\Notification;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Routing\RouterInterface;

class NewOrderNotificationFormatter implements NotificationFormatterInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RouterInterface $router,
    ) {}
    
    public function format(Notification $notification): Notification
    {
        // You may only format specific notifications
        if (!$notification->name() instanceof NewOrderNotification) {
            return $notification;
        }
        
        // Stop further formatters to format notification:
        $notification->stopPropagation(true);
        
        // Retrieve specific message data:
        $orderId = $notification->get('data.order_id');
        
        // Format:
        return $notification
            ->withMessage($this->translator->trans('New order received'))
            ->withAddedAction(
                text: $this->translator->trans('View Order'),
                url: $this->router->url('orders.view', ['id' => $orderId]),
            );
    }
}
```

In ```app/config/notifier.php```:

```php
'formatters' => [
    NewOrderNotificationFormatter::class,
    GeneralNotificationFormatter::class,
],
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)