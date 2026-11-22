import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card, PageHeader } from '@/Components/ui';

export default function Dashboard({ stats, recent, access, last_login_at, upcoming = [], news = [], my_loans = [], promos = [] }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isSuper = auth.permissions.includes('*');
    const firstName = user.name.split(' ')[0];

    return (
        <AppLayout>
            <Head title="Dashboard" />
            <PageHeader
                title={`Halo, ${firstName}`}
                description={user.roles.length ? `Masuk sebagai ${user.roles.join(', ')}.` : 'Akun Anda belum memiliki peran.'}
            />

            {stats.length > 0 && (
                <dl className="mb-8 grid divide-y divide-[#DDE2E9] overflow-hidden rounded-lg border border-[#DDE2E9] bg-white sm:grid-flow-col sm:auto-cols-fr sm:divide-x sm:divide-y-0">
                    {stats.map((s) => (
                        <div key={s.label} className="px-6 py-5">
                            <dt className="text-sm font-medium text-[#5B6779]">{s.label}</dt>
                            <dd className="mt-1 text-3xl font-extrabold tabular-nums tracking-tight">{s.value}</dd>
                        </div>
                    ))}
                </dl>
            )}

            {upcoming.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-base font-bold">Kegiatan mendatang</h2>
                    <ul className="divide-y divide-[#DDE2E9] rounded-lg border border-[#DDE2E9] bg-white">
                        {upcoming.map((a) => (
                            <li key={a.id}>
                                <Link href={`/activities/${a.id}`} className="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-[#F7F8FA]">
                                    <span>
                                        <span className="block font-semibold">{a.title}</span>
                                        <span className="block text-xs text-[#5B6779]">
                                            {a.when}
                                            {a.location ? `, ${a.location}` : ''}
                                        </span>
                                    </span>
                                    <Badge>{a.division ?? 'Umum'}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {promos.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-base font-bold">Promo dari mitra</h2>
                    <ul className="divide-y divide-[#DDE2E9] rounded-lg border border-[#DDE2E9] bg-white">
                        {promos.map((p) => (
                            <li key={p.id}>
                                <Link href="/partners/promotions" className="block px-5 py-3 hover:bg-[#F7F8FA]">
                                    <span className="block font-semibold">{p.title}</span>
                                    <span className="block text-xs text-[#5B6779]">
                                        {p.partner} · berlaku sampai {p.until}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {my_loans.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-base font-bold">Peminjaman saya</h2>
                    <ul className="divide-y divide-[#DDE2E9] rounded-lg border border-[#DDE2E9] bg-white">
                        {my_loans.map((l) => (
                            <li key={l.id}>
                                <Link href={`/inventory/loans/${l.id}`} className="flex flex-wrap items-start justify-between gap-2 px-5 py-3 hover:bg-[#F7F8FA]">
                                    <span>
                                        <span className="block font-semibold">{l.items}</span>
                                        <span className="block text-xs text-[#5B6779]">
                                            {l.code} · kembali paling lambat {l.due}
                                        </span>
                                    </span>
                                    <Badge tone={l.overdue ? 'red' : 'neutral'}>{l.overdue ? 'Terlambat' : l.status}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {news.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-base font-bold">Berita terbaru</h2>
                    <ul className="divide-y divide-[#DDE2E9] rounded-lg border border-[#DDE2E9] bg-white">
                        {news.map((n) => (
                            <li key={n.id}>
                                <Link href={`/news/${n.id}`} className="block px-5 py-3 hover:bg-[#F7F8FA]">
                                    <span className="block font-semibold">{n.title}</span>
                                    <span className="block text-xs text-[#5B6779]">
                                        {n.published_at}
                                        {n.excerpt ? `, ${n.excerpt}` : ''}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                {recent && (
                    <Card className="lg:col-span-2">
                        <h2 className="border-b border-[#DDE2E9] px-5 py-3.5 text-base font-bold">Aktivitas terbaru</h2>
                        {recent.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-[#5B6779]">Belum ada aktivitas tercatat.</p>
                        ) : (
                            <ul className="divide-y divide-[#EEF1F5]">
                                {recent.map((r) => (
                                    <li key={r.id} className="px-5 py-3">
                                        <p className="text-sm font-medium">{r.description}</p>
                                        <p className="mt-0.5 text-xs text-[#5B6779]">
                                            {r.user_label}, {r.created_at}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                )}

                <div className={recent ? 'space-y-6' : 'space-y-6 lg:col-span-3 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0'}>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">Akun Anda</h2>
                        <dl className="mt-3 space-y-2.5 text-sm">
                            <div>
                                <dt className="text-xs text-[#5B6779]">Nomor Anggota</dt>
                                <dd className="font-semibold tabular-nums">{user.nomor_anggota}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-[#5B6779]">Peran</dt>
                                <dd className="mt-1 flex flex-wrap gap-1.5">
                                    {user.roles.length ? user.roles.map((r) => <Badge key={r}>{r}</Badge>) : <span>-</span>}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-[#5B6779]">Login terakhir</dt>
                                <dd className="font-semibold">{last_login_at ?? '-'}</dd>
                            </div>
                        </dl>
                    </Card>

                    <Card className="p-5">
                        <h2 className="text-base font-bold">Akses Anda</h2>
                        {isSuper ? (
                            <p className="mt-3 text-sm text-[#5B6779]">Super Admin memiliki akses penuh ke seluruh fitur.</p>
                        ) : access.length === 0 ? (
                            <p className="mt-3 text-sm text-[#5B6779]">Belum ada fitur pengelolaan yang diizinkan untuk peran Anda.</p>
                        ) : (
                            <div className="mt-3 space-y-3">
                                {access.map((g) => (
                                    <div key={g.label}>
                                        <p className="text-sm font-semibold">{g.label}</p>
                                        <ul className="mt-1 list-disc space-y-0.5 pl-5 text-xs text-[#5B6779]">
                                            {g.items.map((i) => (
                                                <li key={i}>{i}</li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
