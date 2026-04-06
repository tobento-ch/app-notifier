<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Tobento\App\Notifier\Feature;
use Tobento\App\Notifier\Formatting\NotificationFactoryInterface;
use Tobento\Service\Notifier\Browser;
use Tobento\Service\Notifier\ChannelInterface;
use Tobento\Service\Notifier\Mail;
use Tobento\Service\Notifier\Symfony;
use Tobento\Service\Notifier\Storage;
use Tobento\Service\Storage\StorageInterface;

return [

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Specify and configure the features you wish to use or remove if not needed.
    |
    | See: https://github.com/tobento-ch/app-notifier#features
    |
    */
    
    'features' => [
        // Renders the browser notification views (JS + assets) into your layout.
        // Required when using Browser or BrowserStream notifications.
        new Feature\BrowserView(
            browserChannels: ['browser'],
            autoRenderOnView: 'inc/head',
        ),
        
        // Enables pull‑based browser notifications for authenticated users.
        // Uses periodic polling to fetch new notifications.
        new Feature\Browser(
            browserChannels: ['browser'],
        ),
        
        // Enables real-time browser notifications for authenticated users
        // using Server-Sent Events (SSE). Falls back to pull mode if SSE
        // is not supported by the browser.
        new Feature\BrowserStream(
            browserChannels: ['browser'],
        ),

        // Enables browser notifications for guest (unauthenticated) users.
        // Useful when your application supports anonymous interactions.
        new Feature\BrowserGuest(
            browserChannels: ['browser'],
            autoRenderOnView: 'inc/head',
        ),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | Configure any channels needed for your application.
    |
    | see: https://github.com/tobento-ch/service-notifier#channel
    | see: https://github.com/tobento-ch/service-notifier#lazy-channels
    |
    */
    
    'channels' => [

        'mail' => [
            'factory' => Mail\ChannelFactory::class,
            'config' => [
                'mailer' => 'default',
            ],
        ],
        
        'sms' => [
            'factory' => Symfony\ChannelFactory::class,
            'config' => [
                'dsn' => 'vonage://KEY:SECRET@default?from=FROM',
                'channel' => \Symfony\Component\Notifier\Channel\SmsChannel::class,
            ],
        ],
        
        'storage' => static function (string $name, ContainerInterface $container): ChannelInterface {
            return new Storage\Channel(
                name: $name,
                repository: new Storage\StorageRepository(
                    storage: $container->get(StorageInterface::class)->new(),
                    table: 'user_notifications',
                    entityFactory: $container->get(NotificationFactoryInterface::class),
                ),
                container: $container,
            );
        },
        
        'browser' => static function (string $name, ContainerInterface $container): ChannelInterface {
            return new Browser\Channel(
                name: $name,
                repository: new Browser\StorageRepository(
                    storage: $container->get(StorageInterface::class)->new(),
                    table: 'browser_notifications',
                    entityFactory: $container->get(NotificationFactoryInterface::class),
                ),
                clock: $container->get(ClockInterface::class),
                container: $container,
            );
        },

    ],
    
    /*
    |--------------------------------------------------------------------------
    | Notification Formatters
    |--------------------------------------------------------------------------
    |
    | Configure any formatters needed for your application.
    |
    | These formatters are used not only for the storage channel, but for any
    | notification channel that uses the NotificationFactory - including browser
    | notifications and custom channels.
    |
    | see: https://github.com/tobento-ch/app-notifier#notification-formatters
    |
    */

    'formatters' => [
        \Tobento\App\Notifier\Formatting\GeneralNotificationFormatter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | You may specify a default queue name used for notifications being queued.
    | The queue name will only be used if no specifc were defined.
    |
    | see: https://github.com/tobento-ch/app-queue#queue-config
    |
    */

    'queue' => null, // if null default from queue config will be used.
    //'queue' => 'notifications',
    
    /*
    |--------------------------------------------------------------------------
    | Custom Notifications
    |--------------------------------------------------------------------------
    |
    | Configure any custom notifications.
    |
    | see: https://github.com/tobento-ch/app-notifier#custom-notifications
    |
    */
    
    'notifications' => [
        //
    ],    
];