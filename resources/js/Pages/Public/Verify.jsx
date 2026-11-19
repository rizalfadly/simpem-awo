import { Head } from '@inertiajs/react';
import Avatar from '@/Components/Avatar';
import { Badge } from '@/Components/ui';

export default function Verify({ valid, member }) {
    const active = member?.status === 'active';
    const tone = member?.status === 'active' ? 'green' : member?.status === 'inactive' ? 'amber' : 'neutral';

    return (
        <>
            <Head title="Verifikasi Kartu Anggota" />
            <div className="flex min-h-screen items-center justify-center px-5 py-10">
                <div className="w-full max-w-sm">
                    <div className="mb-6 text-center">
                        <div className="text-2xl font-extrabold tracking-tight">SIMPEM</div>
                        <div className="text-xs font-medium text-[#5B6779]">Arek Wetan Organizer</div>
                    </div>

                    {!valid ? (
                        <div className="rounded-lg border border-[#E7B9BC] bg-white p-6 text-center" role="alert">
                            <h1 className="text-lg font-extrabold text-[#8F1B24]">Kartu tidak ditemukan</h1>
                            <p className="mt-2 text-sm text-[#5B6779]">
                                QR Code ini tidak terdaftar atau sudah tidak berlaku. Mintalah pemilik kartu menunjukkan kartu terbaru.
                            </p>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-[#DDE2E9] bg-white p-6">
                            <div className="flex items-center gap-4">
                                <Avatar name={member.name} src={member.photo_url} className="h-20 w-20 rounded-lg" textClass="text-2xl" />
                                <div className="min-w-0">
                                    <h1 className="text-lg font-extrabold leading-tight">{member.name}</h1>
                                    <p className="mt-0.5 text-sm font-semibold tabular-nums text-[#5B6779]">{member.nomor_anggota}</p>
                                </div>
                            </div>

                            <div className="mt-5 flex items-center justify-between border-t border-[#EEF1F5] pt-4">
                                <span className="text-sm text-[#5B6779]">Status keanggotaan</span>
                                <Badge tone={tone}>{member.status_label}</Badge>
                            </div>
                            {member.positions.length > 0 && (
                                <div className="mt-3 border-t border-[#EEF1F5] pt-3">
                                    <p className="text-xs text-[#5B6779]">Jabatan</p>
                                    <ul className="mt-1 space-y-0.5 text-sm font-semibold">
                                        {member.positions.map((p) => (
                                            <li key={p}>{p}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            <p className="mt-4 text-xs text-[#8A94A4]">Anggota sejak {member.joined_year}.</p>
                            {!active && (
                                <p className="mt-3 rounded-md bg-[#FDF0D5] px-3 py-2 text-xs font-medium text-[#7A5206]">
                                    Anggota ini tidak berstatus Aktif.
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
