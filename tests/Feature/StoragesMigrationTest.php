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
use Tobento\App\Notifier\Migration\Storages;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

class StoragesMigrationTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        return $app;
    }
    
    public function testInstallCreatesStorageAndBrowserTables()
    {
        $app = $this->bootingApp();
        
        $actions = new Storages($app->get(ChannelsInterface::class))->install()->all();
        
        $tables = [];
        
        foreach($actions as $action) {
            if ($action instanceof RepositoryAction) {
                $tables[] = $action->name();
            }
        }
        
        $this->assertSame(['user_notifications', 'browser_notifications'], $tables);
    }
    
    public function testUninstallDeletesStorageAndBrowserTables()
    {
        $app = $this->bootingApp();
        
        $actions = new Storages($app->get(ChannelsInterface::class))->uninstall()->all();
        
        $tables = [];
        
        foreach($actions as $action) {
            if ($action instanceof RepositoryDeleteAction) {
                $tables[] = $action->name();
            }
        }
        
        $this->assertSame(['user_notifications', 'browser_notifications'], $tables);
    }
}