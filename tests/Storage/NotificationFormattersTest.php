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
use Tobento\App\Notifier\Storage\NotificationFormatters;
use Tobento\App\Notifier\Storage\NotificationFormatterInterface;
use Tobento\App\Notifier\Storage\Notification;
use Tobento\App\Notifier\Test\Mock;
use Tobento\Service\Container\Container;

class NotificationFormattersTest extends TestCase
{
    public function testFormatMethod()
    {
        $formatters = new NotificationFormatters(
            container: new Container(),
            formatters: [
                Mock\FooNotificationFormatter::class,
                Mock\BarNotificationFormatter::class,
            ],
        );
        
        $n = new Notification(['data' => ['message' => 'Msg']]);
        
        $this->assertSame('Bar', $formatters->format($n)->message());
    }
    
    public function testFormatMethodPropagationIsStopped()
    {
        $formatters = new NotificationFormatters(
            container: new Container(),
            formatters: [
                Mock\BarNotificationFormatter::class,
                Mock\FooNotificationFormatter::class,
            ],
        );
        
        $n = new Notification(['data' => ['message' => 'Msg']]);
        
        $this->assertSame('Bar', $formatters->format($n)->message());
    }
    
    public function testFormatManyMethod()
    {
        $formatters = new NotificationFormatters(
            container: new Container(),
            formatters: [
                Mock\FooNotificationFormatter::class,
            ],
        );
        
        $formatted = $formatters->formatMany([
            new Notification(['data' => ['message' => 'Msg']]),
            new Notification(['data' => ['message' => 'Msg']])
        ]);
        
        $this->assertSame(2, count($formatted));
    }    
}