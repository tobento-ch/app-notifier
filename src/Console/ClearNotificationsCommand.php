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

namespace Tobento\App\Notifier\Console;

use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Notifier\ChannelInterface;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\Storage;
use Psr\Clock\ClockInterface;

class ClearNotificationsCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        notifications:clear | Clears notifications from channels.
        {--channel[] : The name(s) of the channel(s)}
        {--read-only : Clears only notifications that are read}
        {--unread-only : Clears only notifications that are unread}
        {--older-than-days=10 : The number of days after which to clear notifications}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param ChannelsInterface $channels
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(
        InteractorInterface $io,
        ChannelsInterface $channels,
        ClockInterface $clock,
    ): int {
        $channelNames = $io->option(name: 'channel');
        
        if (empty($channelNames)) {
            $channelNames = $channels->names();
        }
        
        $totalCleared = [];
        
        foreach($channelNames as $channelName) {
            if (! $channels->has($channelName)) {
                $io->info(sprintf('Channel %s not found to clear notifications', $channelName));
                continue;
            }
            
            $channel = $channels->get($channelName);
            
            $cleared = $this->clearNotificationsFromChannel($io, $channel, $clock);
            
            if (is_int($cleared)) {
                $totalCleared[] = $cleared;
                $io->success(sprintf('%d notifications cleared from %s channel', $cleared, $channelName));
            }
        }
        
        $io->success(sprintf('%d notifications cleared in total', array_sum($totalCleared)));
        
        return 0;
    }
    
    /**
     * Clears notifications from the given channel.
     *
     * @param InteractorInterface $io
     * @param ChannelInterface $channel
     * @param ClockInterface $clock
     * @return null|int Null if channel is not supported, otherwise the number of notifications cleared. 
     */
    protected function clearNotificationsFromChannel(
        InteractorInterface $io,
        ChannelInterface $channel,
        ClockInterface $clock,
    ): null|int {
        if (! $channel instanceof Storage\Channel) {
            return null;
        }
        
        $where = [];
        
        $date = $clock->now()->modify(sprintf('- %s days', (string)$io->option(name: 'older-than-days')));
        $date = $date ? $date->format('Y-m-d H:i:s') : $clock->now()->format('Y-m-d H:i:s');
        
        if ($io->option(name: 'read-only')) {
            $where['read_at'] = ['not null', '<' => $date];
        }
        
        if ($io->option(name: 'unread-only')) {
            $where['read_at'] = ['null'];
            $where['created_at'] = ['<' => $date];
        }
        
        if (empty($where)) {
            $where['created_at'] = ['<' => $date];
        }
        
        $notifications = $channel->repository()->delete(where: $where);

        return is_countable($notifications) ? count($notifications) : 0;
    }
}