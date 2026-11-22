<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerPromotion;
use App\Support\PartnerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Promosi mitra: draf disiapkan pengelola, terbit butuh izin content.publish. */
class PartnerPromotionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $viewer = PartnerAccess::viewer($user);

        $promotions = PartnerPromotion::query()
            ->with('partner:id,name,status')
            ->whereHas('partner', fn ($q) => $viewer ? $q : $q->where('status', 'active'))
            ->when(! $viewer, fn ($q) => $q->current())
            ->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(12)
            ->through(fn (PartnerPromotion $p) => [
                'id' => $p->id, 'title' => $p->title, 'description' => $p->description, 'terms' => $p->terms,
                'partner' => ['id' => $p->partner->id, 'name' => $p->partner->name],
                'period' => $p->starts_on->format('d/m/Y').' - '.$p->ends_on->format('d/m/Y'),
                'audience_label' => PartnerPromotion::AUDIENCES[$p->audience] ?? $p->audience,
                'phase' => $p->phase(),
            ]);

        return Inertia::render('Partners/Promotions', ['promotions' => $promotions, 'viewer' => $viewer]);
    }

    public function store(Request $request, Partner $partner): RedirectResponse
    {
        $partner->promotions()->create($this->validated($request) + ['status' => 'draft', 'created_by' => $request->user()->id]);

        return back()->with('success', 'Promosi disimpan sebagai draf.');
    }

    public function update(Request $request, PartnerPromotion $promotion): RedirectResponse
    {
        if ($promotion->status === 'published') {
            return back()->with('error', 'Tarik promosi ke draf dulu sebelum mengubahnya.');
        }

        $promotion->update($this->validated($request));

        return back()->with('success', 'Promosi diperbarui.');
    }

    public function status(Request $request, PartnerPromotion $promotion): RedirectResponse
    {
        $to = $request->validate(['status' => ['required', Rule::in(['draft', 'published'])]])['status'];

        if ($to === $promotion->status) {
            return back()->with('error', 'Status tidak berubah.');
        }

        if ($to === 'published') {
            if (! $promotion->partner->isActive()) {
                return back()->with('error', 'Mitra tidak aktif, promosi belum dapat diterbitkan.');
            }
            if ($promotion->ends_on->lt(today())) {
                return back()->with('error', 'Masa promosi sudah berakhir.');
            }
        }

        $promotion->update([
            'status'       => $to,
            'published_at' => $to === 'published' ? now() : $promotion->published_at,
            'published_by' => $to === 'published' ? $request->user()->id : $promotion->published_by,
        ]);

        return back()->with('success', $to === 'published' ? 'Promosi diterbitkan.' : 'Promosi ditarik ke draf.');
    }

    public function destroy(PartnerPromotion $promotion): RedirectResponse
    {
        if ($promotion->status === 'published') {
            return back()->with('error', 'Tarik promosi ke draf dulu sebelum menghapusnya.');
        }

        $promotion->delete();

        return back()->with('success', 'Promosi dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1500'],
            'terms'       => ['nullable', 'string', 'max:500'],
            'starts_on'   => ['required', 'date'],
            'ends_on'     => ['required', 'date', 'after_or_equal:starts_on'],
            'audience'    => ['required', Rule::in(array_keys(PartnerPromotion::AUDIENCES))],
        ], [
            'title.required'         => 'Judul promosi wajib diisi.',
            'starts_on.required'     => 'Tanggal mulai wajib diisi.',
            'ends_on.required'       => 'Tanggal berakhir wajib diisi.',
            'ends_on.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);
    }
}
