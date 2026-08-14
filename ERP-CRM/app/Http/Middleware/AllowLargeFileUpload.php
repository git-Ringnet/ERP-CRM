<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowLargeFileUpload
{
    /**
     * Handle an incoming request to allow large file uploads.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Increase PHP limits for this request
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '600');
        ini_set('post_max_size', '200M');
        ini_set('upload_max_filesize', '200M');
        set_time_limit(600);
        
        return $next($request);
    }
}
