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

/**
 * Resolves and persists the read-notification state for the current visitor.
 *
 * Implementations may store read notification identifiers using any mechanism
 * (e.g., cookies, session, tokens, database). The resolver is responsible for
 * returning all previously stored read notification IDs and persisting newly
 * read ones for future requests.
 */
interface ReadNotificationResolverInterface
{
    /**
     * Retrieve the list of notification IDs that have already been read.
     *
     * @return array<int|string> The IDs of notifications previously marked as read
     */
    public function getReadIds(): array;

    /**
     * Persist the list of read notification IDs.
     *
     * Implementations may merge the provided IDs with existing ones or overwrite
     * them entirely, depending on the storage strategy.
     *
     * @param array<int|string> $ids Notification IDs to persist as read
     * @return void
     */
    public function storeReadIds(array $ids): void;
}