import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Lightbox from '@/Components/Lightbox';
import PublishBar from '@/Components/PublishBar';
import { Button, Card, Field, Input, inputCls } from '@/Components/ui';

const keep = { preserveScroll: true };

function Caption({ item, editable }) {
    const [value, setValue] = useState(item.caption ?? '');
    if (!editable) return item.caption ? <p className="mt-1.5 text-xs text-[#3B4658]">{item.caption}</p> : null;
    return (
        <input
            value={value}
            onChange={(e) => setValue(e.target.value)}
            onBlur={() => value !== (item.caption ?? '') && router.put(`/media-items/${item.id}`, { caption: value }, keep)}
            placeholder="Tambah keterangan"
            aria-label="Keterangan media"
            className="mt-1.5 w-full rounded border border-transparent px-1 py-0.5 text-xs hover:border-[#CBD2DC] focus:border-[#16202E] focus:outline-none"
        />
    );
}

export default function Show({ album, media, can }) {
    const photos = media.filter((m) => m.type === 'photo');
    const videos = media.filter((m) => m.type === 'video');
    const [lightbox, setLightbox] = useState(null);

    const pf = useForm({ photos: [] });
    const vf = useForm({ video_url: '', video_file: null, caption: '' });

    const uploadPhotos = (e) => {
        e.preventDefault();
        pf.post(`/albums/${album.id}/photos`, { forceFormData: true, preserveScroll: true, onSuccess: () => { pf.reset(); document.getElementById('photos')?.form?.reset(); } });
    };
    const uploadVideo = (e) => {
        e.preventDefault();
        vf.post(`/albums/${album.id}/videos`, { forceFormData: true, preserveScroll: true, onSuccess: () => { vf.reset(); document.getElementById('video_file')?.form?.reset(); } });
    };
    const photoError = pf.errors.photos || Object.entries(pf.errors).find(([k]) => k.startsWith('photos.'))?.[1];

    return (
        <AppLayout>
            <Head title={album.title} />

            <div className="mb-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight">{album.title}</h1>
                        <p className="mt-1 text-sm text-[#5B6779]">
                            {album.event_date}
                            {album.activity && (
                                <>
                                    {album.event_date ? ' · ' : ''}
                                    <Link href={`/activities/${album.activity.id}`} className="font-semibold text-[#B3202A] hover:underline">
                                        {album.activity.code} {album.activity.title}
                                    </Link>
                                </>
                            )}
                        </p>
                        {album.description && <p className="mt-2 max-w-prose whitespace-pre-line text-sm">{album.description}</p>}
                    </div>
                    <div className="flex gap-2">
                        {can.manage && (
                            <Button variant="secondary" href={`/albums/${album.id}/edit`}>
                                Ubah
                            </Button>
                        )}
                        {can.manage && album.status !== 'published' && (
                            <Button variant="danger" onClick={() => window.confirm('Hapus album ini?') && router.delete(`/albums/${album.id}`)}>
                                Hapus
                            </Button>
                        )}
                    </div>
                </div>
                <div className="mt-4">
                    <PublishBar status={album.status} statusLabel={album.status_label} can={can} onChange={(to) => router.post(`/albums/${album.id}/status`, { status: to }, keep)} />
                    {album.published_at && <p className="mt-1.5 text-xs text-[#5B6779]">Terbit {album.published_at}{album.published_by ? ` oleh ${album.published_by}` : ''}</p>}
                </div>
            </div>

            {can.contribute && (
                <div className="mb-8 grid gap-6 lg:grid-cols-2">
                    <form onSubmit={uploadPhotos} noValidate>
                        <Card className="p-5">
                            <h2 className="text-base font-bold">Unggah foto</h2>
                            <div className="mt-3">
                                <Field label="Pilih foto (maks. 20, tiap berkas 8 MB)" htmlFor="photos" error={photoError} hint="JPG, PNG, atau WEBP. Lokasi (GPS) di dalam foto otomatis dihapus.">
                                    <input id="photos" type="file" multiple accept="image/jpeg,image/png,image/webp" onChange={(e) => pf.setData('photos', Array.from(e.target.files))} className={inputCls} />
                                </Field>
                            </div>
                            <Button type="submit" className="mt-4" disabled={pf.processing || pf.data.photos.length === 0}>
                                {pf.processing ? 'Mengunggah...' : `Unggah ${pf.data.photos.length || ''} foto`}
                            </Button>
                        </Card>
                    </form>

                    <form onSubmit={uploadVideo} noValidate>
                        <Card className="p-5">
                            <h2 className="text-base font-bold">Tambah video</h2>
                            <div className="mt-3 space-y-4">
                                <Field label="Tautan video (https)" htmlFor="video_url" error={vf.errors.video_url} hint="YouTube akan ditampilkan langsung. Tautan lain dibuka di tab baru.">
                                    <Input id="video_url" value={vf.data.video_url} onChange={(e) => vf.setData('video_url', e.target.value)} placeholder="https://www.youtube.com/watch?v=..." />
                                </Field>
                                <Field label="atau unggah berkas (MP4/WEBM, maks. 50 MB)" htmlFor="video_file" error={vf.errors.video_file}>
                                    <input id="video_file" type="file" accept="video/mp4,video/webm" onChange={(e) => vf.setData('video_file', e.target.files[0] ?? null)} className={inputCls} />
                                </Field>
                                <Field label="Keterangan (opsional)" htmlFor="v_caption" error={vf.errors.caption}>
                                    <Input id="v_caption" value={vf.data.caption} onChange={(e) => vf.setData('caption', e.target.value)} />
                                </Field>
                            </div>
                            <Button type="submit" className="mt-4" variant="secondary" disabled={vf.processing}>
                                Tambahkan video
                            </Button>
                        </Card>
                    </form>
                </div>
            )}

            <section className="mb-10">
                <h2 className="mb-3 text-lg font-extrabold tracking-tight">Foto ({photos.length})</h2>
                {photos.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-10 text-center text-sm text-[#5B6779]">Belum ada foto.</p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {photos.map((m, i) => (
                            <figure key={m.id}>
                                <button type="button" onClick={() => setLightbox(i)} className="block aspect-square w-full overflow-hidden rounded-lg bg-[#E9EDF2] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#16202E]" aria-label={`Buka foto ${i + 1}`}>
                                    <img src={m.thumb} alt={m.caption ?? `Foto ${i + 1}`} loading="lazy" className="h-full w-full object-cover" />
                                </button>
                                <figcaption>
                                    <Caption item={m} editable={can.contribute} />
                                    <div className="mt-1 flex gap-3 text-xs">
                                        {can.manage && (
                                            album.cover_media_id === m.id ? <span className="font-semibold text-[#1E6B3C]">Sampul</span> : (
                                                <button type="button" onClick={() => router.post(`/albums/${album.id}/cover`, { media_id: m.id }, keep)} className="font-semibold text-[#5B6779] hover:underline">
                                                    Jadikan sampul
                                                </button>
                                            )
                                        )}
                                        {m.can_delete && (
                                            <button type="button" onClick={() => window.confirm('Hapus foto ini permanen?') && router.delete(`/media-items/${m.id}`, keep)} className="font-semibold text-[#B3202A] hover:underline">
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                )}
            </section>

            {videos.length > 0 && (
                <section>
                    <h2 className="mb-3 text-lg font-extrabold tracking-tight">Video ({videos.length})</h2>
                    <div className="grid gap-6 md:grid-cols-2">
                        {videos.map((m) => (
                            <div key={m.id}>
                                <div className="overflow-hidden rounded-lg border border-[#DDE2E9] bg-black">
                                    {m.embed ? (
                                        <iframe src={m.embed} title={m.caption ?? 'Video'} loading="lazy" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" className="aspect-video w-full" />
                                    ) : m.src ? (
                                        <video controls preload="metadata" src={m.src} className="aspect-video w-full" />
                                    ) : (
                                        <a href={m.href} target="_blank" rel="noopener noreferrer" className="flex aspect-video items-center justify-center bg-white p-4 text-center text-sm font-semibold text-[#B3202A] hover:underline">
                                            Buka video di tab baru
                                            <span className="ml-1 break-all text-xs font-normal text-[#5B6779]">({new URL(m.href).hostname})</span>
                                        </a>
                                    )}
                                </div>
                                <Caption item={m} editable={can.contribute} />
                                {m.can_delete && (
                                    <button type="button" onClick={() => window.confirm('Hapus video ini?') && router.delete(`/media-items/${m.id}`, keep)} className="mt-1 text-xs font-semibold text-[#B3202A] hover:underline">
                                        Hapus
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {lightbox !== null && <Lightbox items={photos} index={lightbox} onClose={() => setLightbox(null)} onChange={setLightbox} />}
        </AppLayout>
    );
}
