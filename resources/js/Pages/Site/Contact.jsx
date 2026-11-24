import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Button, Card, Field, Input, inputCls } from '@/Components/ui';

export default function Contact({ site }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        website: '',
    });

    const [sentNotice, setSentNotice] = useState(false);
    const [copiedKey, setCopiedKey] = useState(null);

    const copyText = (key, rawText) => {
        if (!rawText || typeof navigator === 'undefined' || !navigator.clipboard) return;
        navigator.clipboard.writeText(String(rawText)).then(() => {
            setCopiedKey(key);
            window.setTimeout(() => setCopiedKey(null), 1800);
        });
    };

    const info = [
        {
            key: 'address',
            label: 'Alamat',
            copyValue: site?.address,
            value: site?.address,
        },
        {
            key: 'email',
            label: 'Email',
            copyValue: site?.email,
            value: site?.email && (
                <a href={`mailto:${site.email}`} className="text-[#0891B2] hover:underline">
                    {site.email}
                </a>
            ),
        },
        {
            key: 'phone',
            label: 'Telepon',
            copyValue: site?.phone,
            value: site?.phone && <span className="tabular-nums">{site.phone}</span>,
        },
        {
            key: 'whatsapp',
            label: 'WhatsApp',
            copyValue: site?.whatsapp || site?.whatsapp_url,
            value: site?.whatsapp_url && (
                <a
                    href={site.whatsapp_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-[#0891B2] hover:underline"
                >
                    {site.whatsapp || 'Chat WhatsApp'}
                </a>
            ),
        },
        {
            key: 'hours',
            label: 'Jam layanan',
            copyValue: null,
            value: site?.hours,
        },
        {
            key: 'maps',
            label: 'Peta',
            copyValue: null,
            value: site?.maps_url && (
                <a
                    href={site.maps_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 text-[#0891B2] hover:underline"
                >
                    <span>Buka di peta</span>
                    <span aria-hidden="true">↗</span>
                </a>
            ),
        },
    ].filter((item) => item.value);

    return (
        <PublicLayout title="Kontak">
            {/* Header Banner Gelap Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute -bottom-24 left-12 h-72 w-72 rounded-full bg-[#0284C7]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-6 px-4 py-12 sm:px-6 sm:py-16">
                    <div>
                        <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            <span>Jalur Komunikasi</span>
                        </div>

                        <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Kontak</h1>
                        <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                            Ada rencana kolaborasi event, kemitraan UMKM, atau pertanyaan seputar kegiatan? Hubungi kanal resmi kami atau kirim pesan langsung.
                        </p>
                    </div>

                    {(site?.whatsapp_url || site?.email) && (
                        <div className="flex flex-wrap gap-2.5">
                            {site?.whatsapp_url && (
                                <a
                                    href={site.whatsapp_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="rounded-xl bg-[#36D8E8] px-4 py-2.5 text-xs font-extrabold text-[#08090B] shadow-[0_0_18px_rgba(54,216,232,0.3)] transition hover:bg-[#67E8F9] sm:text-sm"
                                >
                                    Chat WhatsApp
                                </a>
                            )}
                            {site?.email && (
                                <a
                                    href={`mailto:${site.email}`}
                                    className="rounded-xl border border-white/15 bg-white/[0.06] px-4 py-2.5 text-xs font-extrabold text-white transition hover:border-[#36D8E8]/50 hover:text-[#36D8E8] sm:text-sm"
                                >
                                    Kirim Email
                                </a>
                            )}
                        </div>
                    )}
                </div>
            </section>

            {/* Informasi Kontak & Formulir Pesan */}
            <Section className="py-10">
                <div className="grid gap-8 lg:grid-cols-12">
                    {/* Kolom Kiri: Kanal Informasi */}
                    <div className="space-y-4 lg:col-span-5">
                        <div className="flex items-center gap-3">
                            <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                            <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                                Kanal Resmi
                            </h2>
                        </div>

                        {info.length === 0 ? (
                            <div className="rounded-2xl border border-[#DDE2E9] bg-white p-5 text-sm text-[#5B6779] shadow-xs">
                                Informasi kontak sedang disiapkan. Silakan kirim pesan lewat formulir.
                            </div>
                        ) : (
                            <dl className="space-y-3">
                                {info.map((item) => (
                                    <div
                                        key={item.key}
                                        className="group flex items-start justify-between gap-3 rounded-2xl border border-[#DDE2E9] bg-white p-4 shadow-xs transition hover:border-[#36D8E8]/70"
                                    >
                                        <div className="min-w-0">
                                            <dt className="text-[11px] font-bold tracking-wider text-[#7A8B8C] uppercase">
                                                {item.label}
                                            </dt>
                                            <dd className="mt-1 text-sm font-bold text-[#08090B]">{item.value}</dd>
                                        </div>

                                        {item.copyValue && (
                                            <button
                                                type="button"
                                                onClick={() => copyText(item.key, item.copyValue)}
                                                className="shrink-0 rounded-lg border border-[#DDE2E9] bg-[#FAFBFD] px-2.5 py-1 text-[11px] font-bold text-[#5B6779] transition hover:border-[#36D8E8] hover:text-[#0891B2]"
                                            >
                                                {copiedKey === item.key ? 'Tersalin' : 'Salin'}
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </dl>
                        )}
                    </div>

                    {/* Kolom Kanan: Formulir Pesan */}
                    <div className="lg:col-span-7">
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                setSentNotice(false);
                                post('/kontak', {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        reset();
                                        setSentNotice(true);
                                    },
                                });
                            }}
                            noValidate
                        >
                            <Card className="space-y-4 rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm sm:p-7">
                                <div className="flex items-center justify-between gap-3 border-b border-[#EEF1F5] pb-4">
                                    <div className="flex items-center gap-3">
                                        <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                                        <div>
                                            <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                                                Kirim pesan
                                            </h2>
                                            <p className="text-xs text-[#5B6779]">
                                                Pesan akan diteruskan langsung ke tim pengurus.
                                            </p>
                                        </div>
                                    </div>
                                    <span className="hidden rounded-full border border-[#36D8E8]/30 bg-[#36D8E8]/10 px-2.5 py-0.5 text-[10px] font-extrabold tracking-widest text-[#0891B2] uppercase sm:inline-block">
                                        Direct Form
                                    </span>
                                </div>

                                {sentNotice && (
                                    <div
                                        role="status"
                                        className="flex items-center gap-2.5 rounded-xl border border-[#36D8E8]/40 bg-[#08090B] px-4 py-3 text-xs font-bold text-[#36D8E8]"
                                    >
                                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                                        <span>Pesan berhasil dikirim. Terima kasih sudah menghubungi kami!</span>
                                    </div>
                                )}

                                <Field label="Nama" htmlFor="name" error={errors.name}>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => {
                                            setSentNotice(false);
                                            setData('name', e.target.value);
                                        }}
                                        autoComplete="name"
                                        placeholder="Nama lengkap kamu"
                                    />
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field label="Email" htmlFor="email" error={errors.email}>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => {
                                                setSentNotice(false);
                                                setData('email', e.target.value);
                                            }}
                                            autoComplete="email"
                                            placeholder="nama@email.com"
                                        />
                                    </Field>
                                    <Field label="Telepon / WhatsApp" htmlFor="phone" error={errors.phone}>
                                        <Input
                                            id="phone"
                                            inputMode="tel"
                                            value={data.phone}
                                            onChange={(e) => {
                                                setSentNotice(false);
                                                setData('phone', e.target.value);
                                            }}
                                            autoComplete="tel"
                                            placeholder="08xxxxxxxxxx"
                                        />
                                    </Field>
                                </div>

                                <p className="-mt-1 text-xs text-[#5B6779]">
                                    Isi salah satu agar kami bisa membalas.
                                </p>

                                <Field label="Subjek (opsional)" htmlFor="subject" error={errors.subject}>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="Contoh: Kerja sama acara / Kemitraan UMKM"
                                    />
                                </Field>

                                <Field label="Pesan" htmlFor="message" error={errors.message}>
                                    <textarea
                                        id="message"
                                        rows={5}
                                        value={data.message}
                                        onChange={(e) => {
                                            setSentNotice(false);
                                            setData('message', e.target.value);
                                        }}
                                        placeholder="Tuliskan detail pesan atau kebutuhanmu..."
                                        className={inputCls}
                                    />
                                </Field>

                                {/* Perangkap bot: disembunyikan dari pengguna dan pembaca layar. Bila terisi, pesan dibuang. */}
                                <div className="hidden" aria-hidden="true">
                                    <label htmlFor="website">Jangan isi kolom ini</label>
                                    <input
                                        id="website"
                                        tabIndex={-1}
                                        autoComplete="off"
                                        value={data.website}
                                        onChange={(e) => setData('website', e.target.value)}
                                    />
                                </div>

                                <div className="pt-1">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-[#08090B] font-extrabold text-[#36D8E8] ring-1 ring-[#36D8E8]/40 transition hover:bg-[#12161F] hover:ring-[#36D8E8]"
                                    >
                                        {processing ? 'Mengirim...' : 'Kirim pesan'}
                                    </Button>
                                </div>
                            </Card>
                        </form>
                    </div>
                </div>
            </Section>
        </PublicLayout>
    );
}