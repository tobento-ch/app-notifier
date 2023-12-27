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

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)