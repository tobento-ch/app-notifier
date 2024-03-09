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

namespace Tobento\App\Notifier\Storage;

use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlException;

/**
 * GeneralNotificationFormatter
 */
class GeneralNotificationFormatter implements NotificationFormatterInterface
{
    /**
     * Create a new GeneralNotificationFormatter.
     *
     * @param null|TranslatorInterface $translator
     * @param null|RouterInterface $router
     */
    public function __construct(
        protected null|TranslatorInterface $translator = null,
        protected null|RouterInterface $router = null,
    ) {}

    /**
     * Returns the formatted notification.
     *
     * @param Notification $notification
     * @return Notification
     */
    public function format(Notification $notification): Notification
    {
        // Message:
        $message = $notification->get('data.message', '');
        $messageParams = $notification->get('data.message_parameters', []);
        
        if ($message && $this->translator) {
            $notification = $notification->withMessage($this->translator->trans($message, $messageParams));
        }
        
        // Action:
        if (is_null($this->router)) {
            return $notification;
        }
        
        $text = $notification->get('data.action_text', '');
        $route = $notification->get('data.action_route', '');
        $routeParams = $notification->get('data.action_route_parameters', []);
        
        if (empty($text) || empty($route)) {
            return $notification;
        }
        
        try {
            $url = $this->router->url($route, $routeParams);
        } catch (UrlException $e) {
            return $notification;
        }
        
        if ($this->translator) {
            $text = $this->translator->trans($text);
        }
        
        return $notification->withAddedAction(text: $text, url: $url);
    }
}