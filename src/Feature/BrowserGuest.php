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
use Tobento\App\Notifier\GuestResolverInterface;
use Tobento\App\Notifier\Formatting\Notification;
use Tobento\App\Notifier\ReadNotificationResolverInterface;
use Tobento\App\User\Exception\AuthorizationException;
use Tobento\App\User\UserInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Notifier\Browser\Channel;
use Tobento\Service\Notifier\ChannelsInterface;
use Tobento\Service\Notifier\GuestRecipient;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\View\ViewInterface;

class BrowserGuest extends Boot
{
    public const INFO = [
        'boot' => [
            'Browser route',
        ],
    ];
    
    public const BOOT = [
        Migration::class,
        
        // HTTP:
        \Tobento\App\Http\Boot\ErrorHandler::class,
        \Tobento\App\Http\Boot\Routing::class,
        \Tobento\App\Http\Boot\RequesterResponser::class,
        \Tobento\App\Encryption\Boot\Encryption::class,
        \Tobento\App\Http\Boot\Cookies::class,
        
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
     * @param bool $withAcl
     * @param bool $localizeRoute
     */
    public function __construct(
        protected array $browserChannels = ['browser'],
        protected null|string $autoRenderOnView = 'inc/head',
        protected bool $withAcl = true,
        protected bool $localizeRoute = false,
    ) {}
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param AppInterface $app
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
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
        
        $route = $router->post(
            uri: '{?locale}/notifications/browser-guest',
            handler: [$this, 'browser'],
        )->name('notifications.browser.guest')
         ->middleware(...$this->configureMiddlewares($app));
        
        if ($this->localizeRoute) {
            $app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        }
        
        $this->configureRoutes($router, $app);
        
        $app->on(ViewInterface::class, function(ViewInterface $view) use ($app): void {
            
            if ($this->autoRenderOnView) {
                $view->autoRender(view: 'notifier.browser.guest', on: $this->autoRenderOnView, apply: 'after');
            }
            
            $view->on('notifier.browser.guest', function(array $data, ViewInterface $view) use ($app): array {
                
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
                
                // If route is missing or disabled, skip rendering
                $pullUrl = (string)$view->routeUrl(name: 'notifications.browser.guest', throw: false);
                
                if ($pullUrl === '') {
                    return [];
                }
                
                $data['pullUrl'] = $pullUrl;
                
                $view->add(key: 'notifier.browser.guest', view: 'notifier/browser-guest');
                
                return $data;
            });
        });
    }
    
    /**
     * Browser action
     *
     * @param RequesterInterface $requester
     * @param GuestResolverInterface $guestResolver
     * @param ReadNotificationResolverInterface $readResolver
     * @param ChannelsInterface $channels
     * @param ClockInterface $clock,
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function browser(
        RequesterInterface $requester,
        GuestResolverInterface $guestResolver,
        ReadNotificationResolverInterface $readResolver,
        ChannelsInterface $channels,
        ClockInterface $clock,
        ResponserInterface $responser,
    ): ResponseInterface {
        $user = $requester->request()->getAttribute(UserInterface::class);
        $this->isAuthorized($user);
        
        $recipientId = $guestResolver->resolveId();

        // Load read notification IDs
        $readIds = $readResolver->getReadIds();
        $newReadIds = [];
        
        // Collecting notifiactions
        $messages = [];
        
        foreach ($this->browserChannels as $channelName) {
            $channel = $channels->get(name: $channelName);

            if (!$channel instanceof Channel) {
                continue;
            }

            // TARGETED NOTIFICATIONS (recipient_id = browser_guest_id)
            $targeted = iterator_to_array($channel->repository()->findAll(
                where: [
                    'recipient_id' => $recipientId,
                    'recipient_type' => GuestRecipient::class,
                    'read_at' => ['null'],
                ],
                orderBy: ['created_at' => 'desc'],
                limit: 5,
            ));

            // BROADCAST NOTIFICATIONS (recipient_id IS NULL)
            $broadcasted = iterator_to_array($channel->repository()->findAll(
                where: [
                    'recipient_id' => ['null'],
                    'recipient_type' => GuestRecipient::class,
                ],
                orderBy: ['created_at' => 'desc'],
                limit: 5,
            ));
            
            // Filter out broadcast notifications already read and expired
            $broadcasted = array_filter($broadcasted, function ($n) use ($readIds, $clock) {
                // Skip if already read
                if (in_array($n->get('id'), $readIds)) {
                    return false;
                }

                // Skip if expired
                $expires = $n->get('expires_at');
                if (!is_null($expires)) {
                    $expires = new \DateTimeImmutable($expires);

                    // Skip if expired
                    if ($expires < $clock->now()) {
                        return false;
                    }
                }

                return true;
            });

            // Track newly read broadcast IDs
            foreach ($broadcasted as $n) {
                $newReadIds[] = $n->get('id');
            }
            
            // Merge targeted + broadcast
            $all = array_merge($targeted, $broadcasted);

            foreach ($all as $notification) {
                $messages[] = $this->mapNotification($notification);
            }
            
            // Mark targeted notifications as read
            if (!empty($targeted)) {
                $ids = array_map(fn($n) => $n->get('id'), $targeted);

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
        
        // Persist updated broadcast read IDs
        if (!empty($newReadIds)) {
            $readResolver->storeReadIds(
                array_unique(array_merge($readIds, $newReadIds))
            );
        }

        return $responser->json(
            data: [
                'notifications' => $messages,
            ],
            code: 200,
        );
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
     * Determines if the user can receive browser notifications or not.
     *
     * @param null|UserInterface $user
     * @return bool
     */
    protected function userCanReceiveBrowserNotifications(null|UserInterface $user): bool
    {
        if (is_null($user)) {
            return true;
        }
        
        return $user->isAuthenticated() ? false : true;
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
            || $user->isAuthenticated()
        ) {
            throw new AuthorizationException();
        }
    }
}