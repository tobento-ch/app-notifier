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

use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Notifier\Formatting\Notification;
use Tobento\App\User\Exception\AuthorizationException;
use Tobento\App\User\UserInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Notifier\Browser\Channel;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class BrowserStream extends Boot
{
    public const INFO = [
        'boot' => [
            'Browser stream route',
        ],
    ];
    
    public const BOOT = [
        Migration::class,
        
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
    ];
    
    /**
     * Create a new instance.
     *
     * @param array<int, string> $browserChannels
     * @param bool $withAcl
     * @param bool $localizeRoute
     */
    public function __construct(
        protected array $browserChannels = ['browser'],
        protected bool $withAcl = true,
        protected bool $localizeRoute = false,
    ) {}
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param AppInterface $app
     * @return void
     */
    public function boot(Migration $migration, AppInterface $app): void
    {
        $acl = $app->get(AclInterface::class);
        $acl->rule('notifications.browser')->description('User can receive browser notifications.');
        
        if ($this->withAcl === false) {
            $acl->addPermissions(['notifications.browser']);
        }

        // Routes:
        $router = $app->get(RouterInterface::class);
        
        $route = $router->get(
            uri: '{?locale}/notifications/browser-stream',
            handler: [$this, 'browser'],
        )->name('notifications.browser.stream')
         ->middleware(...$this->configureMiddlewares($app));
        
        if ($this->localizeRoute) {
            $app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        }
        
        $this->configureRoutes($router, $app);
    }
    
    /**
     * Browser action
     *
     * @param RequesterInterface $requester
     * @param ChannelsInterface $channels
     * @param ClockInterface $clock,
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function browser(
        RequesterInterface $requester,
        ChannelsInterface $channels,
        ClockInterface $clock,
        ResponserInterface $responser,
    ): ResponseInterface {
        $user = $requester->request()->getAttribute(UserInterface::class);
        $this->isAuthorized($user);
        
        // Collecting notifiactions
        $messages = [];
        
        foreach($this->browserChannels as $channelName) {
            $channel = $channels->get(name: $channelName);
            
            if (!$channel instanceof Channel) {
                continue;
            }

            $notifications = $channel->repository()->findAll(
                where: [
                    'recipient_id' => $user->id(),
                    'read_at' => ['null'],
                ],
                orderBy: ['created_at' => 'desc'],
                limit: 5,
            );
                        
            $ids = [];
            
            foreach($notifications as $notificaction) {
                $ids[] = $notificaction->get('id');
                $messages[] = $this->mapNotification($notificaction);
            }
            
            if (!empty($ids)) {
                $channel->repository()->update(
                    where: [
                        'id' => ['in' => $ids],
                    ],
                    attributes: [
                        'read_at' => $clock->now(),
                    ],
                );                
            }
        }

        return $responser
            ->write(
                data: sprintf("data: %s\n\n", json_encode(['notifications' => $messages])),
                code: 200,
            )
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive');
    }
    
    /**
     * Maps a fully formatted Notification object to the array structure
     * expected by the JS‑Notifier frontend component.
     *
     * The returned array must follow the parameter format defined at:
     * https://github.com/tobento-ch/js-notifier#parameters
     *
     * @param Notification $notification  The formatted notification instance.
     * @return array  The notification mapped to JS‑Notifier parameters.
     */
    protected function mapNotification(Notification $notification): array
    {
        $mapped = [
            'status' => $notification->get('data.status', 'success'),
            'text' => $notification->message(),
            'autotimeout' => $notification->has('data.autotimeout')
                ? $notification->get('data.autotimeout')
                : 5000,
            'showCloseButton' => $notification->get('data.showCloseButton', true),
        ];
        
        if ($title = $notification->get('data.title', '')) {
            $mapped['title'] = $title;
        }
        
        if ($action = $notification->actions()[0] ?? null) {
            $classes = $action->attributes()['class'] ?? '';
            
            $mapped['action'] = [
                'title' => $action->text(),
                'url' => $action->url(),
                'classes' => $classes !== '' ? explode(' ', $classes) : [],
            ];
        }
        
        return $mapped;
    }
    
    /**
     * Configure middlewares for the route(s).
     *
     * @param AppInterface $app
     * @return array
     */
    protected function configureMiddlewares(AppInterface $app): array
    {
        return [
            ['can', 'permission' => 'notifications.browser'],
        ];
    }
    
    /**
     * Configure routes
     *
     * @param RouterInterface $router
     * @param AppInterface $app
     * @return void
     */
    protected function configureRoutes(RouterInterface $router, AppInterface $app): void
    {
        // $router->getRoute(name: 'notifications.browser')->middleware();
    }
    
    /**
     * Determines if the user is authorized to access the notifications.
     *
     * @param mixed $user
     * @return void
     * @throws AuthorizationException
     */
    protected function isAuthorized(mixed $user): void
    {
        if (
            !$user instanceof UserInterface
            || !$user->isAuthenticated()
        ) {
            throw new AuthorizationException();
        }
    }
}