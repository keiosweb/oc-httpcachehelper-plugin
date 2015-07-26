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
        if ($this->app->runningInConsole()) {
            return;
        }

        /**
         * @var \Illuminate\Http\Request $request
         */
        $request = $this->app->make('request');

        /**
         * @var \Illuminate\Contracts\Config\Repository $config
         */
        $config = $this->app->make('config');

        /**
         * @var \Illuminate\Contracts\Events\Dispatcher $eventDispatcher
         */
        $eventDispatcher = $this->app->make('events');

        $backendUri = $config->get('cms.backendUri');

        if ($request && $request->is($backendUri.'*')) {

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
                'cms.template.save',
                function ($controller, $template, $type) {

                    if ($type === 'page') {

                    }

                    if ($type === 'asset') {

                    }

                }
            );

            return;
        }

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
