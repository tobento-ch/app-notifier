<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\Symfony;
use Tobento\Service\Mail\Address;
use Tobento\Service\Mail\Parameters;
use Tobento\Service\Mail\Parameter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport;
use function Tobento\App\{directory};

return [
    
    /*
    |--------------------------------------------------------------------------
    | Mailers
    |--------------------------------------------------------------------------
    |
    | Configure any mailers needed for your application.
    | The first mailer is the default mailer.
    |
    | see: https://github.com/tobento-ch/service-mail#symfony-dsn-mailer-factory
    | see: https://github.com/tobento-ch/service-mail#symfony-smtp-mailer-factory
    |
    */
    
    'mailers' => [
        
        'default' => [
            'factory' => Symfony\DsnMailerFactory::class,
            'config' => [
                'dsn' => 'null://null',
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Addresses And Parameters
    |--------------------------------------------------------------------------
    |
    | You may specify any default addresses and parameters used by
    | all mailers. On each mailer you may overwrite them individually though.
    |
    */

    'defaults' => [
        //'from' => 'from@example.com',
        // with object:
        'from' => new Address('from@example.com', 'FromName'),

        'replyTo' => 'reply@example.com',
        // with object:
        //'replyTo' => new Address('reply@example.com'),

        // You may define an address to send all emails to:
        //'alwaysTo' => 'debug@example.com',
        // with object:
        //'alwaysTo' => new Address('debug@example.com'),

        'parameters' => new Parameters(
            new Parameter\PathHeader('Return-Path', 'return@example.com'),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | You may specify a default queue name used for messages being queued.
    | The queue name will only be used if no specifc were defined.
    |
    | see: https://github.com/tobento-ch/app-queue#queue-config
    |
    */

    'queue' => 'mails', // if null default from queue config will be used.
    //'queue' => 'mails',
    
];