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

namespace Tobento\App\Notifier\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\AvailableChannels;
use Tobento\App\Notifier\AvailableChannelsInterface;

class AvailableChannelsTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceof(AvailableChannelsInterface::class, new AvailableChannels([]));
    }
    
    public function testHasMethod()
    {
        $channels = new AvailableChannels(['sms']);
        
        $this->assertTrue($channels->has('sms'));
        $this->assertFalse($channels->has('mail'));
    }
    
    public function testTitleForMethod()
    {
        $channels = new AvailableChannels(['sms']);
        
        $this->assertSame('Sms', $channels->titleFor(channel: 'sms'));
        $this->assertSame(null, $channels->titleFor(channel: 'mail'));
    }
    
    public function testWithTitleMethod()
    {
        $channels = new AvailableChannels(['sms']);
        $newChannels = $channels->withTitle(channel: 'sms', title: 'SMS');
        
        $this->assertFalse($channels === $newChannels);
        $this->assertSame('SMS', $newChannels->titleFor(channel: 'sms'));
    }
    
    public function testAllMethod()
    {
        $this->assertSame(
            [],
            (new AvailableChannels([]))->all()
        );
        
        $this->assertSame(
            ['sms' => 'Sms', 'mail' => 'Mail'],
            (new AvailableChannels(['sms', 'mail']))->all()
        );
    }
    
    public function testNamesMethod()
    {
        $this->assertSame(
            [],
            (new AvailableChannels([]))->names()
        );
        
        $this->assertSame(
            ['sms', 'mail'],
            (new AvailableChannels(['sms', 'mail']))->names()
        );
    }
    
    public function testTitlesMethod()
    {
        $this->assertSame(
            [],
            (new AvailableChannels([]))->titles()
        );
        
        $this->assertSame(
            ['Sms', 'Mail'],
            (new AvailableChannels(['sms', 'mail']))->titles()
        );
    }
    
    public function testTitlesToStringMethod()
    {
        $this->assertSame(
            '',
            (new AvailableChannels([]))->titlesToString()
        );
        
        $this->assertSame(
            'Sms, Mail',
            (new AvailableChannels(['sms', 'mail']))->titlesToString()
        );
        
        $this->assertSame(
            'Sms / Mail',
            (new AvailableChannels(['sms', 'mail']))->titlesToString(separator: ' / ')
        );
    }
    
    public function testOnlyMethod()
    {
        $channels = new AvailableChannels([]);
        $newChannels = $channels->only([]);
        
        $this->assertFalse($channels === $newChannels);
            
        $this->assertSame(
            [],
            (new AvailableChannels([]))->only([])->names()
        );
        
        $this->assertSame(
            ['sms', 'mail'],
            (new AvailableChannels(['sms', 'mail', 'storage']))->only(['sms', 'mail'])->names()
        );
        
        $this->assertSame(
            [],
            (new AvailableChannels(['sms', 'mail']))->only([])->names()
        );
    }
    
    public function testExceptMethod()
    {
        $channels = new AvailableChannels([]);
        $newChannels = $channels->except([]);
        
        $this->assertFalse($channels === $newChannels);
            
        $this->assertSame(
            [],
            (new AvailableChannels([]))->except([])->names()
        );
        
        $this->assertSame(
            ['storage'],
            (new AvailableChannels(['sms', 'mail', 'storage']))->except(['sms', 'mail'])->names()
        );
        
        $this->assertSame(
            ['sms'],
            (new AvailableChannels(['sms']))->except([])->names()
        );
    }
    
    public function testMapMethod()
    {
        $channels = new AvailableChannels([]);
        $newChannels = $channels->map(fn($title, $name) => strtoupper($title));
        
        $this->assertFalse($channels === $newChannels);
            
        $this->assertSame(
            [],
            (new AvailableChannels([]))
                ->map(fn($title, $name) => strtoupper($title))
                ->titles()
        );
        
        $this->assertSame(
            ['SMS'],
            (new AvailableChannels(['sms']))
                ->map(fn($title, $name) => strtoupper($title))
                ->titles()
        );
    }
    
    public function testSortByNameMethod()
    {
        $channels = new AvailableChannels([]);
        $newChannels = $channels->sortByName();
        
        $this->assertFalse($channels === $newChannels);
        
        $this->assertSame(
            ['mail', 'sms', 'storage'],
            (new AvailableChannels(['sms', 'mail', 'storage']))->sortByName()->names()
        );
    }
    
    public function testSortByTitleMethod()
    {
        $channels = new AvailableChannels([]);
        $newChannels = $channels->sortByTitle();
        
        $this->assertFalse($channels === $newChannels);
        
        $this->assertSame(
            ['Mail', 'Sms', 'Storage'],
            (new AvailableChannels(['sms', 'mail', 'storage']))->sortByTitle()->titles()
        );
        
        $this->assertSame(
            ['Account', 'Mail', 'Sms'],
            (new AvailableChannels(['sms', 'mail', 'storage']))
                ->withTitle('storage', 'Account')
                ->sortByTitle()
                ->titles()
        );
    }
    
    public function testCountMethod()
    {
        $this->assertSame(0, (new AvailableChannels([]))->count());
        $this->assertSame(2, (new AvailableChannels(['sms', 'mail']))->count());
    }
    
    public function testGetIteratorMethod()
    {
        $channels = [];
        
        foreach(new AvailableChannels(['sms', 'mail']) as $name => $title) {
            $channels[$name] = $title;
        }
        
        $this->assertSame(['sms' => 'Sms', 'mail' => 'Mail'], $channels);
    }
}