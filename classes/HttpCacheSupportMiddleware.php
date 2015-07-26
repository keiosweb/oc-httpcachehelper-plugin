<?php
/**
 * Created by PhpStorm.
 * User: Łukasz Biały
 * URL: http://keios.eu
 * Date: 7/18/15
 * Time: 3:20 AM
 */

namespace Keios\HttpCacheSupport\Classes;

use Closure;

/**
 * Class HttpCacheSupportMiddleware
 * @package Keios\HttpCacheSupport
 */
class HttpCacheSupportMiddleware
{

    /**
     * @var bool
     */
    private $addCachingHeader = false;

    /**
     * @return null
     */
    public function addCachingHeader()
    {
        $this->addCachingHeader = true;
    }

    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return \Illuminate\Http\Response $response
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var \Illuminate\Http\Response $response
         */
        $response = $next($request);

        /**
         * Never cache AJAX responses
         */
        if ($request->ajax()) {
            return $response;
        }

        /**
         * Add header for HTTP cache if request is reading (GET, HEAD, OPTION)
         * and current page is defined as public
         */
        if ($this->addCachingHeader && in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            $response->header('X-Cacheable-Page', 'true');
        }

        return $response;
    }
}