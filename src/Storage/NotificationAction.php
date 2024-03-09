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

/**
 * NotificationAction
 */
class NotificationAction
{
    /**
     * Create a new NotificationAction.
     *
     * @param string $text
     * @param string $url
     */
    public function __construct(
        protected string $text,
        protected string $url,
    ) {}
    
    /**
     * Returns the text.
     *
     * @return string
     */
    public function text(): string
    {
        return $this->text;
    }
    
    /**
     * Returns the url.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }
}