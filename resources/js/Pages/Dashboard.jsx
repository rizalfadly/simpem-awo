import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card, PageHeader } from '@/Components/ui';

export default function Dashboard({ stats, recent, access, last_login_at }) {
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
                            <dd className="mt-1 text-4xl font-extrabold tabular-nums tracking-tight">{s.value}</dd>
                        </div>
                    ))}
                </dl>
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
