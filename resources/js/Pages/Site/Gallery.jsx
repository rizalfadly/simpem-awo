import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Empty } from '@/Components/SiteCards';
import { cx } from '@/Components/ui';

export default function GalleryDetail({ album, items: itemsProp }) {
    const photos = album?.items ?? album?.photos ?? itemsProp ?? [];
    const [activeIdx, setActiveIdx] = useState(null);
    const [fitMode, setFitMode] = useState('cover'); // 'cover' | 'contain'

    const activePhoto = activeIdx !== null ? photos[activeIdx] : null;

    const closeLightbox = () => setActiveIdx(null);
    const prevPhoto = () => {
        if (!photos.length || activeIdx === null) return;
        setActiveIdx((activeIdx - 1 + photos.length) % photos.length);
    };
    const nextPhoto = () => {
        if (!photos.length || activeIdx === null) return;
        setActiveIdx((activeIdx + 1) % photos.length);
    };

    useEffect(() => {
        if (activeIdx === null) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevPhoto();
            if (e.key === 'ArrowRight') nextPhoto();
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    });

    const getSrc = (photo) => photo?.url ?? photo?.full_url ?? photo?.src ?? photo?.path ?? photo?.image ?? '';
    const getCaption = (photo) => photo?.caption ?? photo?.title ?? photo?.description ?? '';

    return (
        <PublicLayout title={album?.title ?? 'Detail Galeri'}>
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

                <div className="relative mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Link
                            href="/galeri"
                            className="inline-flex items-center gap-1.5 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-xs font-bold tracking-wider text-[#36D8E8] uppercase transition hover:bg-[#36D8E8]/20"
                        >
                            <span aria-hidden="true">←</span>
                            <span>Kembali ke Galeri</span>
                        </Link>

                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            {album?.date && (
                                <span className="rounded-lg border border-white/10 bg-white/[0.04] px-3 py-1 font-semibold tabular-nums text-[#94A3B8]">
                                    {album.date}
                                </span>
                            )}
                            <span className="rounded-lg border border-[#36D8E8]/30 bg-[#36D8E8]/10 px-3 py-1 font-extrabold tabular-nums text-[#36D8E8]">
                                {photos.length} Foto
                            </span>
                        </div>
                    </div>

                    <h1 className="mt-4 text-3xl font-black leading-tight tracking-tight sm:text-5xl">
                        {album?.title}
                    </h1>

                    {album?.description && (
                        <p className="mt-3 max-w-3xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                            {album.description}
                        </p>
                    )}
                </div>
            </section>

            {/* Grid Foto Album */}
            <Section className="py-10">
                {photos.length === 0 ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                        <Empty>Belum ada foto di dalam album ini.</Empty>
                    </div>
                ) : (
                    <div className="space-y-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                                <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                                    Dokumentasi Foto
                                </h2>
                            </div>

                            <div
                                role="group"
                                aria-label="Mode bingkai foto"
                                className="inline-flex rounded-xl border border-[#DDE2E9] bg-white p-1 shadow-xs"
                            >
                                <button
                                    type="button"
                                    onClick={() => setFitMode('cover')}
                                    className={cx(
                                        'rounded-lg px-3 py-1 text-xs font-extrabold transition',
                                        fitMode === 'cover'
                                            ? 'bg-[#08090B] text-[#36D8E8]'
                                            : 'text-[#5B6779] hover:text-[#08090B]',
                                    )}
                                >
                                    Penuh (Crop)
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFitMode('contain')}
                                    className={cx(
                                        'rounded-lg px-3 py-1 text-xs font-extrabold transition',
                                        fitMode === 'contain'
                                            ? 'bg-[#08090B] text-[#36D8E8]'
                                            : 'text-[#5B6779] hover:text-[#08090B]',
                                    )}
                                >
                                    Utuh (No Crop)
                                </button>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {photos.map((photo, idx) => {
                                const src = getSrc(photo);
                                const caption = getCaption(photo);

                                return (
                                    <figure
                                        key={photo?.id ?? idx}
                                        className="group overflow-hidden rounded-2xl border border-[#DDE2E9] bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/70 hover:shadow-md"
                                    >
                                        <button
                                            type="button"
                                            onClick={() => setActiveIdx(idx)}
                                            className="relative block aspect-[4/3] w-full overflow-hidden bg-[#0E1116] text-left focus:outline-none focus:ring-2 focus:ring-[#36D8E8]"
                                        >
                                            <img
                                                src={src}
                                                alt={caption || album?.title || `Foto ${idx + 1}`}
                                                loading="lazy"
                                                className={cx(
                                                    'h-full w-full transition duration-300 group-hover:scale-105',
                                                    fitMode === 'cover' ? 'object-cover' : 'object-contain',
                                                )}
                                            />
                                            <span className="pointer-events-none absolute right-2.5 top-2.5 rounded-md border border-white/15 bg-[#08090B]/80 px-2 py-0.5 text-[10px] font-bold tabular-nums text-[#36D8E8] opacity-0 backdrop-blur transition group-hover:opacity-100">
                                                Perbesar
                                            </span>
                                        </button>

                                        {caption && (
                                            <figcaption className="border-t border-[#F1F3F7] px-3.5 py-2.5 text-xs font-semibold text-[#3B4658]">
                                                {caption}
                                            </figcaption>
                                        )}
                                    </figure>
                                );
                            })}
                        </div>
                    </div>
                )}
            </Section>

            {/* Lightbox Modal Gelap-Cyan */}
            {activePhoto && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={getCaption(activePhoto) || album?.title || 'Pratinjau foto'}
                    className="fixed inset-0 z-50 flex items-center justify-center bg-[#08090B]/92 p-4 backdrop-blur-md"
                    onClick={closeLightbox}
                >
                    <div
                        className="relative w-full max-w-4xl overflow-hidden rounded-2xl border border-white/15 bg-[#0E1116] text-white shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                            <div className="flex items-center gap-2 text-xs">
                                <span className="h-2 w-2 rounded-full bg-[#36D8E8]" />
                                <span className="font-extrabold tabular-nums text-[#36D8E8]">
                                    Foto {activeIdx + 1} / {photos.length}
                                </span>
                                <span className="truncate font-semibold text-[#94A3B8]">
                                    · {album?.title}
                                </span>
                            </div>

                            <button
                                type="button"
                                onClick={closeLightbox}
                                className="rounded-lg border border-white/15 bg-white/5 px-3 py-1 text-xs font-bold text-white transition hover:border-[#36D8E8]/50 hover:text-[#36D8E8]"
                            >
                                Tutup (Esc)
                            </button>
                        </div>

                        <div className="relative flex max-h-[72vh] items-center justify-center bg-[#08090B] p-3 sm:p-5">
                            <img
                                src={getSrc(activePhoto)}
                                alt={getCaption(activePhoto) || album?.title || ''}
                                className="max-h-[65vh] w-auto max-w-full rounded-lg object-contain"
                            />

                            {photos.length > 1 && (
                                <>
                                    <button
                                        type="button"
                                        onClick={prevPhoto}
                                        aria-label="Foto sebelumnya"
                                        className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full border border-white/15 bg-[#08090B]/85 px-3 py-2 text-sm font-black text-[#36D8E8] transition hover:border-[#36D8E8]"
                                    >
                                        ‹
                                    </button>
                                    <button
                                        type="button"
                                        onClick={nextPhoto}
                                        aria-label="Foto berikutnya"
                                        className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-white/15 bg-[#08090B]/85 px-3 py-2 text-sm font-black text-[#36D8E8] transition hover:border-[#36D8E8]"
                                    >
                                        ›
                                    </button>
                                </>
                            )}
                        </div>

                        {getCaption(activePhoto) && (
                            <div className="border-t border-white/10 bg-[#0B0D10] px-5 py-3 text-sm font-semibold text-white">
                                {getCaption(activePhoto)}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </PublicLayout>
    );
}