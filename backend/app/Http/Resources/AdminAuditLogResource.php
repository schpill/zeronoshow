<?php

namespace App\Http\Resources;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AdminAuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AdminAuditLog $auditLog */
        $auditLog = $this->resource;

        return [
            'id' => $auditLog->id,
            'admin_id' => $auditLog->admin_id,
            'admin_name' => data_get($auditLog, 'admin.name'),
            'action' => $auditLog->action,
            'target_type' => $auditLog->target_type,
            'target_id' => $auditLog->target_id,
            'payload' => $auditLog->payload,
            'ip_address' => $auditLog->ip_address,
            'created_at' => ($auditLog->created_at instanceof Carbon ? $auditLog->created_at : Carbon::parse($auditLog->created_at))->toIso8601String(),
        ];
    }
}
