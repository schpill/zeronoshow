<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAuditLogResource;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Audit',
    description: 'Administrative audit log endpoints',
)]
class AdminAuditController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/audit-logs',
        tags: ['Admin Audit'],
        summary: 'List admin audit log entries',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'admin_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'target_type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated audit logs'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $query = AdminAuditLog::query()
            ->with('admin')
            ->orderByDesc('created_at');

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->string('admin_id')->toString());
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type')->toString());
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return AdminAuditLogResource::collection($query->paginate(50))->response();
    }
}
