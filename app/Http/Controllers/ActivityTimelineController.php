<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityTimeline;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityTimelineController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; timeline tidak dapat diubah.');
        }

        $activity->timelines()->create($this->validated($request));

        return back()->with('success', 'Agenda ditambahkan.');
    }

    public function update(Request $request, ActivityTimeline $timeline): RedirectResponse
    {
        $activity = $timeline->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; timeline tidak dapat diubah.');
        }

        $timeline->update($this->validated($request));

        return back()->with('success', 'Agenda diperbarui.');
    }

    /** Pengelola kegiatan, atau PIC agenda itu sendiri, boleh memperbarui status pengerjaan. */
    public function status(Request $request, ActivityTimeline $timeline): RedirectResponse
    {
        $activity = $timeline->activity;
        $user = $request->user();

        $allowed = $user->can('manage', $activity) || ($user->member_id && $timeline->pic_member_id === $user->member_id);
        abort_unless($allowed, 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; status tidak dapat diubah.');
        }

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ActivityTimeline::STATUSES))]]);
        $timeline->update($data);

        return back()->with('success', 'Status agenda diperbarui.');
    }

    public function destroy(Request $request, ActivityTimeline $timeline): RedirectResponse
    {
        $activity = $timeline->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; timeline tidak dapat diubah.');
        }

        $timeline->delete();

        return back()->with('success', 'Agenda dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'kind'          => ['required', Rule::in(array_keys(ActivityTimeline::KINDS))],
            'title'         => ['required', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:500'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'pic_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')->where('status', Member::ACTIVE)->whereNull('deleted_at')],
            'status'        => ['required', Rule::in(array_keys(ActivityTimeline::STATUSES))],
        ], [
            'title.required'         => 'Judul agenda wajib diisi.',
            'starts_at.required'     => 'Waktu mulai wajib diisi.',
            'ends_at.after_or_equal' => 'Waktu selesai tidak boleh sebelum waktu mulai.',
            'pic_member_id.exists'   => 'PIC harus anggota berstatus Aktif.',
        ]);
    }
}
