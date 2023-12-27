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

namespace Tobento\App\Notifier\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\Storage\Channel as ChannelStorage;
use Tobento\Service\Notifier\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

/**
 * Storages migration.
 */
class Storages implements MigrationInterface
{
    /**
     * Create a new Storages.
     *
     * @param ChannelsInterface $channels
     */
    public function __construct(
        protected ChannelsInterface $channels,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Notifier storages.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $actions = [];

        foreach($this->channels->names() as $name) {
            $channel = $this->channels->get($name);
            
            if (
                $channel instanceof ChannelStorage
                && $channel->repository() instanceof StorageRepository
            ) {
                $actions[] = RepositoryAction::newOrNull(
                    repository: $channel->repository(),
                    description: sprintf('Notification channel storage %s', $channel->name()),
                );
            }
        }
        
        return new Actions(...$actions);
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        $actions = [];
        
        foreach($this->channels->names() as $name) {
            $channel = $this->channels->get($name);
            
            if (
                $channel instanceof ChannelStorage
                && $channel->repository() instanceof StorageRepository
            ) {
                $actions[] = RepositoryDeleteAction::newOrNull(
                    repository: $channel->repository(),
                    description: sprintf('Notification channel storage %s', $channel->name()),
                );
            }
        }
        
        return new Actions(...$actions);
    }
}