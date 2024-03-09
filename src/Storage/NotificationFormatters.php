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

use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Autowire\AutowireException;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * The formatters to format storage notifications.
 */
final class NotificationFormatters implements NotificationFormattersInterface
{
    /**
     * @var Autowire
     */
    private Autowire $autowire;
    
    /**
     * @var array<class-string, NotificationFormatterInterface>
     */
    private array $created = [];
    
    /**
     * Create a new Formatters.
     *
     * @param ContainerInterface $container
     * @param iterable<class-string> $formatters
     */
    public function __construct(
        ContainerInterface $container,
        private iterable $formatters = [],
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Formats the specified notifications.
     *
     * @param iterable<Notification> $notifications
     * @return iterable<Notification>
     */
    public function formatMany(iterable $notifications): iterable
    {
        $formatted = [];
        
        foreach($notifications as $notification) {
            $formatted[] = $this->format($notification);
        }

        return $formatted;
    }
    
    /**
     * Formats the specified notification.
     *
     * @param Notification $notification
     * @return Notification
     */
    public function format(Notification $notification): Notification
    {
        foreach($this->formatters as $formatter) {
            $formatter = $this->ensureFormatter($formatter);
            
            $notification = $formatter->format($notification);
            
            if ($notification->isPropagationStopped()) {
                return $notification;
            }
        }

        return $notification;
    }
    
    /**
     * Ensure formatter.
     *
     * @param mixed $formatter
     * @return NotificationFormatterInterface
     * @throws InvalidArgumentException
     * @throws AutowireException
     */
    private function ensureFormatter(mixed $formatter): NotificationFormatterInterface
    {
        if (!is_string($formatter)) {
            throw new InvalidArgumentException('Formatter needs to be a class name string!');
        }
        
        if (isset($this->created[$formatter])) {
            return $this->created[$formatter];
        }
        
        $formatter = $this->autowire->resolve($formatter);
        
        if (! $formatter instanceof NotificationFormatterInterface) {
            throw new InvalidArgumentException(
                sprintf('Formatter needs to be an instanceof %s', NotificationFormatterInterface::class)
            );
        }
        
        return $this->created[$formatter::class] = $formatter;
    }
}