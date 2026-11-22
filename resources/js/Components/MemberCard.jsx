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
            className="group relative min-h-[215px] w-full max-w-md overflow-hidden rounded-2xl bg-[#041118] p-3.5 text-white shadow-lg ring-1 ring-cyan-400/30 sm:min-h-0 sm:aspect-[1.586/1] sm:p-5"
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
            <div className="relative z-10 flex h-full flex-col justify-between gap-2.5 sm:gap-0">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <div className="text-base font-extrabold leading-none tracking-tight text-white drop-shadow sm:text-xl">
                            Arek Wetan
                        </div>
                        <div className="mt-0.5 text-[10px] font-medium text-cyan-200/85 sm:mt-1 sm:text-[11px]">
                            Organizer
                        </div>
                    </div>
                    <span className="shrink-0 rounded border border-cyan-300/30 bg-cyan-400/15 px-2 py-0.5 text-[10px] font-semibold text-cyan-100 backdrop-blur-xs sm:text-xs">
                        {member.status_label}
                    </span>
                </div>

                <div className="flex items-center gap-3 sm:mt-4 sm:items-start sm:gap-4">
                    <Avatar
                        name={member.name}
                        src={member.photo_url}
                        className="h-14 w-14 shrink-0 rounded-lg ring-2 ring-cyan-300/40 sm:h-20 sm:w-20"
                        textClass="text-lg sm:text-2xl"
                    />
                    <div className="min-w-0 flex-1 sm:pt-0.5">
                        <div className="text-sm font-bold leading-snug text-white drop-shadow break-words sm:text-lg sm:leading-tight">
                            {member.name}
                        </div>
                        {positions.length > 0 && (
                            <ul className="mt-1 space-y-0.5 text-[11px] text-cyan-100/90 sm:mt-1.5 sm:text-xs">
                                {positions.slice(0, 2).map((p) => (
                                    <li key={p} className="truncate">
                                        {p}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>

                <div className="mt-auto flex items-end justify-between gap-2 pt-1 sm:pt-4">
                    <div className="min-w-0">
                        <div className="text-[10px] text-cyan-100/75 sm:text-[11px]">
                            Nomor Anggota
                        </div>
                        <div className="truncate text-sm font-extrabold tabular-nums tracking-wide text-white drop-shadow sm:text-xl">
                            {member.nomor_anggota}
                        </div>
                        <div className="text-[10px] text-cyan-200/75 sm:text-[11px]">
                            Bergabung {member.joined_year}
                        </div>
                    </div>
                    <img
                        src={qrUrl}
                        alt="QR Code verifikasi kartu"
                        className="h-14 w-14 shrink-0 rounded bg-white p-1 ring-1 ring-cyan-300/40 sm:h-20 sm:w-20"
                    />
                </div>
            </div>
        </div>
    );
}