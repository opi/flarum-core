<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Zend\Stratigility\MiddlewarePipe;
use Flarum\Http\Middleware\HandleErrors;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        if ($app->isInstalled()) {
            $path = parse_url($app->url('admin'), PHP_URL_PATH);
            $errorDir = __DIR__ . '/../../error';

            if ($app->isUpToDate()) {
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));
                $pipe->pipe($path, $app->make('Flarum\Admin\Middleware\RequireAdministrateAbility'));

                event(new ConfigureMiddleware($pipe, $path, $this));

                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.admin.routes')]));
                $pipe->pipe($path, new HandleErrors($errorDir, $app->inDebugMode()));
            } else {
                $app->register('Flarum\Update\UpdateServiceProvider');

                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.update.routes')]));
                $pipe->pipe($path, new HandleErrors($errorDir, true));
            }
        }

        return $pipe;
    }
}
