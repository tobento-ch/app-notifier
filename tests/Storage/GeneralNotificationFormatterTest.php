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
use Tobento\App\Notifier\Storage\GeneralNotificationFormatter;
use Tobento\App\Notifier\Storage\Notification;
use Tobento\Service\Translation;
use Tobento\Service\Routing;
use Tobento\Service\Container\Container;

class GeneralNotificationFormatterTest extends TestCase
{
    public function testFormatWithoutTranslatorAndRouter()
    {
        $formatter = new GeneralNotificationFormatter();
        
        $n = new Notification(['data' => ['message' => 'Msg']]);
        
        $this->assertSame('Msg', $formatter->format($n)->message());
    }
    
    public function testFormatsWithTranslator()
    {
        $translator = new Translation\Translator(
            resources: new Translation\Resources(
                new Translation\Resource('*', 'de', [
                    'Hello World' => 'Hallo Welt',
                ]),
            ),
            modifiers: new Translation\Modifiers(
                new Translation\Modifier\ParameterReplacer(),
            ),
            missingTranslationHandler: new Translation\MissingTranslationHandler(),
            locale: 'de',
        );
        
        $formatter = new GeneralNotificationFormatter(translator: $translator);
        
        $n = new Notification(['data' => ['message' => 'Hello World']]);
        
        $this->assertSame('Hallo Welt', $formatter->format($n)->message());
    }
    
    public function testFormatsWithRouter()
    {
        $container = new Container();
        $router = new Routing\Router(
            new Routing\RequestData('GET', 'foo'),
            new Routing\UrlGenerator(
                'https://example.com',
                'a-random-32-character-secret-signature-key',
            ),
            new Routing\RouteFactory(),
            new Routing\RouteDispatcher($container, new Routing\Constrainer\Constrainer()),
            new Routing\RouteHandler($container),
            new Routing\MatchedRouteHandler($container),
            new Routing\RouteResponseParser(),
        );
        
        $formatter = new GeneralNotificationFormatter(router: $router);
        
        $n = new Notification(['data' => [
            'action_text' => 'View Order',
            'action_route' => 'orders.view',
            'action_route_parameters' => ['id' => 555],
        ]]);
        
        // No route exists, so no action is created:
        $this->assertSame(0, count($formatter->format($n)->actions()));
        
        $router->get('orders/{id}', 'Controller::method')->name('orders.view');
        
        $action = $formatter->format($n)->actions()[0] ?? null;
        
        $this->assertSame('View Order', $action?->text());
        $this->assertSame('https://example.com/orders/555', $action?->url());
    }
}