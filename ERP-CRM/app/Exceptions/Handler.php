<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // Handle AuthorizationException
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->handleAuthorizationException($request, $e);
        }
        
        // Handle HttpException with 403 status
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 403) {
            return $this->handleForbiddenException($request, $e);
        }
        
        return parent::render($request, $e);
    }

    /**
     * Handle authorization exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\Access\AuthorizationException $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleAuthorizationException($request, \Illuminate\Auth\Access\AuthorizationException $e)
    {
        $message = $this->getAuthorizationMessage($e);
        
        if ($request->expectsJson()) {
            return $this->jsonUnauthorizedResponse($message, $e);
        }
        
        return $this->webUnauthorizedResponse($message);
    }

    /**
     * Handle forbidden HTTP exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpKernel\Exception\HttpException $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleForbiddenException($request, \Symfony\Component\HttpKernel\Exception\HttpException $e)
    {
        $message = $e->getMessage() ?: 'Bạn không có quyền truy cập chức năng này.';
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Forbidden'
            ], 403);
        }
        
        return $this->webUnauthorizedResponse($message);
    }

    /**
     * Get authorization message from exception.
     *
     * @param \Illuminate\Auth\Access\AuthorizationException $e
     * @return string
     */
    protected function getAuthorizationMessage(\Illuminate\Auth\Access\AuthorizationException $e): string
    {
        $defaultMessage = 'Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ quản trị viên để được cấp quyền.';
        
        // Use custom message if provided
        if ($e->getMessage() && $e->getMessage() !== 'This action is unauthorized.') {
            return $e->getMessage();
        }
        
        return $defaultMessage;
    }

    /**
     * Return JSON response for unauthorized API request.
     *
     * @param string $message
     * @param \Illuminate\Auth\Access\AuthorizationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonUnauthorizedResponse(string $message, \Illuminate\Auth\Access\AuthorizationException $e)
    {
        $response = [
            'message' => $message,
            'error' => 'Unauthorized'
        ];
        
        // Include permission info if available
        if (method_exists($e, 'ability') && $e->ability()) {
            $response['required_permission'] = $e->ability();
        }
        
        return response()->json($response, 403);
    }

    /**
     * Return redirect response for unauthorized web request.
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function webUnauthorizedResponse(string $message)
    {
        // Flash error message
        session()->flash('error', $message);
        
        // Safe redirect
        $previousUrl = url()->previous();
        $currentUrl = url()->current();
        
        // Prevent redirect loop
        if ($previousUrl === $currentUrl) {
            return redirect()->route('dashboard');
        }
        
        // Redirect back or to dashboard
        return redirect($previousUrl ?: route('dashboard'));
    }
}
