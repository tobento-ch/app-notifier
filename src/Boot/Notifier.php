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
 
namespace Tobento\App\Notifier\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Mail\Boot\Mail;
use Tobento\App\Queue\Boot\Queue;
use Tobento\App\Database\Boot\Database;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\Notifier as ServiceNotifier;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\LazyChannels;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\Service\Notifier\Queue\QueueHandler;
use Psr\Container\ContainerInterface;

/**
 * Notifier
 */
class Notifier extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads notifier config file',
            'implements notifier interfaces',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        Mail::class,
        Database::class,
        Queue::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // install migration:
        $migration->install(\Tobento\App\Notifier\Migration\Notifier::class);
        
        // load the notifier config:
        $config = $config->load('notifier.php');
        
        // interfaces:
        $this->app->set(QueueHandlerInterface::class, QueueHandler::class)->with([
            'queueName' => $config['queue'] ?? null
        ]);
        
        $this->app->set(
            ChannelsInterface::class,
            static function(ContainerInterface $container) use ($config): ChannelsInterface {
                return new LazyChannels(
                    container: $container,
                    channels: $config['channels'] ?? [],
                );
            }
        );
        
        $this->app->set(NotifierInterface::class, ServiceNotifier::class);

        // install migration after channels:
        /*$migrator = $this->app->get(\Tobento\Service\Migration\MigratorInterface::class);
        $installed = $migrator->install(\Tobento\App\Notifier\Migration\Storages::class);
        echo '<pre>';
        print_r($installed->actions());
        exit;*/
        
        $migration->install(\Tobento\App\Notifier\Migration\Storages::class);
    }
}