<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\Period;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MemberAssignmentController extends Controller
{
    public function store(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'period_id'   => ['required', 'integer', 'exists:periods,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'started_at'  => ['required', 'date'],
            'ended_at'    => ['nullable', 'date', 'after_or_equal:started_at'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ], [
            'period_id.required'       => 'Pilih periode kepengurusan.',
            'position_id.required'     => 'Pilih jabatan.',
            'started_at.required'      => 'Tanggal mulai wajib diisi.',
            'ended_at.after_or_equal'  => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        $period = Period::findOrFail($data['period_id']);
        $position = Position::findOrFail($data['position_id']);
        $open = empty($data['ended_at']);

        if (! $position->is_active) {
            $this->fail('position_id', 'Jabatan ini sudah tidak aktif.');
        }

        $divisionId = null;
        if ($position->scope === 'division') {
            if (empty($data['division_id'])) {
                $this->fail('division_id', 'Pilih divisi untuk jabatan tingkat divisi.');
            }
            $division = Division::findOrFail($data['division_id']);
            if (! $division->is_active) {
                $this->fail('division_id', 'Divisi ini sudah tidak aktif.');
            }
            $divisionId = $division->id;
        }

        if ($open && ! $period->is_current) {
            $this->fail('ended_at', 'Penugasan di periode yang tidak berjalan harus memiliki tanggal berakhir.');
        }
        if ($open && $member->status !== Member::ACTIVE) {
            $this->fail('position_id', 'Anggota yang tidak aktif tidak dapat diberi jabatan aktif.');
        }

        if ($open) {
            $same = MemberPosition::query()
                ->where('period_id', $period->id)
                ->where('position_id', $position->id)
                ->whereNull('ended_at')
                ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId), fn ($q) => $q->whereNull('division_id'));

            if ((clone $same)->where('member_id', $member->id)->exists()) {
                $this->fail('position_id', 'Anggota ini sudah memegang jabatan tersebut.');
            }
            if ($position->max_holders && (clone $same)->count() >= $position->max_holders) {
                $this->fail('position_id', "Jabatan {$position->name} sudah terisi (maksimal {$position->max_holders} orang untuk periode dan divisi ini).");
            }
        }

        $member->assignments()->create([
            'period_id'   => $period->id,
            'division_id' => $divisionId,
            'position_id' => $position->id,
            'started_at'  => $data['started_at'],
            'ended_at'    => $data['ended_at'] ?? null,
            'notes'       => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Jabatan ditambahkan.');
    }

    public function end(Request $request, MemberPosition $assignment): RedirectResponse
    {
        $data = $request->validate(['ended_at' => ['nullable', 'date']]);

        if ($assignment->ended_at) {
            return back()->with('error', 'Jabatan ini sudah berakhir.');
        }

        $end = isset($data['ended_at']) ? Carbon::parse($data['ended_at']) : today();
        if ($end->lt($assignment->started_at)) {
            $end = $assignment->started_at->copy();
        }

        $assignment->update(['ended_at' => $end]);

        return back()->with('success', 'Jabatan diakhiri.');
    }

    public function destroy(MemberPosition $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', 'Riwayat jabatan dihapus.');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
