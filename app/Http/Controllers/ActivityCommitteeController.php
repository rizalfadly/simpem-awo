<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCommitteeMember;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityCommitteeController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; panitia tidak dapat diubah.');
        }

        $data = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->where('status', Member::ACTIVE)->whereNull('deleted_at')],
            'role'      => ['required', Rule::in(array_keys(ActivityCommitteeMember::ROLES))],
            'section'   => ['nullable', 'string', 'max:60', 'required_if:role,coordinator'],
            'notes'     => ['nullable', 'string', 'max:255'],
        ], [
            'member_id.required' => 'Pilih anggota.',
            'member_id.exists'   => 'Hanya anggota berstatus Aktif yang dapat menjadi panitia.',
            'section.required_if' => 'Koordinator wajib memiliki nama seksi.',
        ]);

        if ($activity->committee()->where('member_id', $data['member_id'])->exists()) {
            throw ValidationException::withMessages(['member_id' => 'Anggota ini sudah menjadi panitia kegiatan ini.']);
        }
        $this->ensureSingleLeader($activity, $data['role']);

        $activity->committee()->create($data);

        return back()->with('success', 'Panitia ditambahkan.');
    }

    public function update(Request $request, ActivityCommitteeMember $committeeMember): RedirectResponse
    {
        $activity = $committeeMember->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; panitia tidak dapat diubah.');
        }

        $data = $request->validate([
            'role'    => ['required', Rule::in(array_keys(ActivityCommitteeMember::ROLES))],
            'section' => ['nullable', 'string', 'max:60', 'required_if:role,coordinator'],
            'notes'   => ['nullable', 'string', 'max:255'],
        ], ['section.required_if' => 'Koordinator wajib memiliki nama seksi.']);

        $this->ensureSingleLeader($activity, $data['role'], $committeeMember->id);

        $committeeMember->update($data);

        return back()->with('success', 'Panitia diperbarui.');
    }

    public function destroy(Request $request, ActivityCommitteeMember $committeeMember): RedirectResponse
    {
        $activity = $committeeMember->activity;
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan sudah ditutup; panitia tidak dapat diubah.');
        }

        $committeeMember->delete();

        return back()->with('success', 'Panitia dihapus.');
    }

    private function ensureSingleLeader(Activity $activity, string $role, ?int $exceptId = null): void
    {
        if ($role !== 'leader') {
            return;
        }

        $exists = $activity->committee()->where('role', 'leader')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();

        if ($exists) {
            throw ValidationException::withMessages(['role' => 'Ketua Pelaksana sudah ada. Ubah dulu peran yang lama.']);
        }
    }
}
