import { useEffect, useRef } from 'react';

/** Penampil foto layar penuh: Esc menutup, panah kiri/kanan berpindah. */
export default function Lightbox({ items, index, onClose, onChange }) {
    const closeRef = useRef(null);
    const item = items[index];

    useEffect(() => {
        const prev = document.activeElement;
        closeRef.current?.focus();
        const onKey = (e) => {
            if (e.key === 'Escape') onClose();
            if (e.key === 'ArrowRight') onChange((index + 1) % items.length);
            if (e.key === 'ArrowLeft') onChange((index - 1 + items.length) % items.length);
        };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
            prev?.focus?.();
        };
    }, [index, items.length]);

    if (!item) return null;

    return (
        <div role="dialog" aria-modal="true" aria-label="Penampil foto" className="fixed inset-0 z-50 flex flex-col bg-black/90 print:hidden" onClick={onClose}>
            <div className="flex items-center justify-between px-4 py-3 text-white" onClick={(e) => e.stopPropagation()}>
                <span className="text-sm tabular-nums">
                    {index + 1} / {items.length}
                </span>
                <button ref={closeRef} type="button" onClick={onClose} className="rounded-md border border-white/40 px-3 py-1.5 text-sm font-semibold hover:bg-white/10">
                    Tutup
                </button>
            </div>
            <div className="relative flex min-h-0 flex-1 items-center justify-center px-4" onClick={(e) => e.stopPropagation()}>
                {items.length > 1 && (
                    <button type="button" aria-label="Foto sebelumnya" onClick={() => onChange((index - 1 + items.length) % items.length)} className="absolute left-2 rounded-full bg-white/15 px-3 py-2 text-xl text-white hover:bg-white/25">
                        ‹
                    </button>
                )}
                <img src={item.src} alt={item.caption ?? ''} className="max-h-full max-w-full object-contain" />
                {items.length > 1 && (
                    <button type="button" aria-label="Foto berikutnya" onClick={() => onChange((index + 1) % items.length)} className="absolute right-2 rounded-full bg-white/15 px-3 py-2 text-xl text-white hover:bg-white/25">
                        ›
                    </button>
                )}
            </div>
            <p className="min-h-12 px-6 py-3 text-center text-sm text-white/90" onClick={(e) => e.stopPropagation()}>
                {item.caption}
            </p>
        </div>
    );
}
