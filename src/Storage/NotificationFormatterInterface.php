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
 * NotificationFormatterInterface
 */
interface NotificationFormatterInterface
{
    /**
     * Returns the formatted notification.
     *
     * @param Notification $notification
     * @return Notification
     */
    public function format(Notification $notification): Notification;
}