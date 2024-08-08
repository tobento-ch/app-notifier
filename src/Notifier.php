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

namespace Tobento\App\Notifier;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tobento\Service\Notifier\ChannelMessagesInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\Exception\NotifierException;
use Tobento\Service\Notifier\NotificationInterface;
use Tobento\Service\Notifier\Notifier as ServiceNotifier;
use Tobento\Service\Notifier\QueueHandlerInterface;
use Tobento\Service\Notifier\RecipientInterface;

/**
 * Notifier
 */
class Notifier extends ServiceNotifier
{
    /**
     * Create a new Notifier.
     *
     * @param ChannelsInterface $channels
     * @param null|QueueHandlerInterface $queueHandler
     * @param null|EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        protected NotificationsInterface $notifications,
        protected ChannelsInterface $channels,
        protected null|QueueHandlerInterface $queueHandler = null,
        protected null|EventDispatcherInterface $eventDispatcher = null,
    ) {}
    
    /**
     * Send the notification to the specified recipients.
     *
     * @param NotificationInterface $notification
     * @param RecipientInterface ...$recipients
     * @return iterable<int, ChannelMessagesInterface>
     * @throws NotifierException
     */
    public function send(NotificationInterface $notification, RecipientInterface ...$recipients): iterable
    {
        return parent::send($this->notifications->get($notification), ...$recipients);
    }
}