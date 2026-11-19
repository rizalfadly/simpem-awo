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
            <div className="min-h-screen overflow-hidden lg:grid lg:grid-cols-[1.15fr_1fr]">
                {/* Panel Kiri: Identitas Geometris Arek Wetan Organizer */}
                <div className="relative flex flex-col items-center justify-between overflow-hidden bg-[#08090B] px-8 py-10 text-white lg:px-14 lg:py-12">
                    {/* Glow lembut latar belakang */}
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute left-1/2 top-1/2 h-[420px] w-[420px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#36D8E8]/[0.07] blur-3xl"
                    />

                    {/* Badge Atas */}
                    <div className="relative z-10 flex w-full items-center justify-between text-xs font-bold tracking-widest text-slate-400 uppercase">
                        <span className="inline-flex items-center gap-2 text-[#36D8E8]">
                            <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            SIMPEM · AREK WETAN ORGANIZER
                        </span>
                        <span className="hidden sm:inline text-slate-500">EST. 2023</span>
                    </div>

                    {/* Centerpiece Logo Heksagon Terbelah + Animasi Garis */}
                    <div className="awo-hex-container relative z-10 my-8 flex w-full max-w-[360px] flex-col items-center sm:max-w-[400px]">
                        <svg
                            viewBox="0 0 440 450"
                            className="w-full h-auto select-none overflow-visible"
                            role="img"
                            aria-label="Logo Arek Wetan Organizer"
                        >
                            {/* === BAGIAN ATAS HEKSAGON === */}
                            {/* Kisi-kisi struktur abu-abu baja atas */}
                            <g className="awo-stroke-steel">
                                <line x1="220" y1="24" x2="220" y2="168" />
                                <polyline points="54,168 54,116 220,168 386,116 386,168" />
                                <polyline points="136,70 136,168 220,116 304,168 304,70" />
                                <polyline points="95,142 220,72 345,142" />
                            </g>

                            {/* Bingkai luar & dalam cyan atas */}
                            <g className="awo-stroke-cyan">
                                <polyline points="54,168 54,116 220,24 386,116 386,168" />
                                <polyline points="95,142 220,72 345,142" />
                                <polyline points="136,118 220,168 304,118" />
                            </g>

                            {/* Aliran energi cahaya menyusuri bingkai atas */}
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

                            {/* Garis pengapit ORGANIZER */}
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
                            {/* Kisi-kisi struktur abu-abu baja bawah */}
                            <g className="awo-stroke-steel">
                                <line x1="220" y1="292" x2="220" y2="432" />
                                <polyline points="54,292 54,344 220,292 386,344 386,292" />
                                <polyline points="136,388 136,292 220,344 304,292 304,388" />
                                <polyline points="95,318 220,388 345,318" />
                            </g>

                            {/* Bingkai luar & dalam cyan bawah */}
                            <g className="awo-stroke-cyan">
                                <polyline points="54,292 54,344 220,432 386,344 386,292" />
                                <polyline points="95,318 220,388 345,318" />
                                <polyline points="136,342 220,292 304,342" />
                            </g>

                            {/* Aliran energi cahaya menyusuri bingkai bawah */}
                            <polyline
                                points="386,292 386,344 220,432 54,344 54,292"
                                className="awo-stroke-beam"
                                style={{ animationDelay: '-1.8s' }}
                            />
                        </svg>

                        <p className="mt-4 text-center text-sm font-medium leading-relaxed text-slate-400">
                            Sistem Informasi Manajemen Kepemudaan — Satu pintu masuk digital untuk anggota dan pengurus.
                        </p>
                    </div>

                    {/* Footer Kiri */}
                    <p className="relative z-10 text-center text-xs text-slate-500">
                        Tata kelola organisasi yang transparan, solid, dan berkelanjutan.
                    </p>
                </div>

                {/* Panel Kanan: Form Masuk */}
                <div className="flex items-center justify-center bg-white px-6 py-12">
                    <form
                        key={hasErrors ? 'error-state' : 'normal-state'}
                        onSubmit={submit}
                        noValidate
                        className={`w-full max-w-sm space-y-5 ${hasErrors ? 'simpem-shake' : ''}`}
                    >
                        <div>
                            <div className="inline-block rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-bold tracking-wider text-cyan-700 uppercase">
                                SIMPEM Login
                            </div>
                            <h2 className="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Masuk Akun</h2>
                            <p className="mt-1 text-sm text-[#5B6779]">Gunakan Nomor Anggota dan kata sandi Anda.</p>
                        </div>

                        <Field label="Nomor Anggota" htmlFor="nomor_anggota" error={errors.nomor_anggota}>
                            <Input
                                id="nomor_anggota"
                                value={data.nomor_anggota}
                                onChange={(e) => setData('nomor_anggota', e.target.value)}
                                placeholder="AWO-0001"
                                autoComplete="username"
                                autoFocus
                                className="font-semibold uppercase tracking-wide transition duration-200 focus:scale-[1.01] placeholder:font-normal placeholder:normal-case placeholder:tracking-normal"
                            />
                        </Field>

                        <Field label="Kata sandi" htmlFor="password" error={errors.password}>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                className="transition duration-200 focus:scale-[1.01]"
                            />
                        </Field>

                        <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-700 select-none">
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
                            className="w-full bg-[#0E7490] text-white hover:bg-[#155E75] shadow-md shadow-cyan-500/20 transition-all duration-200 active:scale-[0.98] disabled:opacity-75"
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

                        <p className="text-xs text-[#5B6779]">
                            Lupa kata sandi? Hubungi sekretaris atau pengurus untuk reset akun.
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}