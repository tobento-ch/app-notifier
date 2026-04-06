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
 
namespace Tobento\App\Notifier\Feature;

use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\User\UserInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\View\ViewInterface;

class BrowserView extends Boot
{
    public const INFO = [
        'boot' => [
            'Browser view',
        ],
    ];
    
    public const BOOT = [
        // HTTP:
        \Tobento\App\Http\Boot\ErrorHandler::class,
        \Tobento\App\Http\Boot\Routing::class,
        \Tobento\App\Http\Boot\RequesterResponser::class,
        
        // I18n:
        \Tobento\App\Language\Boot\Language::class,
        \Tobento\App\Translation\Boot\Translation::class,
        
        // USER:
        \Tobento\App\User\Boot\HttpUserErrorHandler::class,
        \Tobento\App\User\Boot\Acl::class,
        \Tobento\App\User\Boot\User::class,
        
        // VIEW:
        \Tobento\App\View\Boot\View::class,
    ];
    
    /**
     * Create a new instance.
     *
     * @param array<int, string> $browserChannels
     * @param null|string $autoRenderOnView
     */
    public function __construct(
        protected array $browserChannels = ['browser'],
        protected null|string $autoRenderOnView = 'inc/head',
    ) {}
    
    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function boot(AppInterface $app): void
    {
        $app->on(ViewInterface::class, function(ViewInterface $view) use ($app): void {
            
            if ($this->autoRenderOnView) {
                $view->autoRender(view: 'notifier.browser', on: $this->autoRenderOnView, apply: 'after');
            }
            
            $view->on('notifier.browser', function(array $data, ViewInterface $view) use ($app): array {
                
                $request = $app->get(ServerRequestInterface::class);
                $user = $request->getAttribute(UserInterface::class);
                $acl = $app->get(AclInterface::class);
                
                // Prevent rendering entirely if not allowed
                if (
                    $acl->cant('notifications.browser')
                    || !$this->userCanReceiveBrowserNotifications($user)
                ) {
                    return [];
                }

                $view->add(key: 'notifier.browser', view: 'notifier/browser');
                
                // Resolve URLs safely
                $pullUrl = (string)$view->routeUrl(name: 'notifications.browser', throw: false);
                $streamUrl = (string)$view->routeUrl(name: 'notifications.browser.stream', throw: false);
                
                $data['pullUrl'] = $pullUrl;
                $data['streamUrl'] = $streamUrl;
                
                return $data;
            });
        });
    }
    
    /**
     * Determines if the user can receive browser notifications or not.
     *
     * @param null|UserInterface $user
     * @return bool
     */
    protected function userCanReceiveBrowserNotifications(null|UserInterface $user): bool
    {
        if (is_null($user)) {
            return false;
        }
        
        if (!$user->isAuthenticated()) {
            return false;
        }
        
        $channels = $user->setting('preferred_notification_channels', []);
        
        if (empty($channels)) {
            return true;
        }
        
        return !empty(array_intersect($channels, $this->browserChannels));
    }
}