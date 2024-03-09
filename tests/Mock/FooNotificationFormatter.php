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

namespace Tobento\App\Notifier\Test\Mock;

use Tobento\App\Notifier\Storage\NotificationFormatterInterface;
use Tobento\App\Notifier\Storage\Notification;

class FooNotificationFormatter implements NotificationFormatterInterface
{
    public function format(Notification $notification): Notification
    {
        return $notification->withMessage('Foo');
    }
}