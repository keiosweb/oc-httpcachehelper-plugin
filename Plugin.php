<?php namespace Keios\HttpCacheSupport;

use Cms\Classes\CmsController;
use Cms\Classes\Page;
use Keios\HttpCacheSupport\Classes\HttpCacheSupportMiddleware;
use System\Classes\PluginBase;

/**
 * HttpCacheSupport Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'HttpCache Support', // todo translation
            'description' => 'Support for HTTP caches like Varnish', // todo translation
            'author'      => 'Keios',
            'icon'        => 'icon-bolt'
        ];
    }

    public function register()
    {
        $this->app->singleton(
            'Keios\HttpCacheSupport\Classes\HttpCacheSupportMiddleware',
            function () {
                return new HttpCacheSupportMiddleware();
            }
        );

        CmsController::extend(
            function (CmsController $controller) {
                $controller->middleware('Keios\HttpCacheSupport\Classes\HttpCacheSupportMiddleware');
            }
        );

        $eventDispatcher = $this->app->make('events');

        $eventDispatcher->listen(
            'backend.form.extendFields',
            function ($widget) {
                if (!$widget->getController() instanceof \Cms\Controllers\Index) {
                    return;
                }
                if (!$widget->model instanceof \Cms\Classes\Page) {
                    return;
                }

                $widget->addFields(
                    [
                        'settings[is_public]' => [
                            'label' => 'This page is public and can be cached', // todo translation
                            'tab'   => 'Caching', // todo translation
                            'type'  => 'checkbox',
                        ]
                    ],
                    'primary'
                );
            }
        );

        $eventDispatcher->listen(
            'cms.page.display',
            function ($controller, $url, Page $page, $response) {
                $page->addVisible('is_public');

                if ($page->is_public) {
                    $this->app
                        ->make('Keios\HttpCacheSupport\Classes\HttpCacheSupportMiddleware')
                        ->addCachingHeader();
                }
            }
        );
    }

}
