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

namespace Tobento\App\Notifier\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\App\Notifier\Console\ClearNotificationsCommand;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Notifier\Storage;
use Tobento\Service\Notifier\Channels;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\Storage\StorageRepository;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Clock\FrozenClock;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

class ClearNotificationsCommandTest extends TestCase
{
    protected function createStorageChannel(string $name = 'storage')
    {
        return new Storage\Channel(
            name: $name,
            repository: new StorageRepository(
                storage: new  InMemoryStorage(items: []),
                table: 'notifications',
            ),
            container: new Container(),
        );
    }
    
    public function testClearsAllNotificationsShowsZeroNumbersIfNoneCleared()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-08-21 15:00')));
        
        $channels = new Channels(
            $this->createStorageChannel(name: 'foo'),
            $this->createStorageChannel(name: 'bar'),
        );
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(command: ClearNotificationsCommand::class))
            ->expectsOutput('0 notifications cleared from foo channel')
            ->expectsOutput('0 notifications cleared from bar channel')
            ->expectsOutput('0 notifications cleared in total')
            ->expectsExitCode(0)
            ->execute($container);
    }
    
    public function testClearsAllNotificationsShowsTheClearedNumbers()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-08-21 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $barChannel = $this->createStorageChannel(name: 'bar');
        $barChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel, $barChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(command: ClearNotificationsCommand::class))
            ->expectsOutput('2 notifications cleared from foo channel')
            ->expectsOutput('1 notifications cleared from bar channel')
            ->expectsOutput('3 notifications cleared in total')
            ->expectsExitCode(0)
            ->execute($container);
    }
    
    public function testClearsAllNotificationsWithDays()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-07-25 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-23 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-23 15:00',
            'created_at' => '2024-07-23 15:00',
        ]);
        
        $channels = new Channels($fooChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--older-than-days' => 3,
            ],
        ))
        ->expectsOutput('2 notifications cleared from foo channel')
        ->expectsOutput('2 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearNotificationsFromSpecificChannels()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-08-21 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $barChannel = $this->createStorageChannel(name: 'bar');
        $barChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel, $barChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--channel' => ['bar'],
            ],
        ))
        ->doesntExpectOutput('1 notifications cleared from foo channel')
        ->expectsOutput('1 notifications cleared from bar channel')
        ->expectsOutput('1 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearsReadOnlyNotifications()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-08-21 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--read-only' => null,
            ],
        ))
        ->expectsOutput('2 notifications cleared from foo channel')
        ->expectsOutput('2 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearsReadOnlyNotificationsWithDaysOlderThan()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-07-25 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-21 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-24 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-21 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--read-only' => null,
                '--older-than-days' => 3,
            ],
        ))
        ->expectsOutput('2 notifications cleared from foo channel')
        ->expectsOutput('2 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearsUnreadOnlyNotifications()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-08-21 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--unread-only' => null,
            ],
        ))
        ->expectsOutput('1 notifications cleared from foo channel')
        ->expectsOutput('1 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testClearsUnreadOnlyNotificationsWithDaysOlderThan()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock(new DateTimeImmutable('2024-07-25 15:00')));
        
        $fooChannel = $this->createStorageChannel(name: 'foo');
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => null,
            'created_at' => '2024-07-23 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        $fooChannel->repository()->create([
            'name' => 'notif',
            'recipient_id' => 1,
            'recipient_type' => 'users',
            'read_at' => '2024-07-22 15:00',
            'created_at' => '2024-07-21 15:00',
        ]);
        
        $channels = new Channels($fooChannel);
        
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--unread-only' => null,
                '--older-than-days' => 3,
            ],
        ))
        ->expectsOutput('1 notifications cleared from foo channel')
        ->expectsOutput('1 notifications cleared in total')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testChannelNotFound()
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock());
        $channels = new Channels();
        $container->set(ChannelsInterface::class, $channels);
        
        (new TestCommand(
            command: ClearNotificationsCommand::class,
            input: [
                '--channel' => ['bar'],
            ],
        ))
        ->expectsOutput('Channel bar not found to clear notifications')
        ->expectsExitCode(0)
        ->execute($container);
    }
}