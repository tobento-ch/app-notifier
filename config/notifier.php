<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\Service\Notifier\ChannelInterface;
use Tobento\Service\Notifier\Mail;
use Tobento\Service\Notifier\Symfony;
use Tobento\Service\Notifier\Storage;
use Tobento\Service\Storage\StorageInterface;
use Psr\Container\ContainerInterface;

return [
    
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
        // using a factory:
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
        
        // using a closure:
        'storage' => static function (string $name, ContainerInterface $container): ChannelInterface {
            return new Storage\Channel(
                name: $name,
                repository: new Storage\StorageRepository(
                    storage: $container->get(StorageInterface::class)->new(),
                    table: 'notifications',
                ),
                container: $container,
            );
        },

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
    
];