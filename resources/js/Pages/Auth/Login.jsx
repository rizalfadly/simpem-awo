import { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button, Field, Input } from '@/Components/ui';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        nomor_anggota: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [shake, setShake] = useState(false);
    const nomorRef = useRef(null);
    const passwordRef = useRef(null);

    // Shake via state (bukan `key`), supaya input tidak di-remount
    // dan keyboard HP tidak tertutup saat terjadi error.
    useEffect(() => {
        if (!errors.nomor_anggota && !errors.password) return;

        setShake(true);
        const timer = setTimeout(() => setShake(false), 600);

        // Fokus ke field yang bermasalah
        if (errors.nomor_anggota) nomorRef.current?.focus();
        else passwordRef.current?.focus();

        return () => clearTimeout(timer);
    }, [errors]);

    const submit = (e) => {
        e.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Masuk" />
            {/*
              - min-h-[100dvh]: aman dari address bar & keyboard mobile
              - landscape:max-lg:flex-row: HP landscape jadi 2 kolom, form tidak perlu scroll panjang
            */}
            <div className="flex min-h-[100dvh] flex-col overflow-x-hidden bg-[#08090B] landscape:max-lg:flex-row lg:grid lg:grid-cols-[1.15fr_1fr] lg:bg-white">
                {/* Panel Kiri / Header Mobile */}
                <div className="relative flex flex-col items-center overflow-hidden bg-[#08090B] px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-5 text-white sm:px-8 sm:pt-10 sm:pb-8 landscape:max-lg:w-1/2 landscape:max-lg:justify-center landscape:max-lg:py-4 lg:justify-between lg:px-14 lg:py-12">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute left-1/2 top-1/2 h-[220px] w-[220px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#36D8E8]/[0.08] blur-3xl sm:h-[360px] sm:w-[360px] lg:h-[420px] lg:w-[420px]"
                    />

                    {/* Badge atas: disembunyikan saat HP landscape agar hemat ruang */}
                    <div className="relative z-10 flex w-full items-center justify-between text-[10px] font-bold tracking-widest text-slate-400 uppercase landscape:max-lg:hidden sm:text-xs">
                        <span className="inline-flex items-center gap-2 text-[#36D8E8]">
                            <span className="h-2 w-2 shrink-0 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            AREK WETAN ORGANIZER
                        </span>
                        <span className="text-slate-500">EST. 2023</span>
                    </div>

                    {/* Logo: kecil di HP, bertahap membesar di tablet & desktop */}
                    <div className="awo-hex-container relative z-10 my-3 flex w-full max-w-[130px] flex-col items-center sm:my-6 sm:max-w-[280px] landscape:max-lg:my-0 landscape:max-lg:max-w-[190px] lg:my-8 lg:max-w-[400px]">
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

                        <p className="mt-3 hidden text-center text-xs leading-relaxed font-medium text-slate-400 sm:block sm:text-sm landscape:max-lg:hidden lg:mt-4">
                            Sistem Informasi Manajemen Kepemudaan — Satu pintu masuk digital untuk anggota dan pengurus.
                        </p>
                    </div>

                    <p className="relative z-10 hidden text-center text-xs text-slate-500 lg:block">
                        Tata kelola organisasi yang transparan, solid, dan berkelanjutan.
                    </p>
                </div>

                {/* Panel Kanan / Bottom Sheet di HP */}
                <div className="flex flex-1 items-start justify-center rounded-t-3xl bg-white px-5 pt-6 pb-[max(2rem,env(safe-area-inset-bottom))] shadow-[0_-10px_30px_rgba(0,0,0,0.35)] sm:items-center sm:px-8 sm:py-12 landscape:max-lg:w-1/2 landscape:max-lg:items-center landscape:max-lg:overflow-y-auto landscape:max-lg:rounded-none landscape:max-lg:py-4 landscape:max-lg:shadow-none lg:rounded-none lg:px-6 lg:shadow-none">
                    <form
                        onSubmit={submit}
                        noValidate
                        className={`w-full max-w-sm space-y-4 sm:max-w-md sm:space-y-5 lg:max-w-sm ${shake ? 'simpem-shake' : ''}`}
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
                                ref={nomorRef}
                                id="nomor_anggota"
                                value={data.nomor_anggota}
                                onChange={(e) => setData('nomor_anggota', e.target.value)}
                                placeholder="AWO-0001"
                                autoComplete="username"
                                autoCapitalize="characters"
                                autoCorrect="off"
                                spellCheck={false}
                                enterKeyHint="next"
                                /* text-base (16px) di mobile mencegah auto-zoom iOS Safari */
                                className="h-12 text-base font-semibold uppercase tracking-wide transition duration-200 placeholder:font-normal placeholder:normal-case placeholder:tracking-normal sm:h-10 sm:text-sm lg:focus:scale-[1.01]"
                            />
                        </Field>

                        <Field label="Kata sandi" htmlFor="password" error={errors.password}>
                            <div className="relative">
                                <Input
                                    ref={passwordRef}
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="current-password"
                                    autoCapitalize="none"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    enterKeyHint="go"
                                    className="h-12 pr-14 text-base transition duration-200 sm:h-10 sm:pr-12 sm:text-sm lg:focus:scale-[1.01]"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                                    aria-pressed={showPassword}
                                    className="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-md text-slate-500 transition hover:text-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:w-11"
                                >
                                    {showPassword ? (
                                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                            <path d="M3 3l18 18" />
                                            <path d="M10.6 6.1A10.9 10.9 0 0 1 12 6c5 0 9 4 10 6-.5 1-1.6 2.5-3.2 3.8M6.5 7.6C4.4 9 3 11 2 12c1 2 5 6 10 6 1.5 0 2.9-.4 4.1-.9" />
                                            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                        </svg>
                                    ) : (
                                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                            <path d="M2 12c1-2 5-6 10-6s9 4 10 6c-1 2-5 6-10 6S3 14 2 12z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                        </Field>

                        {/* Area sentuh minimal 44px */}
                        <label className="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm text-slate-700 select-none">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-5 w-5 shrink-0 rounded border-[#CBD2DC] text-cyan-600 transition duration-150 focus:ring-cyan-500"
                            />
                            Ingat saya di perangkat ini
                        </label>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-12 w-full bg-[#0E7490] text-base font-semibold text-white shadow-md shadow-cyan-500/20 transition-all duration-200 hover:bg-[#155E75] active:scale-[0.98] disabled:opacity-75 sm:h-10 sm:text-sm"
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