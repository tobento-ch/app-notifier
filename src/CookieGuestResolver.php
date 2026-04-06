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


class CookieGuestResolver implements GuestResolverInterface
{
    protected null|string $createdId = null;

    /**
     * Create a new instance.
     *
     * @param ServerRequestInterface $request
     * @param string $cookieName
     * @param string $prefix
     */
    public function __construct(
        protected ServerRequestInterface $request,
        protected string $cookieName = 'browser_guest_id',
        protected string $prefix = 'guest:',
    ) {}
    
    /**
     * Resolve or create the guest ID.
     *
     * @return string The resolved guest ID
     */
    public function resolveId(): string
    {
        $cookieValues = $this->request->getAttribute(CookieValuesInterface::class);
        
        // If cookie already exists use it
        if (
            $cookieValues
            && $cookieValues instanceof CookieValuesInterface
            && $cookieValues->has($this->cookieName)
        ) {
            return $cookieValues->get($this->cookieName);
        }
        
        // If we already generated an ID during this request, reuse it
        if ($this->createdId !== null) {
            return $this->createdId;
        }
        
        // Generate new ID
        $id = $this->prefix.bin2hex(random_bytes(16));
        $this->createdId = $id;
        
        // Write cookie to response
        $cookies = $this->request->getAttribute(CookiesInterface::class);
                
        if ($cookies instanceof CookiesInterface) {
            $cookies->add(
                name: $this->cookieName,
                value: $id,
                lifetime: 3600 * 24 * 365, // 1 year
            );
        }
        
        return $id;
    }
}