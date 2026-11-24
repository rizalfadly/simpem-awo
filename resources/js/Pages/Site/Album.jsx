import { Link } from '@inertiajs/react';
import { useState } from 'react';
import Lightbox from '@/Components/Lightbox';
import PublicLayout, { Section } from '@/Components/PublicLayout';

export default function Album({ album, media }) {
    const photos = media.filter((m) => m.type === 'photo');
    const videos = media.filter((m) => m.type === 'video');
    const [open, setOpen] = useState(null);

    return (
        <PublicLayout title={album.title}>
            <Section>
                <Link href="/galeri" className="text-sm font-semibold text-[#5B6779] hover:underline">
                    Galeri
                </Link>
                <h1 className="mt-2 text-4xl font-extrabold leading-tight tracking-tight">{album.title}</h1>
                {album.date && <p className="mt-2 text-sm text-[#5B6779]">{album.date}</p>}
                {album.description && <p className="mt-4 max-w-3xl whitespace-pre-line text-base text-[#3B4658]">{album.description}</p>}

                {photos.length > 0 && (
                    <div className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        {photos.map((m, i) => (
                            <figure key={m.id}>
                                <button type="button" onClick={() => setOpen(i)} aria-label={`Buka foto ${i + 1}`} className="block aspect-square w-full overflow-hidden rounded-lg bg-[#E9EDF2] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#16202E]">
                                    <img src={m.thumb} alt={m.caption ?? `Foto ${i + 1}`} loading="lazy" className="h-full w-full object-cover" />
                                </button>
                                {m.caption && <figcaption className="mt-1.5 text-xs text-[#3B4658]">{m.caption}</figcaption>}
                            </figure>
                        ))}
                    </div>
                )}

                {videos.length > 0 && (
                    <div className="mt-10 grid gap-6 md:grid-cols-2">
                        {videos.map((m) => (
                            <div key={m.id}>
                                <div className="overflow-hidden rounded-lg border border-[#DDE2E9] bg-black">
                                    {m.embed ? (
                                        <iframe src={m.embed} title={m.caption ?? 'Video'} loading="lazy" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" className="aspect-video w-full" />
                                    ) : m.src ? (
                                        <video controls preload="metadata" src={m.src} className="aspect-video w-full" />
                                    ) : (
                                        <a href={m.href} target="_blank" rel="noopener noreferrer" className="flex aspect-video items-center justify-center bg-white p-4 text-center text-sm font-semibold text-[#B3202A] hover:underline">
                                            Tonton video di tab baru
                                        </a>
                                    )}
                                </div>
                                {m.caption && <p className="mt-1.5 text-xs text-[#3B4658]">{m.caption}</p>}
                            </div>
                        ))}
                    </div>
                )}
            </Section>
            {open !== null && <Lightbox items={photos} index={open} onClose={() => setOpen(null)} onChange={setOpen} />}
        </PublicLayout>
    );
}
