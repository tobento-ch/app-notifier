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

namespace Tobento\App\Notifier\Storage;

/**
 * NotificationFormattersInterface
 */
interface NotificationFormattersInterface
{
    /**
     * Formats the specified notifications.
     *
     * @param iterable<Notification> $notifications
     * @return iterable<Notification>
     */
    public function formatMany(iterable $notifications): iterable;
    
    /**
     * Formats the specified notification.
     *
     * @param Notification $notification
     * @return Notification
     */
    public function format(Notification $notification): Notification;
}