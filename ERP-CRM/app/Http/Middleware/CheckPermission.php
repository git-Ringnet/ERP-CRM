<?php

namespace App\Http\Middleware;

use App\Services\AuditServiceInterface;
use App\Services\PermissionServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Create a new middleware instance.
     *
     * @param PermissionServiceInterface $permissionService
     * @param AuditServiceInterface $auditService
     */
    public function __construct(
        private PermissionServiceInterface $permissionService,
        private AuditServiceInterface $auditService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permission The required permission slug
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            abort(401, 'Unauthenticated.');
        }

        $userId = auth()->id();

        // Check if user has the required permission
        if (!$this->permissionService->checkPermission($userId, $permission)) {
            // Log unauthorized access attempt
            $this->auditService->logUnauthorizedAccess(
                $userId,
                $request->path()
            );

            // Return 403 Forbidden
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
