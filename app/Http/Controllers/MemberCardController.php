<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class MemberCardController extends Controller
{
    /** /kartu-saya -> kartu milik akun yang sedang login. */
    public function mine(Request $request): RedirectResponse
    {
        abort_unless($request->user()->member_id, 404);

        return to_route('members.card', $request->user()->member_id);
    }

    public function show(Request $request, Member $member): Response
    {
        $this->authorizeCard($request, $member);
        $member->load(['activeAssignments.position', 'activeAssignments.division']);

        return Inertia::render('Members/Card', [
            'member' => [
                'id'            => $member->id,
                'name'          => $member->name,
                'nomor_anggota' => $member->nomor_anggota,
                'photo_url'     => $member->photo_url,
                'status'        => $member->status,
                'status_label'  => $member->statusLabel(),
                'joined_year'   => $member->joined_at->format('Y'),
                'positions'     => $member->positionLabels(),
            ],
            'qr_url'     => route('members.qr', $member, absolute: false),
            'verify_url' => route('verify', $member->qr_token),
            'is_own'     => $request->user()->member_id === $member->id,
        ]);
    }

    public function qr(Request $request, Member $member): HttpResponse
    {
        $this->authorizeCard($request, $member);

        return response(Qr::svg(route('verify', $member->qr_token)), 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function authorizeCard(Request $request, Member $member): void
    {
        $user = $request->user();
        abort_unless($user->can('members.view') || $user->member_id === $member->id, 403);
    }
}
