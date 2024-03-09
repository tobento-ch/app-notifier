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

use IteratorAggregate;
use Countable;

/**
 * The available channels. May be used to verify channels, display channel names, e.g.
 */
interface AvailableChannelsInterface extends Countable, IteratorAggregate
{
    /**
     * Returns true if has channel, otherwise false.
     *
     * @param string $channel
     * @return bool
     */
    public function has(string $channel): bool;
    
    /**
     * Returns the channel title if exists, otherwise null.
     *
     * @param string $channel
     * @return null|string
     */
    public function titleFor(string $channel): null|string;
    
    /**
     * Returns a new instance with the added channel title if the channel exists.
     *
     * @param string $channel
     * @param string $title
     * @return static
     */
    public function withTitle(string $channel, string $title): static;
    
    /**
     * Returns all channels.
     *
     * @return iterable<string, string>
     */
    public function all(): iterable;

    /**
     * Returns the channel names.
     *
     * @return array<int, string>
     */
    public function names(): array;
    
    /**
     * Returns the channel titles.
     *
     * @return array<int, string>
     */
    public function titles(): array;
    
    /**
     * Returns a new instance with countries with only the codes specified.
     *
     * @param array $channels
     * @return static
     */
    public function only(array $channels): static;
    
    /**
     * Returns a new instance with the channels except those specified.
     *
     * @param array $channels
     * @return static
     */
    public function except(array $channels): static;
    
    /**
     * Returns a new instance with the channels mapped.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static;

    /**
     * Returns a new instance with the channels sorted by its name.
     *
     * @return static
     */
    public function sortByName(): static;
    
    /**
     * Returns a new instance with the channels sorted by its title.
     *
     * @return static
     */
    public function sortByTitle(): static;
    
    /**
     * Returns the channel titles as string.
     *
     * @param string $separator
     * @return string
     */
    public function titlesToString(string $separator = ','): string;
}