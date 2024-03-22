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
use Tobento\App\Notifier\Storage\Notification;

class NotificationTest extends TestCase
{
    public function testGetMethod()
    {
        $n = new Notification(['foo' => 'Foo', 'data' => ['msg' => 'Msg']]);
        
        $this->assertSame('Foo', $n->get('foo'));
        $this->assertSame(null, $n->get('bar'));
        $this->assertSame('Bar', $n->get('bar', 'Bar'));
        $this->assertSame('Msg', $n->get('data.msg'));
        $this->assertSame(null, $n->get('data.foo'));
        $this->assertSame('Foo', $n->get('data.foo', 'Foo'));
    }
    
    public function testHasMethod()
    {
        $n = new Notification(['foo' => 'Foo', 'data' => ['msg' => 'Msg']]);
        
        $this->assertTrue($n->has('foo'));
        $this->assertFalse($n->has('bar'));
        $this->assertTrue($n->has('data.msg'));
        $this->assertFalse($n->has('data.foo'));
    }
    
    public function testIdMethod()
    {
        $this->assertSame('Foo', (new Notification(['id' => 'Foo']))->id());
        $this->assertSame('', (new Notification([]))->id());
    }
    
    public function testNameMethod()
    {
        $this->assertSame('Foo', (new Notification(['name' => 'Foo']))->name());
        $this->assertSame('', (new Notification([]))->name());
    }
    
    public function testRecipientIdMethod()
    {
        $this->assertSame('Foo', (new Notification(['recipient_id' => 'Foo']))->recipientId());
        $this->assertSame('', (new Notification([]))->recipientId());
    }
    
    public function testRecipientTypeMethod()
    {
        $this->assertSame('Foo', (new Notification(['recipient_type' => 'Foo']))->recipientType());
        $this->assertSame('', (new Notification([]))->recipientType());
    }
    
    public function testMessageMethod()
    {
        $this->assertSame('', (new Notification([]))->message());
        $this->assertSame('Msg', (new Notification(['data' => ['message' => 'Msg']]))->message());
        $this->assertSame('Sub', (new Notification(['data' => ['subject' => 'Sub']]))->message());
        
        $this->assertSame(
            'Msg',
            (new Notification(['data' => ['message' => 'Msg', 'subject' => 'Sub']]))->message()
        );
    }
    
    public function testWithMessageMethod()
    {
        $n = new Notification(['data' => ['message' => 'Msg']]);
        $new = $n->withMessage('New');
        
        $this->assertFalse($n === $new);
        $this->assertSame('Msg', $n->message());
        $this->assertSame('New', $new->message());
    }
    
    public function testWithAddedActionMethod()
    {
        $n = new Notification(['data' => ['message' => 'Msg']]);
        $new = $n->withAddedAction('text', 'url');
        
        $this->assertFalse($n === $new);
        $this->assertSame(0, count($n->actions()));
        $this->assertSame(1, count($new->actions()));
    }
    
    public function testPropagationMethods()
    {
        $n = new Notification([]);
        $this->assertFalse($n->isPropagationStopped());
        $n->stopPropagation();
        $this->assertTrue($n->isPropagationStopped());
        $n->stopPropagation(false);
        $this->assertFalse($n->isPropagationStopped());
    }
}