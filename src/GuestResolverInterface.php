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
 * Resolves the unique guest identifier for the current visitor.
 *
 * Implementations may determine the guest ID using any mechanism
 * (e.g., cookies, session, tokens). If no identifier exists yet,
 * the resolver must create one and ensure it persists for future requests.
 */
interface GuestResolverInterface
{
    /**
     * Resolve or create the guest ID.
     *
     * @return string The resolved guest ID
     */
    public function resolveId(): string;
}