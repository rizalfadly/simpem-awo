import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import PublishBar from '@/Components/PublishBar';
import { Button } from '@/Components/ui';

export default function Show({ post, can }) {
    return (
        <AppLayout>
            <Head title={post.title} />

            <article className="mx-auto max-w-3xl">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <PublishBar status={post.status} statusLabel={post.status_label} can={can} onChange={(to) => router.post(`/news/${post.id}/status`, { status: to }, { preserveScroll: true })} />
                    {can.manage && (
                        <div className="flex gap-2">
                            <Button variant="secondary" size="sm" href={`/news/${post.id}/edit`}>
                                Ubah
                            </Button>
                            {post.status !== 'published' && (
                                <Button variant="danger" size="sm" onClick={() => window.confirm('Hapus berita ini?') && router.delete(`/news/${post.id}`)}>
                                    Hapus
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                <h1 className="text-3xl font-extrabold leading-tight tracking-tight">{post.title}</h1>
                <p className="mt-2 text-sm text-[#5B6779]">
                    {post.published_at ?? 'Belum terbit'}
                    {post.author ? ` · ${post.author}` : ''}
                    {post.activity && (
                        <>
                            {' · '}
                            <Link href={`/activities/${post.activity.id}`} className="font-semibold text-[#B3202A] hover:underline">
                                {post.activity.title}
                            </Link>
                        </>
                    )}
                </p>

                {post.cover && <img src={post.cover} alt="" className="mt-6 w-full rounded-lg object-cover" />}
                {post.excerpt && <p className="mt-6 text-lg font-medium leading-relaxed text-[#3B4658]">{post.excerpt}</p>}
                {/* Isi berita berupa teks biasa; ditampilkan sebagai teks (bukan HTML) sehingga aman dari XSS. */}
                <div className="mt-6 whitespace-pre-line text-base leading-relaxed">{post.body || <span className="text-[#8A94A4]">Isi berita belum ditulis.</span>}</div>
            </article>
        </AppLayout>
    );
}
