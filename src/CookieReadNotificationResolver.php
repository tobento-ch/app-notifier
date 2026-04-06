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

use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Cookie\CookiesInterface;
use Tobento\Service\Cookie\CookieValuesInterface;

/**
 * Resolves and persists read notification IDs for the current visitor
 * using a browser cookie.
 *
 * This implementation stores a comma-separated list of notification IDs
 * in a persistent cookie. It gracefully handles missing cookies and
 * invalid values.
 */
class CookieReadNotificationResolver implements ReadNotificationResolverInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param string $cookieName The cookie key used to store read IDs
     * @param int $lifetime Cookie lifetime in seconds (default: 1 year)
     */
    public function __construct(
        protected ServerRequestInterface $request,
        protected string $cookieName = 'browser_read_notifications',
        protected int $lifetime = 3600 * 24 * 365,
    ) {}

    /**
     * Retrieve the list of notification IDs that have already been read.
     *
     * @return array<int|string>
     */
    public function getReadIds(): array
    {
        $cookieValues = $this->request->getAttribute(CookieValuesInterface::class);

        if (!$cookieValues instanceof CookieValuesInterface) {
            return [];
        }

        if (!$cookieValues->has($this->cookieName)) {
            return [];
        }

        $raw = trim((string)$cookieValues->get($this->cookieName));

        if ($raw === '') {
            return [];
        }

        // Split comma-separated IDs
        return array_filter(explode(',', $raw), fn($id) => $id !== '');
    }

    /**
     * Persist the list of read notification IDs.
     *
     * @param array<int|string> $ids
     * @return void
     */
    public function storeReadIds(array $ids): void
    {
        $cookies = $this->request->getAttribute(CookiesInterface::class);

        if (!$cookies instanceof CookiesInterface) {
            return;
        }
        
        // Normalize and deduplicate
        $ids = array_unique(array_map('strval', $ids));
                
        $cookies->add(
            name: $this->cookieName,
            value: implode(',', $ids),
            lifetime: $this->lifetime,
        );
    }
}