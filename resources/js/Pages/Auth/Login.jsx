import { Head, useForm } from '@inertiajs/react';
import { Button, Field, Input } from '@/Components/ui';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        nomor_anggota: '',
        password: '',
        remember: false,
    });

    const hasErrors = Boolean(errors.nomor_anggota || errors.password);

    const submit = (e) => {
        e.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Masuk" />
            {/* Gunakan min-h-[100dvh] & overflow-x-hidden agar aman dari keyboard mobile & tidak terpotong */}
            <div className="flex min-h-[100dvh] flex-col overflow-x-hidden bg-[#08090B] lg:grid lg:grid-cols-[1.15fr_1fr] lg:bg-white">
                
                {/* Panel Kiri / Header Mobile: Identitas Geometris Arek Wetan Organizer */}
                <div className="relative flex flex-col items-center justify-between overflow-hidden bg-[#08090B] px-5 pt-6 pb-8 text-white sm:px-8 sm:py-10 lg:px-14 lg:py-12">
                    {/* Glow lembut latar belakang */}
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute left-1/2 top-1/2 h-[260px] w-[260px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#36D8E8]/[0.08] blur-3xl sm:h-[360px] sm:w-[360px] lg:h-[420px] lg:w-[420px]"
                    />

                    {/* Badge Atas */}
                    <div className="relative z-10 flex w-full items-center justify-between text-[11px] font-bold tracking-widest text-slate-400 uppercase sm:text-xs">
                        <span className="inline-flex items-center gap-2 text-[#36D8E8]">
                            <span className="h-2 w-2 shrink-0 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            AREK WETAN ORGANIZER
                        </span>
                        <span className="text-slate-500">EST. 2023</span>
                    </div>

                    {/* Centerpiece Logo Heksagon Terbelah + Animasi Garis */}
                    {/* Proporsi dibuat ringkas di mobile (max-w-[210px]), sedang di tablet (sm:max-w-[280px]), penuh di desktop (lg:max-w-[400px]) */}
                    <div className="awo-hex-container relative z-10 my-4 flex w-full max-w-[210px] flex-col items-center sm:my-6 sm:max-w-[280px] lg:my-8 lg:max-w-[400px]">
                        <svg
                            viewBox="0 0 440 450"
                            className="h-auto w-full select-none overflow-visible"
                            role="img"
                            aria-label="Logo Arek Wetan Organizer"
                        >
                            {/* === BAGIAN ATAS HEKSAGON === */}
                            <g className="awo-stroke-steel">
                                <line x1="220" y1="24" x2="220" y2="168" />
                                <polyline points="54,168 54,116 220,168 386,116 386,168" />
                                <polyline points="136,70 136,168 220,116 304,168 304,70" />
                                <polyline points="95,142 220,72 345,142" />
                            </g>

                            <g className="awo-stroke-cyan">
                                <polyline points="54,168 54,116 220,24 386,116 386,168" />
                                <polyline points="95,142 220,72 345,142" />
                                <polyline points="136,118 220,168 304,118" />
                            </g>

                            <polyline
                                points="54,168 54,116 220,24 386,116 386,168"
                                className="awo-stroke-beam"
                            />

                            {/* === TIPOGRAFI TENGAH === */}
                            <text
                                x="220"
                                y="232"
                                textAnchor="middle"
                                fill="#36D8E8"
                                style={{
                                    fontFamily: "'Oswald', 'Impact', 'Arial Narrow', sans-serif",
                                    fontSize: '58px',
                                    fontWeight: 800,
                                    letterSpacing: '2px',
                                }}
                            >
                                AREK WETAN
                            </text>

                            <line x1="54" y1="262" x2="122" y2="262" stroke="#7A8B8C" strokeWidth="3.5" />
                            <text
                                x="220"
                                y="271"
                                textAnchor="middle"
                                fill="#7A8B8C"
                                style={{
                                    fontFamily: "'Inter', sans-serif",
                                    fontSize: '23px',
                                    fontWeight: 700,
                                    letterSpacing: '6px',
                                }}
                            >
                                ORGANIZER
                            </text>
                            <line x1="318" y1="262" x2="386" y2="262" stroke="#7A8B8C" strokeWidth="3.5" />

                            {/* === BAGIAN BAWAH HEKSAGON === */}
                            <g className="awo-stroke-steel">
                                <line x1="220" y1="292" x2="220" y2="432" />
                                <polyline points="54,292 54,344 220,292 386,344 386,292" />
                                <polyline points="136,388 136,292 220,344 304,292 304,388" />
                                <polyline points="95,318 220,388 345,318" />
                            </g>

                            <g className="awo-stroke-cyan">
                                <polyline points="54,292 54,344 220,432 386,344 386,292" />
                                <polyline points="95,318 220,388 345,318" />
                                <polyline points="136,342 220,292 304,342" />
                            </g>

                            <polyline
                                points="386,292 386,344 220,432 54,344 54,292"
                                className="awo-stroke-beam"
                                style={{ animationDelay: '-1.8s' }}
                            />
                        </svg>

                        {/* Deskripsi disembunyikan di HP sangat kecil agar form langsung terlihat, tampil mulai tablet (sm:) */}
                        <p className="mt-3 hidden text-center text-xs leading-relaxed font-medium text-slate-400 sm:block sm:text-sm lg:mt-4">
                            Sistem Informasi Manajemen Kepemudaan — Satu pintu masuk digital untuk anggota dan pengurus.
                        </p>
                    </div>

                    {/* Footer Kiri: hanya tampil di desktop agar hemat ruang vertikal di mobile/tablet */}
                    <p className="relative z-10 hidden text-center text-xs text-slate-500 lg:block">
                        Tata kelola organisasi yang transparan, solid, dan berkelanjutan.
                    </p>
                </div>

                {/* Panel Kanan / Bottom Sheet Form di Mobile & Tablet */}
                <div className="flex flex-1 items-start justify-center rounded-t-3xl bg-white px-5 pt-7 pb-10 shadow-[0_-10px_30px_rgba(0,0,0,0.35)] sm:items-center sm:px-8 sm:py-12 lg:rounded-none lg:px-6 lg:shadow-none">
                    <form
                        key={hasErrors ? 'error-state' : 'normal-state'}
                        onSubmit={submit}
                        noValidate
                        className={`w-full max-w-sm space-y-4 sm:max-w-md sm:space-y-5 lg:max-w-sm ${hasErrors ? 'simpem-shake' : ''}`}
                    >
                        <div>
                            <div className="inline-block rounded-md bg-cyan-50 px-2.5 py-1 text-[11px] font-bold tracking-wider text-cyan-700 uppercase sm:text-xs">
                                SIMPEM Login
                            </div>
                            <h2 className="mt-2 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                                Masuk Akun
                            </h2>
                            <p className="mt-1 text-xs text-[#5B6779] sm:text-sm">
                                Gunakan Nomor Anggota dan kata sandi Anda.
                            </p>
                        </div>

                        <Field label="Nomor Anggota" htmlFor="nomor_anggota" error={errors.nomor_anggota}>
                            <Input
                                id="nomor_anggota"
                                value={data.nomor_anggota}
                                onChange={(e) => setData('nomor_anggota', e.target.value)}
                                placeholder="AWO-0001"
                                autoComplete="username"
                                autoCapitalize="characters"
                                /* text-base (16px) di mobile mencegah auto-zoom paksa di iOS Safari */
                                className="h-11 text-base font-semibold uppercase tracking-wide transition duration-200 placeholder:font-normal placeholder:normal-case placeholder:tracking-normal sm:h-10 sm:text-sm lg:focus:scale-[1.01]"
                            />
                        </Field>

                        <Field label="Kata sandi" htmlFor="password" error={errors.password}>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                className="h-11 text-base transition duration-200 sm:h-10 sm:text-sm lg:focus:scale-[1.01]"
                            />
                        </Field>

                        <label className="flex min-h-[36px] cursor-pointer items-center gap-2.5 text-sm text-slate-700 select-none">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-4 w-4 rounded border-[#CBD2DC] text-cyan-600 transition duration-150 focus:ring-cyan-500"
                            />
                            Ingat saya di perangkat ini
                        </label>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-11 w-full bg-[#0E7490] text-base font-semibold text-white shadow-md shadow-cyan-500/20 transition-all duration-200 hover:bg-[#155E75] active:scale-[0.98] disabled:opacity-75 sm:h-10 sm:text-sm"
                        >
                            {processing ? (
                                <span className="inline-flex items-center justify-center gap-2">
                                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                    <span>Memproses...</span>
                                </span>
                            ) : (
                                'Masuk'
                            )}
                        </Button>

                        <p className="pt-1 text-center text-xs leading-relaxed text-[#5B6779] sm:text-left">
                            Lupa kata sandi? Hubungi sekretaris atau pengurus untuk reset akun.
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}