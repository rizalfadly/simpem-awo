import Avatar from '@/Components/Avatar';

/** Kartu anggota digital (rasio kartu ID). QR menuju halaman verifikasi publik. */
export default function MemberCard({ member, qrUrl }) {
    return (
        <div
            className="relative aspect-[1.586/1] w-full max-w-md overflow-hidden rounded-2xl bg-[#A61E26] p-5 text-white shadow-lg"
            style={{ WebkitPrintColorAdjust: 'exact', printColorAdjust: 'exact' }}
        >
            <div className="flex items-start justify-between">
                <div>
                    <div className="text-xl font-extrabold leading-none tracking-tight">SIMPEM</div>
                    <div className="mt-1 text-[11px] font-medium text-white/80">Arek Wetan Organizer</div>
                </div>
                <span className="rounded bg-white/15 px-2 py-0.5 text-xs font-semibold">{member.status_label}</span>
            </div>

            <div className="mt-4 flex gap-4">
                <Avatar name={member.name} src={member.photo_url} className="h-20 w-20 rounded-lg ring-2 ring-white/40" textClass="text-2xl" />
                <div className="min-w-0 pt-0.5">
                    <div className="text-lg font-bold leading-tight">{member.name}</div>
                    {member.positions.length > 0 && (
                        <ul className="mt-1.5 space-y-0.5 text-xs text-white/85">
                            {member.positions.slice(0, 2).map((p) => (
                                <li key={p}>{p}</li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <div className="absolute bottom-5 left-5 right-5 flex items-end justify-between">
                <div>
                    <div className="text-[11px] text-white/70">Nomor Anggota</div>
                    <div className="text-xl font-extrabold tabular-nums tracking-wide">{member.nomor_anggota}</div>
                    <div className="text-[11px] text-white/70">Bergabung {member.joined_year}</div>
                </div>
                <img src={qrUrl} alt="QR Code verifikasi kartu" className="h-20 w-20 rounded bg-white p-1" />
            </div>
        </div>
    );
}
