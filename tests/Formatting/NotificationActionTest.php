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

namespace Tobento\App\Notifier\Test\Formatting;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\Formatting\NotificationAction;

class NotificationActionTest extends TestCase
{
    public function testGetMethods()
    {
        $action = new NotificationAction(text: 'text', url: 'url', attributes: ['class' => 'foo']);
        
        $this->assertSame('text', $action->text());
        $this->assertSame('url', $action->url());
        $this->assertSame(['class' => 'foo'], $action->attributes());
    }
}