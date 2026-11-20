import Avatar from '@/Components/Avatar';

/** Kartu anggota digital (rasio kartu ID). QR menuju halaman verifikasi publik. */
export default function MemberCard({
    member,
    qrUrl,
    bgUrl = '/images/circuit-bg.jpg',
}) {
    const positions = Array.isArray(member?.positions) ? member.positions : [];

    return (
        <div
            className="group relative aspect-[1.586/1] w-full max-w-md overflow-hidden rounded-2xl bg-[#041118] p-5 text-white shadow-lg ring-1 ring-cyan-400/30"
            style={{ WebkitPrintColorAdjust: 'exact', printColorAdjust: 'exact' }}
        >
            {/* Background gambar circuit dengan gerakan pan halus */}
            <img
                src={bgUrl}
                alt="Background sirkuit"
                className="animate-circuit-pan pointer-events-none absolute inset-0 h-full w-full object-cover object-center opacity-90"
            />

            {/* Overlay gelap transparan agar teks, avatar, dan QR tetap jelas */}
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#030b10]/75 via-[#061822]/55 to-[#030c12]/80" />

            {/* Glow cyan berdenyut di sudut */}
            <div className="animate-cyan-pulse pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-cyan-400/20 blur-2xl print:hidden" />
            <div className="animate-cyan-pulse pointer-events-none absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-sky-400/20 blur-2xl print:hidden" />

            {/* Sapuan kilau diagonal halus */}
            <div className="pointer-events-none absolute inset-0 overflow-hidden print:hidden">
                <div className="animate-card-shimmer h-full w-1/3 bg-gradient-to-r from-transparent via-cyan-300/15 to-transparent" />
            </div>

            {/* Konten utama kartu */}
            <div className="relative z-10 flex h-full flex-col justify-between">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="text-xl font-extrabold leading-none tracking-tight text-white drop-shadow">
                            Arek Wetan
                        </div>
                        <div className="mt-1 text-[11px] font-medium text-cyan-200/85">
                            Organizer
                        </div>
                    </div>
                    <span className="rounded border border-cyan-300/30 bg-cyan-400/15 px-2 py-0.5 text-xs font-semibold text-cyan-100 backdrop-blur-xs">
                        {member.status_label}
                    </span>
                </div>

                <div className="mt-4 flex gap-4">
                    <Avatar
                        name={member.name}
                        src={member.photo_url}
                        className="h-20 w-20 rounded-lg ring-2 ring-cyan-300/40"
                        textClass="text-2xl"
                    />
                    <div className="min-w-0 pt-0.5">
                        <div className="text-lg font-bold leading-tight text-white drop-shadow">
                            {member.name}
                        </div>
                        {positions.length > 0 && (
                            <ul className="mt-1.5 space-y-0.5 text-xs text-cyan-100/90">
                                {positions.slice(0, 2).map((p) => (
                                    <li key={p}>{p}</li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>

                <div className="mt-auto flex items-end justify-between pt-4">
                    <div>
                        <div className="text-[11px] text-cyan-100/75">Nomor Anggota</div>
                        <div className="text-xl font-extrabold tabular-nums tracking-wide text-white drop-shadow">
                            {member.nomor_anggota}
                        </div>
                        <div className="text-[11px] text-cyan-200/75">
                            Bergabung {member.joined_year}
                        </div>
                    </div>
                    <img
                        src={qrUrl}
                        alt="QR Code verifikasi kartu"
                        className="h-20 w-20 rounded bg-white p-1 ring-1 ring-cyan-300/40"
                    />
                </div>
            </div>
        </div>
    );
}