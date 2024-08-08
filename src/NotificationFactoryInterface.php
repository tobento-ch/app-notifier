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

use Tobento\Service\Notifier\NotificationInterface;

/**
 * NotificationFactoryInterface
 */
interface NotificationFactoryInterface
{
    /**
     * Returns the created notification.
     *
     * @param NotificationInterface $notification
     * @return NotificationInterface
     */
    public function createNotification(NotificationInterface $notification): NotificationInterface;
}