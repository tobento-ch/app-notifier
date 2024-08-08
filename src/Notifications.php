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

use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Notifier\NotificationInterface;
use Closure;

/**
 * Custom notifications.
 */
class Notifications implements NotificationsInterface
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new Notifications.
     *
     * @param ContainerInterface $container
     * @param array<string, mixed> $notifications
     */
    public function __construct(
        ContainerInterface $container,
        protected array $notifications,
    ) {
        $this->autowire = new Autowire($container);
    }

    /**
     * Add a notification.
     *
     * @param string $name
     * @param mixed $notification
     * @return static $this
     */
    public function add(string $name, mixed $notification): static
    {
        $this->notifications[$name] = $notification;
        return $this;
    }
    
    /**
     * Returns true if has notification, otherwise false.
     *
     * @param string $notification
     * @return bool
     */
    public function has(string $notification): bool
    {
        return array_key_exists($notification, $this->notifications);
    }
    
    /**
     * Returns the custom notification if exists, otherwise the given notification.
     *
     * @param NotificationInterface $notification
     * @return NotificationInterface
     */
    public function get(NotificationInterface $notification): NotificationInterface
    {
        if (!isset($this->notifications[$notification->getName()])) {
            return $notification;
        }
        
        $customNotification = $this->notifications[$notification->getName()];
        
        if ($customNotification instanceof Closure) {
            $customNotification = $this->autowire->call($customNotification, ['notification' => $notification]);
        } elseif (is_string($customNotification)) {
            $customNotification = $this->autowire->resolve($customNotification, ['notification' => $notification]);
        }

        if ($customNotification instanceof NotificationInterface) {
            return $customNotification;
        }
        
        if ($customNotification instanceof NotificationFactoryInterface) {
            return $customNotification->createNotification($notification);
        }
        
        return $notification;
    }
    
    /**
     * Returns all notifications.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->notifications;
    }

    /**
     * Returns the notification names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->notifications);
    }
}