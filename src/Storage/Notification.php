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

use Tobento\Service\Collection\Arr;
use Stringable;

/**
 * Notification
 */
class Notification
{
    /**
     * @var array<array-key, NotificationAction>
     */
    protected array $actions = [];
    
    /**
     * @var bool
     */
    protected bool $isPropagationStopped = false;
    
    /**
     * Create a new Notification.
     *
     * @param array $attributes
     */
    public function __construct(
        protected array $attributes,
    ) {}
    
    /**
     * Get an attribute value by key.
     *
     * @param string $key The key.
     * @param mixed $default A default value.
     * @return mixed The the default value if not exist.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }
    
    /**
     * Determines if an attribute value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->attributes, $key);
    }
    
    /**
     * Returns the notification id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->ensureString($this->get('id'));
    }
    
    /**
     * Returns the notification name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->ensureString($this->get('name'));
    }
    
    /**
     * Returns the recipient ID.
     *
     * @return string
     */
    public function recipientId(): string
    {
        return $this->ensureString($this->get('recipient_id'));
    }
    
    /**
     * Returns the recipient type.
     *
     * @return string
     */
    public function recipientType(): string
    {
        return $this->ensureString($this->get('recipient_type'));
    }
    
    /**
     * Returns the message.
     *
     * @return string
     */
    public function message(): string
    {
        if ($this->has('data.message')) {
            return $this->ensureString($this->get('data.message'));
        }
        
        return $this->ensureString($this->get('data.subject'));
    }
    
    /**
     * Returns a new instance with the specified message.
     *
     * @param string $message
     * @return static
     */
    public function withMessage(string $message): static
    {
        $new = clone $this;
        $new->set('data.message', $message);
        return $new;
    }

    /**
     * Returns a new instance with the added action.
     *
     * @param string $text
     * @param string|Stringable $url
     * @return static
     */
    public function withAddedAction(string $text, string|Stringable $url): static
    {
        $new = clone $this;
        $new->actions[] = new NotificationAction($text, (string)$url);
        return $new;
    }
    
    /**
     * Returns the action.
     *
     * @return array<array-key, NotificationAction>
     */
    public function actions(): array
    {
        return $this->actions;
    }
    
    /**
     * Is propagation stopped?
     *
     * This will typically only be used by the Formatters to determine if the
     * previous Formatter halted propagation.
     *
     * @return bool
     *   True if the formatting is complete and no further formatters should be called.
     *   False to continue calling formatters.
     */
    public function isPropagationStopped() : bool
    {
        return $this->isPropagationStopped;
    }
    
    /**
     * If to stop propagation.
     *
     * @param bool $stop
     * @return static $this
     */
    public function stopPropagation(bool $stop = true) : static
    {
        $this->isPropagationStopped = $stop;
        return $this;
    }
    
    /**
     * Returns the message.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->message();
    }
    
    /**
     * Set an attribute value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function set(string $key, mixed $value): void
    {
        $this->attributes = Arr::set($this->attributes, $key, $value);
    }
    
    /**
     * Ensure string.
     *
     * @param mixed $value
     * @return string
     */
    protected function ensureString(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string)$value;
        }
        
        return '';
    }
}