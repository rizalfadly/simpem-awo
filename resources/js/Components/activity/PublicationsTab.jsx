import { Link } from '@inertiajs/react';
import { Badge, Button, Card } from '@/Components/ui';

const tone = { published: 'green', draft: 'neutral', archived: 'neutral' };

export default function PublicationsTab({ activity, publications }) {
    const { albums, news, can_create_album, can_create_news } = publications;
    const isPublic = ['approved', 'completed', 'closed'].includes(activity.status);

    return (
        <div className="space-y-6">
            {!isPublic && (
                <p className="rounded-md bg-[#FDF0D5] px-4 py-3 text-sm text-[#7A5206]">
                    Album dan berita baru dapat dipublikasikan setelah proposal kegiatan disetujui.
                </p>
            )}

            <Card>
                <div className="flex items-center justify-between border-b border-[#DDE2E9] px-4 py-3">
                    <h2 className="text-base font-bold">Album dokumentasi</h2>
                    {can_create_album && (
                        <Button size="sm" variant="secondary" href={`/albums/create?activity=${activity.id}`}>
                            Buat album
                        </Button>
                    )}
                </div>
                {albums.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Belum ada album untuk kegiatan ini.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {albums.map((a) => (
                            <li key={a.id}>
                                <Link href={`/albums/${a.id}`} className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-[#F7F8FA]">
                                    <span>
                                        <span className="block text-sm font-semibold">{a.title}</span>
                                        <span className="block text-xs text-[#5B6779]">{a.photos} foto</span>
                                    </span>
                                    <Badge tone={tone[a.status]}>{a.status_label}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Card>
                <div className="flex items-center justify-between border-b border-[#DDE2E9] px-4 py-3">
                    <h2 className="text-base font-bold">Berita kegiatan</h2>
                    {can_create_news && (
                        <Button size="sm" variant="secondary" href={`/news/create?activity=${activity.id}`}>
                            Tulis berita
                        </Button>
                    )}
                </div>
                {news.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Belum ada berita untuk kegiatan ini.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {news.map((n) => (
                            <li key={n.id}>
                                <Link href={`/news/${n.id}`} className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-[#F7F8FA]">
                                    <span className="text-sm font-semibold">{n.title}</span>
                                    <Badge tone={tone[n.status]}>{n.status_label}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </div>
    );
}
