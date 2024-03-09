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

use ArrayIterator;
use Traversable;

/**
 * The available channels. May be used to verify channels, display channel names, e.g.
 */
class AvailableChannels implements AvailableChannelsInterface
{
    /**
     * @var array<string, string>
     */
    protected array $channels = [];
    
    /**
     * Create a new AvailableChannels.
     *
     * @param array<array-key, string> $channels
     */
    public function __construct(
        array $channels,
    ) {
        foreach($channels as $channel) {
            $this->channels[$channel] = ucfirst($channel);
        }
    }
    
    /**
     * Returns true if has channel, otherwise false.
     *
     * @param string $channel
     * @return bool
     */
    public function has(string $channel): bool
    {
        return array_key_exists($channel, $this->channels);
    }
    
    /**
     * Returns the channel title if exists, otherwise null.
     *
     * @param string $channel
     * @return null|string
     */
    public function titleFor(string $channel): null|string
    {
        return $this->channels[$channel] ?? null;
    }
    
    /**
     * Returns a new instance with the added channel title if the channel exists.
     *
     * @param string $channel
     * @param string $title
     * @return static
     */
    public function withTitle(string $channel, string $title): static
    {
        $new = clone $this;
        
        if ($new->has($channel)) {
            $new->channels[$channel] = ucfirst($title);
        }
        
        return $new;
    }
    
    /**
     * Returns all channels.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->channels;
    }

    /**
     * Returns the channel names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->channels);
    }
    
    /**
     * Returns the channel titles.
     *
     * @return array<int, string>
     */
    public function titles(): array
    {
        return array_values($this->channels);
    }
    
    /**
     * Returns the channel titles as string.
     *
     * @param string $separator
     * @return string
     */
    public function titlesToString(string $separator = ', '): string
    {
        return implode($separator, $this->titles());
    }
    
    /**
     * Returns a new instance with with only the channels specified.
     *
     * @param array $channels
     * @return static
     */
    public function only(array $channels): static
    {
        return $this->filter(
            fn(string $channel): bool => in_array($channel, $channels)
        );
    }
    
    /**
     * Returns a new instance with the channels except those specified.
     *
     * @param array $channels
     * @return static
     */
    public function except(array $channels): static
    {
        return $this->filter(
            fn(string $channel): bool => !in_array($channel, $channels)
        );
    }
    
    /**
     * Returns a new instance with the channels mapped.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $keys = array_keys($this->all());
        $titles = array_map($callback, $this->all(), $keys);
        
        $new = clone $this;
        $new->channels = array_combine($keys, $titles);
        return $new;
    }

    /**
     * Returns a new instance with the channels sorted by its name.
     *
     * @return static
     */
    public function sortByName(): static
    {
        $new = clone $this;
        ksort($new->channels);
        return $new;
    }
    
    /**
     * Returns a new instance with the channels sorted by its title.
     *
     * @return static
     */
    public function sortByTitle(): static
    {
        $new = clone $this;
        asort($new->channels);
        return $new;
    }
    
    /**
     * Returns the number of channels.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->channels);
    }
    
    /**
     * Returns the iterator. 
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->channels);
    }
    
    /**
     * Returns a new instance with the filtered channels.
     *
     * @param callable $callback
     * @param int $mode
     * @return static
     */
    protected function filter(callable $callback, int $mode = ARRAY_FILTER_USE_KEY): static
    {
        $new = clone $this;
        $new->channels = array_filter($this->channels, $callback, $mode);
        return $new;
    }
}