import { Link } from '@inertiajs/react';
import PublicLayout from '@/Components/PublicLayout';

export default function NewsDetail({ post }) {
    return (
        <PublicLayout title={post.title}>
            {/* Header Artikel Gelap Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
                    <Link
                        href="/berita"
                        className="inline-flex items-center gap-1.5 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-xs font-bold tracking-wider text-[#36D8E8] uppercase transition hover:bg-[#36D8E8]/20"
                    >
                        <span aria-hidden="true">←</span>
                        <span>Kembali ke Berita</span>
                    </Link>

                    <h1 className="mt-4 text-3xl font-black leading-tight tracking-tight sm:text-5xl">
                        {post.title}
                    </h1>

                    <div className="mt-4 flex flex-wrap items-center gap-2 text-xs font-medium text-[#94A3B8] sm:text-sm">
                        {post.published_at && <span className="tabular-nums">{post.published_at}</span>}
                        {post.activity && (
                            <>
                                <span className="text-[#7A8B8C]" aria-hidden="true">
                                    ·
                                </span>
                                <Link
                                    href={`/kegiatan/${post.activity.slug}`}
                                    className="inline-flex items-center gap-1 rounded-md border border-[#36D8E8]/30 bg-white/[0.04] px-2.5 py-0.5 font-bold text-[#36D8E8] transition hover:border-[#36D8E8] hover:bg-[#36D8E8]/10"
                                >
                                    <span>Kegiatan: {post.activity.title}</span>
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </section>

            {/* Isi Artikel */}
            <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
                <div className="rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm sm:p-8">
                    {post.cover_full && (
                        <img
                            src={post.cover_full}
                            alt=""
                            className="mb-7 w-full rounded-xl border border-[#EEF1F5] object-cover"
                        />
                    )}

                    {post.excerpt && (
                        <p className="border-l-4 border-[#36D8E8] bg-[#F4F9FA] py-3 pl-4 pr-4 text-base font-semibold leading-relaxed text-[#08090B] sm:text-lg">
                            {post.excerpt}
                        </p>
                    )}

                    {/* Teks biasa, ditampilkan sebagai teks (bukan HTML) sehingga aman dari penyisipan skrip. */}
                    <div className="mt-6 whitespace-pre-line text-base leading-relaxed text-[#3B4658]">
                        {post.body}
                    </div>
                </div>
            </article>
        </PublicLayout>
    );
}