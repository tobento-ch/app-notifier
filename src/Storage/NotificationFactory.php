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

use Tobento\Service\Repository\Storage\EntityFactory;

/**
 * NotificationFactory
 */
class NotificationFactory extends EntityFactory implements NotificationFactoryInterface
{
    /**
     * Create a new NotificationFactory.
     *
     * @param null|NotificationFormattersInterface $formatters
     */
    public function __construct(
        protected null|NotificationFormattersInterface $formatters,
    ) {
        parent::__construct(null);
    }
    
    /**
     * Create an entity from array.
     *
     * @param array $attributes
     * @return Notification The created entity.
     * @throws \Throwable If cannot create notification
     */
    public function createEntityFromArray(array $attributes): Notification
    {
        // Process the columns reading:
        $attributes = $this->columns->processReading($attributes);
        
        $notification = new Notification($attributes);
        
        if ($this->formatters) {
            return $this->formatters->format($notification);
        }
        
        return $notification;
    }
}