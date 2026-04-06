# App Notifier

App Notifier provides powerful and flexible notification support for Tobento applications.  
It builds on the underlying [Notifier Service](https://github.com/tobento-ch/service-notifier) and adds:

- application-level integration  
- browser notifications (pull, SSE, guest)  
- storage notifications  
- unified formatting  
- feature-based configuration  
- view integration and assets  
- channel discovery and availability helpers  

Whether you need real-time browser updates, guest notifications, or persistent storage-based messages, App Notifier gives you a consistent API and a clean integration layer for your Tobento App.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Notifier Boot](#notifier-boot)
        - [Notifier Config](#notifier-config)
    - [Channels](#channels)
        - [Supported Channels](#supported-channels)
        - [Available Channels](#available-channels)
    - [Features](#features)
        - [Browser Feature](#browser-feature)
        - [Browser Stream Feature](#browser-stream-feature)
        - [Browser Guest Feature](#browser-guest-feature)
        - [Browser View Feature](#browser-view-feature)
    - [Creating And Sending Notifications](#creating-and-sending-notifications)
        - [Using General Recipients](#using-general-recipients)
        - [Using Guest Recipient](#using-guest-recipient)
        - [Browser Message](#browser-message)
    - [Queuing Notifications](#queuing-notifications)
    - [Notification Formatters](#notification-formatters)
        - [General Notification Formatter](#general-notification-formatter)
        - [Creating Notification Formatters](#creating-notification-formatters)
    - [Custom Notifications](#custom-notifications)
    - [Console](#console)
        - [Clear Notifications Command](#clear-notifications-command)
    - [App Notification Integration](#app-notification-integration)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app notifier project running this command.

```
composer require tobento/app-notifier
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Notifier Boot

The Notifier boot integrates the notifier system into your application.  
It performs the following tasks:

* loads and applies the [notifier configuration](#notifier-config)
* registers and implements all notifier-related interfaces
* boots the configured [features](#features)

Below is an example of how to initialize the app with the Notifier boot:

```php
use Tobento\App\AppFactory;
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\Notifier\Formatting\NotificationFormattersInterface;
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\App\Notifier\NotificationsInterface;
use Tobento\App\Notifier\ReadNotificationResolverInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\QueueHandlerInterface;

// Create the app
$app = new AppFactory()->createApp();

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

// Service-related interfaces:
$notifier = $app->get(NotifierInterface::class);
$channels = $app->get(ChannelsInterface::class);
$queueHandler = $app->get(QueueHandlerInterface::class);

// App-related interfaces:
$availableChannels = $app->get(AvailableChannelsInterface::class);
$notificationFormatters = $app->get(NotificationFormattersInterface::class);
$notifications = $app->get(NotificationsInterface::class);
$guestResolver = $app->get(GuestResolverInterface::class);
$readNotificationResolver = $app->get(ReadNotificationResolverInterface::class);

// Run the app
$app->run();
```

### Notifier Config

The configuration for the notifier is located in the  `app/config/notifier.php` file of the default [**App Skeleton**](https://github.com/tobento-ch/app-skeleton).

This file allows you to configure all notifier-related settings, including:

- which notification channels your application should use  
- which features should be enabled (Browser, BrowserStream, BrowserGuest, etc.)  
- which notification formatters should be applied  
- channel-specific options such as SMS DNS or browser channel names  

You can adjust this configuration to match the needs of your application.

## Channels

### Supported Channels

By default, the following channels are supported:

* **[Mail Channel](https://github.com/tobento-ch/service-notifier#mail-channel)**  
  You only need to configure your mailers in the [Mail Config](https://github.com/tobento-ch/app-mail#mail-config) file.

* **[Sms Channel - Vonage](https://github.com/tobento-ch/service-notifier#sms-channel)**  
  You only need to configure the DNS settings in the [Notifier Config](#notifier-config) file.

* **[Storage Channel](https://github.com/tobento-ch/service-notifier#storage-channel)**  
  Stores notifications for later retrieval (e.g., user inbox, dashboard).

* **[Browser Channel](https://github.com/tobento-ch/service-notifier#browser-channel)**  
  Provides browser-based notifications.  
  This includes:
  - **Browser (pull-based)** - authenticated users  
  - **BrowserStream (SSE)** - authenticated users, real-time  
  - **BrowserGuest** - unauthenticated users  
  These channels are enabled through the corresponding features in the [Features](#features) section.

### Available Channels

Available channels represent the channels that are currently active and usable in your application.  
They can be used to:

- restrict which channels a service may use  
- display channel names or titles in the UI  
- customize channel titles  
- sort or map channels  

By default, all channels defined in the `app/config/notifier.php` file are available.

```php
use Tobento\App\Notifier\AvailableChannelsInterface;

$channels = $app->get(AvailableChannelsInterface::class);

// Check if a channel exists:
var_dump($channels->has(channel: 'sms'));
// bool(true)

// Get the title for a channel:
var_dump($channels->titleFor(channel: 'sms'));
// string(3) "Sms"

// Get all channel names:
var_dump($channels->names());
// array(3) {[0]=> string(4) "mail" [1]=> string(3) "sms" [2]=> string(7) "storage"}

// Get all channel titles:
var_dump($channels->titles());
// array(3) {[0]=> string(4) "Mail" [1]=> string(3) "Sms" [2]=> string(7) "Storage"}

// Convert titles to a string:
var_dump($channels->titlesToString(separator: ', '));
// string(18) "Mail, Sms, Storage"

// Add or override a channel title (returns a new instance):
$channels = $channels->withTitle(channel: 'sms', title: 'SMS Channel');
var_dump($channels->titleFor(channel: 'sms'));
// string(11) "SMS Channel"

// Map titles (returns a new instance):
$channels = $channels->map(fn($title, $name) => strtoupper($title));
var_dump($channels->titlesToString(separator: ', '));
// string(26) "MAIL, SMS CHANNEL, STORAGE"

// Sort channels by name (returns a new instance):
$channels = $channels->sortByName();

// Sort channels by title (returns a new instance):
$channels = $channels->sortByTitle();

// Count channels:
var_dump($channels->count());
// int(3)

// Keep only specific channels (returns a new instance):
$channels = $channels->only(['sms', 'mail']);

// Exclude specific channels (returns a new instance):
$channels = $channels->except(['sms', 'mail']);

// Iterate over channels:
foreach ($channels->all() as $name => $title) {}
// or simply:
foreach ($channels as $name => $title) {}
```

## Features

### Browser Feature

The **Browser Feature** enables pull-based browser notifications for authenticated users.  
Authentication is handled automatically through the application's user system.

This feature boots the following user-related components:

- `Tobento\App\User\Boot\User` from [app-user](https://github.com/tobento-ch/app-user) 
- `Tobento\App\User\Boot\Acl` from [app-user](https://github.com/tobento-ch/app-user) 
- `Tobento\App\User\Boot\HttpUserErrorHandler` from [app-user](https://github.com/tobento-ch/app-user) 

Because of this, the Browser Feature works out of the box with the default user system.  
If you need web-based authentication, you may install [app-user-web](https://github.com/tobento-ch/app-user-web).

The authenticated user is resolved from the request via:

```php
$user = $request->getAttribute(UserInterface::class);
```

The feature then checks:
- the user must implement UserInterface
- the user must be authenticated
- the user must have the required ACL permission (unless ACL is disabled)

If any of these conditions fail, an `AuthorizationException` is thrown.

**Requirements**

This feature requires the **[Browser View Feature](#browser-view-feature)** which automatically injects its required view snippet into your layout using `autoRenderOnView`.

**Config**

You can configure the Browser Feature in the [Notifier Config](#notifier-config) file:

```php
'features' => [
    new Feature\Browser(
        // The browser channels to use for this feature.
        browserChannels: ['browser'],
        
        // If true, routes are localized (default: true).
        localizeRoute: false,
        
        // ACL is enabled by default.
        // Disable ACL checks only for testing.
        // When ACL is enabled (recommended for production), users must have
        // the required permissions to receive browser notifications.
        withAcl: false,
    ),
],
```

**ACL Permissions**

To receive browser notifications, a user must have the following permission:

* ```notifications.browser``` User can receive browser notifications

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign this permission on the Roles or Users page.

**How Notifications Are Delivered**

When the browser polls the route:

```
POST /{?locale}/notifications/browser
```

The feature:
- Resolves the authenticated user
- Checks ACL (unless disabled)
- Fetches unread notifications from the configured browser channels
- Marks them as read
- Returns them in the JS-Notifier format

This is handled in the `browser()` method of the feature.

### Browser Stream Feature

The **Browser Stream Feature** enables real-time browser notifications using **Server‑Sent Events (SSE)**.  
Unlike the Browser Feature, which relies on polling, this feature pushes notifications to the browser instantly as they occur.

Authentication is handled automatically through the application's user system.

This feature boots the following user-related components:

- `Tobento\App\User\Boot\User` from [app-user](https://github.com/tobento-ch/app-user)  
- `Tobento\App\User\Boot\Acl` from [app-user](https://github.com/tobento-ch/app-user)  
- `Tobento\App\User\Boot\HttpUserErrorHandler` from [app-user](https://github.com/tobento-ch/app-user)

Because of this, the Browser Stream Feature works out of the box with the default user system.  
If you need web-based authentication, you may install [app-user-web](https://github.com/tobento-ch/app-user-web).

The authenticated user is resolved from the request via:

```php
$user = $request->getAttribute(UserInterface::class);
```

The feature then checks:
- the user must implement UserInterface
- the user must be authenticated
- the user must have the required ACL permission (unless ACL is disabled)

If any of these conditions fail, an `AuthorizationException` is thrown.

**Requirements**

This feature requires the **[Browser View Feature](#browser-view-feature)** which automatically injects its required view snippet into your layout using `autoRenderOnView`.

**Config**

You can configure the Browser Stream Feature in the [Notifier Config](#notifier-config) file:

```php
'features' => [
    new Feature\BrowserStream(
        // The browser channels to use for this feature.
        browserChannels: ['browser'],
        
        // If true, routes are localized (default: true).
        localizeRoute: false,
        
        // ACL is enabled by default.
        // Disable ACL checks only for testing.
        // When ACL is enabled (recommended for production), users must have
        // the required permissions to receive browser notifications.
        withAcl: false,
    ),
],
```

**ACL Permissions**

To receive browser notifications, a user must have the following permission:

* ```notifications.browser``` User can receive browser notifications

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign this permission on the Roles or Users page.

**How Notifications Are Delivered**

When the browser connects to the SSE endpoint:

```
GET /{?locale}/notifications/browser-stream
```

The feature:
- Resolves the authenticated user
- Checks ACL (unless disabled)
- Opens a persistent SSE connection
- Streams new notifications in real time
- Marks streamed notifications as delivered

This is handled in the `browser()` method of the feature.

### Browser Guest Feature

The **Browser Guest Feature** enables browser notifications for **guest users** (non-authenticated visitors).  
This is useful when your application needs to display notifications to users who are not logged in, such as storefront visitors, checkout flows, or public-facing pages.

Guest notifications are handled separately from authenticated user notifications and do not require a user account.

This feature boots the following user-related components:

- `Tobento\App\User\Boot\User` from [app-user](https://github.com/tobento-ch/app-user)  
- `Tobento\App\User\Boot\Acl` from [app-user](https://github.com/tobento-ch/app-user)  
- `Tobento\App\User\Boot\HttpUserErrorHandler` from [app-user](https://github.com/tobento-ch/app-user)

Because of this, the Browser Guest Feature works out of the box with the default user system.  
If you need web-based authentication for other features, you may install [app-user-web](https://github.com/tobento-ch/app-user-web).

Guest notifications do **not** require an authenticated user.  
Instead, the feature identifies the guest using a unique guest token stored in the session or cookie.

This feature does not require the **[Browser View Feature](#browser-view-feature)**.  

The feature registers its own view handler internally and will only render the snippet if:
- the user (guest) is allowed to receive browser notifications
- the `notifications.browser.guest` route exists
- ACL allows access (unless ACL is disabled)

If these conditions are not met, the view is not rendered.

**Config**

You can configure the Browser Guest Feature in the [Notifier Config](#notifier-config) file:

```php
'features' => [
    new Feature\BrowserGuest(
        // The browser channels to use for this feature.
        browserChannels: ['browser'],
        
        // The view 'notifier.browser.guest' is automatically rendered
        // when the specified view ('inc/head') is rendered.
        autoRenderOnView: 'inc/head',
        
        // You may disable auto rendering. If disabled, you must manually
        // include the snippet in your layout:
        //
        // <?= $view->render('notifier.browser.guest') ?>
        //
        // autoRenderOnView: null,
        
        // If true, routes are localized (default: true).
        localizeRoute: false,
        
        // ACL is enabled by default.
        // Disable ACL checks only for testing.
        // When ACL is enabled (recommended for production), guests must have
        // the required permissions to receive guest notifications.
        withAcl: false,
    ),
],
```

**ACL Permissions**

To receive browser notifications, a user must have the following permission:

* ```notifications.browser``` User can receive browser notifications

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign this permission on the Roles or Users page.

**How Guest Notifications Are Delivered**

When the browser polls the guest endpoint:

```
POST /{?locale}/notifications/browser-guest
```

The feature:
- Resolves the guest identifier (session-based or cookie-based)
- Checks ACL (unless disabled)
- Fetches unread guest notifications from the configured browser channels
- Marks them as read
- Returns them in the JS-Notifier format

This is handled in the `browser()` method of the feature.

**Sending Notifications**

See [Using Guest Recipient](#using-guest-recipient) for how to send notifications to guests.

### Browser View Feature

The **Browser View Feature** provides the view snippet required for enabling browser notifications in your application's layout.  
It does not register routes or deliver notifications - it only injects the JavaScript and HTML needed for the browser to poll or stream notifications.

This feature boots the following components:

- `Tobento\App\View\Boot\View` from [app-view](https://github.com/tobento-ch/app-view)

Because of this, the Browser View Feature works out of the box with the default view system.

**Config**

You can configure the Browser View Feature in the [Notifier Config](#notifier-config) file:

```php
'features' => [
    new Feature\BrowserView(
        // The browser channels to use for this feature.
        browserChannels: ['browser'],
        
        // The view 'notifier.browser' is automatically rendered
        // when the specified view ('inc/head') is rendered.
        autoRenderOnView: 'inc/head',
        
        // You may disable auto rendering. If disabled, you must manually
        // include the snippet in your layout:
        //
        // <?= $view->render('notifier.browser') ?>
        //
        // autoRenderOnView: null,
    ),
],
```

**What This Feature Does**

The feature registers a handler for the view key:

- `notifier.browser`

This handler:

- checks ACL (`notifications.browser`)
- checks whether the user is allowed to receive browser notifications  
  (based on authentication state and preferred notification channels)
- resolves the pull and stream URLs:
  - `notifications.browser`
  - `notifications.browser.stream`
- injects the view file:
  - `notifier/browser`

If any condition fails (ACL, user preference, missing route), the snippet is **not rendered**.

## Creating And Sending Notifications

### Using General Recipients

```php
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotifierInterface;
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

For more details, see the [Notifier Service - Creating And Sending Notifications](https://github.com/tobento-ch/service-notifier#creating-and-sending-notifications).

**Example sending to the current user (authenticated or guest)**

Make sure you have booted:

```php
$app->boot(\Tobento\App\User\Boot\User::class);
```

If any [browser features](#features) are enabled, it is automatically booted.

```php
use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\User\UserInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\UserRecipient;

class SomeService
{
    public function send(NotifierInterface $notifier, ServerRequestInterface $request): void
    {
        // Create a Notification to be sent using multiple channels
        $notification = new Notification(
            subject: 'Thanks for your order',
            content: 'Your order has been received and is being processed.',
            channels: ['mail', 'sms', 'browser'],
        );
        
        // Get the current user (authenticated or guest):
        $user = $request->getAttribute(UserInterface::class);

        // Create the recipient for the current user:
        $recipient = new UserRecipient(user: $user);

        // Send the notification to the recipient:
        $notifier->send($notification, $recipient);
    }
}
```

### Using Guest Recipient

```php
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\Service\Notifier\GuestRecipient;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\NotifierInterface;

class SomeService
{
    public function send(NotifierInterface $notifier, GuestResolverInterface $guestResolver): void
    {
        // Create a Notification that has to be sent:
        // using the "browser" channel
        $notification = new Notification(
            subject: 'Special Offer',
            content: 'A new discount is available for you!',
            channels: ['browser'],
        );

        // Send to the current guest session:
        $recipient = new GuestRecipient(
            id: $guestResolver->resolveId(),
        );

        // Or send to all guest sessions:
        $recipient = new GuestRecipient(
            id: null,
        );

        // Send the notification to the recipient:
        $notifier->send($notification, $recipient);
    }
}
```

For more details, see the [Notifier Service - Guest Recipient](https://github.com/tobento-ch/service-notifier#guest-recipient).

For more details, see the [Notifier Service - Creating And Sending Notifications](https://github.com/tobento-ch/service-notifier#creating-and-sending-notifications).

### Browser Message

Use the browser message type to send notifications to the in-browser [js-notifier](https://github.com/tobento-ch/js-notifier) UI.

```php
$notification = new Notification()
    ->addMessage('browser', new Message\Browser([
        'title' => 'Optional title',               // js-notifier: title
        'message' => 'You received a new order.',  // js-notifier: text
        'status' => 'warning',                     // js-notifier: status

        // Optional behavior
        'autotimeout' => 500,                      // js-notifier: autotimeout
        //'autotimeout' => null,                   // never closes
        'showCloseButton' => false,                // default: true

        // Action button
        'action_text' => 'View Order',             // js-notifier: action.title
        'action_route' => 'orders.view',           // js-notifier: action.url
        'action_route_parameters' => ['id' => 555],
        'action_attributes' => [                   // js-notifier: action.classes
            'class' => 'button',
        ],
    ]));
```

**js-notifier parameters**

You can find the full list of supported js-notifier parameters here:
https://github.com/tobento-ch/js-notifier#parameters

**Formatters**

If you use action parameters (`action_text`, `action_route`, etc.), ensure the [General Notification Formatter](#general-notification-formatter) is enabled.
This formatter is enabled by default.

#### Using `AbstractNotification`

```php
use Tobento\Service\Notifier\AbstractNotification;
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\RecipientInterface;

class SampleNotification extends AbstractNotification implements Message\ToBrowser
{
    /**
     * Returns the browser message.
     *
     * @param RecipientInterface $recipient
     * @param string $channel The channel name.
     * @return Message\BrowserInterface
     */
    public function toBrowser(RecipientInterface $recipient, string $channel): Message\BrowserInterface
    {
        return new Message\Browser(data: [
            'title' => 'Optional title',
            'message' => 'You received a new order.',
            'status' => 'warning',

            // Optional behavior
            'autotimeout' => 500,

            'action_text' => 'View Order',
            'action_route' => 'orders.view',
            'action_route_parameters' => ['id' => 555],
        ]);
    }
}
```

## Queuing Notifications

Sending notifications can be a time-consuming task. To avoid delays during request handling, you can queue notification messages for background processing.

To queue a notification, simply add the [Queue Parameter](https://github.com/tobento-ch/service-notifier#queue) to your message:

### Example

```php
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;
use Tobento\Service\Notifier\Parameter\Queue;

class SomeService
{
    public function send(NotifierInterface $notifier): void
    {
        // Create a Notification that has to be sent
        // using the "mail" and "sms" channels:
        $notification = new Notification(
            subject: 'New Invoice',
            content: 'You got a new invoice for 15 EUR.',
            channels: ['mail', 'sms'],
        )->parameter(new Queue(
            // You may specify the queue to be used:
            name: 'secondary',
            // You may specify a delay in seconds:
            delay: 30,
            // You may specify how many times to retry:
            retry: 3,
            // You may specify a priority:
            priority: 100,
            // You may specify if you want to encrypt the message:
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

The [Notifier Boot](#notifier-boot) automatically boots the [App Queue Boot](https://github.com/tobento-ch/app-queue#queue-boot), enabling queue support out of the box.

You only need to configure your queues in the [Queue Config](https://github.com/tobento-ch/app-queue#queue-config) file.

## Notification Formatters

Notification formatters may be used to format notifications stored by the [Storage Channel](https://github.com/tobento-ch/service-notifier#storage-channel) or the [Browser Channel](https://github.com/tobento-ch/service-notifier#browser-channel).

### General Notification Formatter

You may use the general formatter, which uses the following message data:

```php
use Tobento\Service\Notifier\Message;
use Tobento\Service\Notifier\Notification;

// Storage
$notification = new Notification()
    ->addMessage('storage', new Message\Storage([
        'message' => 'You received a new order.',
        'action_text' => 'View Order',
        'action_route' => 'orders.view',
        'action_route_parameters' => ['id' => 555],
    ]));

// Browser
$notification = new Notification()
    ->addMessage('browser', new Message\Browser([
        'message' => 'You received a new order.',
        'action_text' => 'View Order',
        'action_route' => 'orders.view',
        'action_route_parameters' => ['id' => 555],
    ]));
```

In ```app/config/notifier.php```:

```php
'formatters' => [
    \Tobento\App\Notifier\Formatting\GeneralNotificationFormatter::class,
],
```

The ```action_route``` and ```action_route_parameters``` values will be used to generate
the message URL if you have installed the [App Http - Routing Boot](https://github.com/tobento-ch/app-http#routing-boot).

The ```message``` and ```action_text``` values will be translated by the formatter if you
have installed the [App Translation Boot](https://github.com/tobento-ch/app-translation#translation-boot).

#### General Formatter Example

You may create your own general formatter for notifications:

```php
use Tobento\App\Notifier\Formatting\Notification;
use Tobento\App\Notifier\Formatting\NotificationFormatterInterface;

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

### Creating Notification Formatters

You may create a specific formatters to format only certain notifications:

```php
use Tobento\App\Notifier\Formatting\NotificationFormatterInterface;
use Tobento\App\Notifier\Formatting\Notification;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

class NewOrderNotificationFormatter implements NotificationFormatterInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RouterInterface $router,
    ) {}
    
    public function format(Notification $notification): Notification
    {
        // Only format specific notifications:
        if (!$notification->name() instanceof NewOrderNotification) {
            return $notification;
        }
        
        // Stop further formatters from modifying this notification:
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

## Custom Notifications

You may easily customize notifications by defining them in the `app/config/notifier.php` file:

```php
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\Parameter\Queue;

'notifications' => [
    // Using a custom notification:
    UserRegisterNotification::class => CustomUserRegisterNotification::class,
    
    // Using a notification factory:
    UserRegisterNotification::class => UserRegisterNotificationFactory::class,
    
    // Using a closure:
    UserRegisterNotification::class => function (UserRegisterNotification $notification): NotificationInterface {
        return $notification->parameter(new Queue());
    },
    
    // If named notification:
    'register' => CustomUserRegisterNotification::class,
],
```

### Creating Custom Notification

```php
use Tobento\Service\Notifier\AbstractNotification;
use Tobento\Service\Notifier\RecipientInterface;
use Tobento\Service\Notifier\Message;

class CustomUserRegisterNotification extends AbstractNotification implements Message\ToSms
{
    public function __construct(
        protected UserRegisterNotification $notification,
        // ...
    ) {}
    
    public function toSms(RecipientInterface $recipient, string $channel, SomeService $service): Message\SmsInterface
    {
        return new Message\Sms(
            subject: 'Thanks for your registration',
        );
    }
}
```

### Creating Notification Factory

```php
use Tobento\App\Notifier\NotificationFactoryInterface;
use Tobento\Service\Notifier\NotificationInterface;

class UserRegisterNotificationFactory implements NotificationFactoryInterface
{
    public function createNotification(NotificationInterface $notification): NotificationInterface
    {
        // create custom notification:
        
        // or modify original:
        return $notification;
    }
}
```

## Console

### Clear Notifications Command

If you have installed the [App Console](https://github.com/tobento-ch/app-console), you may clear all notifications from channels that support it, such as the [Storage Channel](https://github.com/tobento-ch/service-notifier#storage-channel) and [Browser Channel](https://github.com/tobento-ch/service-notifier#browser-channel), using the `user:notifications:clear` command.

**Clearing all notifications from all supported channels**

```
php ap user:notifications:clear
```

**Clearing all notifications from all specific channels**

```
php ap user:notifications:clear --channel=foo --channel=bar
```

**Available Options**

| Option | Description |
| ------ | ----------- |
| `--channel=name` | The name(s) of the channel(s) to clear. |
| `--read-only` | Clears only notifications that are marked as read. |
| `--unread-only` | Clears only notifications that are unread. |
| `--older-than-days=10` | Clears notifications older than the specified number of days. |

## App Notification Integration

You may also want to explore the [App Notification](https://github.com/tobento-ch/app-notification) package, which provides seamless integration of notifications into your application.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)