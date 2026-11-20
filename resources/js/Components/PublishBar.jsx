import { Badge, Button } from '@/Components/ui';

const tone = { published: 'green', draft: 'neutral', archived: 'neutral' };

/** Tombol alur Draf / Dipublikasikan / Diarsipkan. Dipakai album dan berita. */
export default function PublishBar({ status, statusLabel, can, onChange }) {
    const ask = (to, msg) => window.confirm(msg) && onChange(to);

    return (
        <div className="flex flex-wrap items-center gap-2">
            <Badge tone={tone[status]}>{statusLabel}</Badge>
            {status === 'draft' && can.publish && (
                <Button size="sm" onClick={() => ask('published', 'Publikasikan sekarang? Semua anggota yang login dapat melihatnya.')}>
                    Publikasikan
                </Button>
            )}
            {status === 'published' && can.publish && (
                <Button size="sm" variant="secondary" onClick={() => ask('draft', 'Tarik dari publikasi dan kembalikan ke draf?')}>
                    Tarik publikasi
                </Button>
            )}
            {((status === 'draft' && can.manage) || (status === 'published' && can.publish)) && (
                <Button size="sm" variant="secondary" onClick={() => ask('archived', 'Arsipkan? Tidak tampil di daftar utama.')}>
                    Arsipkan
                </Button>
            )}
            {status === 'archived' && can.manage && (
                <Button size="sm" variant="secondary" onClick={() => ask('draft', 'Pulihkan ke draf?')}>
                    Pulihkan
                </Button>
            )}
            {status === 'draft' && !can.publish && <span className="text-xs text-[#5B6779]">Menunggu penerbit (izin content.publish).</span>}
        </div>
    );
}
