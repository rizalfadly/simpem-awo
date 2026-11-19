<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q'     => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:50'],
            'from'  => ['nullable', 'date'],
            'to'    => ['nullable', 'date'],
        ]);

        $logs = AuditLog::query()
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('description', 'like', "%{$s}%")
                ->orWhere('user_label', 'like', "%{$s}%")))
            ->when($filters['event'] ?? null, fn ($q, $e) => $q->where('event', $e))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $l) => [
                'id'          => $l->id,
                'created_at'  => $l->created_at?->format('d/m/Y H:i:s'),
                'user_label'  => $l->user_label ?? 'Sistem',
                'event'       => $l->event,
                'description' => $l->description,
                'subject'     => $l->auditable_type ? class_basename($l->auditable_type).' #'.$l->auditable_id : null,
                'ip_address'  => $l->ip_address,
                'old_values'  => $l->old_values,
                'new_values'  => $l->new_values,
            ]);

        return Inertia::render('AuditLogs/Index', [
            'logs'    => $logs,
            'filters' => [
                'q'     => $filters['q'] ?? '',
                'event' => $filters['event'] ?? '',
                'from'  => $filters['from'] ?? '',
                'to'    => $filters['to'] ?? '',
            ],
            'events' => Audit::EVENTS,
        ]);
    }
}
