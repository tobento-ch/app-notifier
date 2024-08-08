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
 * NotificationsInterface
 */
interface NotificationsInterface
{
    /**
     * Add a notification.
     *
     * @param string $name
     * @param mixed $notification
     * @return static $this
     */
    public function add(string $name, mixed $notification): static;
    
    /**
     * Returns true if has notification, otherwise false.
     *
     * @param string $notification
     * @return bool
     */
    public function has(string $notification): bool;
    
    /**
     * Returns the custom notification if exists, otherwise the given notification.
     *
     * @param NotificationInterface $notification
     * @return NotificationInterface
     */
    public function get(NotificationInterface $notification): NotificationInterface;
    
    /**
     * Returns all notifications.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Returns the notification names.
     *
     * @return array<int, string>
     */
    public function names(): array;
}