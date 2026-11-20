<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReport;
use App\Models\AuditLog;
use App\Models\FinanceDispute;
use App\Models\FinanceReport;
use App\Models\Member;
use App\Models\NewsPost;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Widget hanya dikirim jika pengguna punya hak aksesnya.
        $stats = [];
        if ($user->can('members.view')) {
            $stats[] = ['label' => 'Anggota aktif', 'value' => Member::where('status', Member::ACTIVE)->count()];
            $stats[] = ['label' => 'Alumni', 'value' => Member::where('status', 'alumni')->count()];
        }
        if ($user->can('users.view')) {
            $stats[] = ['label' => 'Akun aktif', 'value' => User::where('is_active', true)->count()];
        }
        if ($user->can('roles.view')) {
            $stats[] = ['label' => 'Role', 'value' => Role::count()];
        }

        if ($user->can('activities.approve')) {
            $stats[] = [
                'label' => 'Menunggu persetujuan kegiatan',
                'value' => Activity::where('status', 'proposed')->count() + ActivityReport::where('status', 'submitted')->count(),
            ];
        }

        $upcoming = Activity::query()
            ->where('status', 'approved')
            ->where('ends_at', '>=', now())
            ->with('division:id,name')
            ->orderBy('starts_at')
            ->limit(4)
            ->get()
            ->map(fn (Activity $a) => [
                'id'       => $a->id,
                'title'    => $a->title,
                'when'     => $a->starts_at->format('d/m/Y H:i'),
                'location' => $a->location,
                'division' => $a->division?->name,
            ]);

        $latest = FinanceReport::where('status', 'published')->orderByDesc('year')->orderByDesc('month')->first();
        if ($latest) {
            $closing = $latest->totals()['closing'];
            $stats[] = ['label' => 'Saldo kas, '.$latest->label(), 'value' => ($closing < 0 ? '-' : '').'Rp '.number_format(abs($closing), 0, ',', '.')];
        }

        $pending = 0;
        if ($user->can('finance.record')) {
            $pending += FinanceReport::where('status', 'revision')->count() + FinanceDispute::where('status', 'open')->count();
        }
        if ($user->can('finance.review')) {
            $pending += FinanceReport::where('status', 'submitted')->count() + FinanceDispute::where('status', 'clarified')->count();
        }
        if ($user->can('finance.approve')) {
            $pending += FinanceReport::where('status', 'reviewed')->count() + FinanceDispute::where('status', 'reviewed')->count();
        }
        if ($user->can('finance.publish')) {
            $pending += FinanceReport::where('status', 'approved')->count();
        }
        if ($pending > 0) {
            $stats[] = ['label' => 'Menunggu tindakan keuangan', 'value' => $pending];
        }

        $news = NewsPost::where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'excerpt', 'published_at'])
            ->map(fn (NewsPost $n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'excerpt'      => $n->excerpt,
                'published_at' => $n->published_at?->format('d/m/Y'),
            ]);

        $recent = $user->can('audit.view')
            ? AuditLog::latest('id')->limit(8)->get()->map(fn (AuditLog $l) => [
                'id'          => $l->id,
                'description' => $l->description,
                'user_label'  => $l->user_label ?? 'Sistem',
                'created_at'  => $l->created_at?->format('d/m/Y H:i'),
            ])
            : null;

        $access = collect(PermissionCatalog::groups())
            ->map(fn ($g) => [
                'label' => $g['label'],
                'items' => collect($g['items'])->filter(fn ($label, $name) => $user->can($name))->values()->all(),
            ])
            ->filter(fn ($g) => count($g['items']) > 0)
            ->values();

        return Inertia::render('Dashboard', [
            'stats'         => $stats,
            'recent'        => $recent,
            'upcoming'      => $upcoming,
            'news'          => $news,
            'access'        => $access,
            'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
        ]);
    }
}
