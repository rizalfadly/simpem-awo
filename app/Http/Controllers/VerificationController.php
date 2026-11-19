<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman publik tujuan QR Code kartu. Hanya menampilkan data minimum. */
class VerificationController extends Controller
{
    public function __invoke(string $token): Response
    {
        $member = strlen($token) === 40
            ? Member::with(['activeAssignments.position', 'activeAssignments.division'])->where('qr_token', $token)->first()
            : null;

        if (! $member) {
            return Inertia::render('Public/Verify', ['valid' => false, 'member' => null]);
        }

        return Inertia::render('Public/Verify', [
            'valid'  => true,
            'member' => [
                'name'          => $member->name,
                'nomor_anggota' => $member->nomor_anggota,
                'photo_url'     => $member->photo_url,
                'status'        => $member->status,
                'status_label'  => $member->statusLabel(),
                'joined_year'   => $member->joined_at->format('Y'),
                'positions'     => $member->positionLabels(),
            ],
        ]);
    }
}
