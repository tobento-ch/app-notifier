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

namespace Tobento\App\Notifier\Test\Storage;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\Storage\NotificationAction;

class NotificationActionTest extends TestCase
{
    public function testGetMethods()
    {
        $action = new NotificationAction(text: 'text', url: 'url');
        
        $this->assertSame('text', $action->text());
        $this->assertSame('url', $action->url());
    }
}