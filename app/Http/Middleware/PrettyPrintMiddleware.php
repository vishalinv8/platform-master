<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

// https://www.aaronsaray.com/2017/laravel-pretty-print-middleware
class PrettyPrintMiddleware
{
		/**
		 * @var string the query parameter
		 */
 	  const QUERY_PARAMETER = 'pretty';

    /**
     * Apply JSON_PRETTY_PRINT if APP_DEBUG, or URL has ?pretty=true.
     * If APP_DEBUG, it can be disabled with ?pretty=false.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
				$response = $next($request);
				    
				if ($response instanceof JsonResponse) {
				  if ($request->query(self::QUERY_PARAMETER) == 'true' || 
				      env('app.debug', false) == true && $request->query(self::QUERY_PARAMETER) != 'false' ) {
				    $response->setEncodingOptions(JSON_PRETTY_PRINT);
				  }
				}
				    
				return $response;
    }
}
