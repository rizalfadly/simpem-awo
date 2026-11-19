import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Avatar from '@/Components/Avatar';
import { Badge, PageHeader, Select } from '@/Components/ui';
import { useCan } from '@/lib/can';

function Person({ e, link }) {
    const body = (
        <span className="flex items-center gap-3">
            <Avatar name={e.name} src={e.photo_url} className="h-9 w-9 rounded-full" textClass="text-xs" />
            <span>
                <span className="block text-sm font-semibold leading-tight">{e.name}</span>
                <span className="block text-xs text-[#5B6779]">{e.position}</span>
            </span>
        </span>
    );
    return link ? (
        <Link href={`/members/${e.member_id}`} className="block rounded-md px-2 py-1.5 hover:bg-[#F2F4F7]">
            {body}
        </Link>
    ) : (
        <div className="px-2 py-1.5">{body}</div>
    );
}

export default function Index({ periods, period, leadership, divisions }) {
    const can = useCan();
    const link = can('members.view');

    return (
        <AppLayout>
            <Head title="Struktur Pengurus" />
            <PageHeader
                title="Struktur Pengurus"
                description="Susunan pengurus per periode kepengurusan."
                actions={
                    periods.length > 0 && (
                        <Select
                            aria-label="Pilih periode"
                            value={period?.id ?? ''}
                            onChange={(e) => router.get('/struktur', { period: e.target.value }, { preserveState: true, replace: true })}
                            className="w-52"
                        >
                            {periods.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.name}
                                    {p.is_current ? ' (berjalan)' : ''}
                                </option>
                            ))}
                        </Select>
                    )
                }
            />

            {!period ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-12 text-center text-sm text-[#5B6779]">
                    Belum ada periode kepengurusan. Sekretaris dapat membuatnya di menu Divisi, Jabatan, Periode.
                </p>
            ) : (
                <div className="space-y-10">
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <h2 className="text-lg font-extrabold tracking-tight">Pimpinan</h2>
                            <Badge tone={period.is_current ? 'green' : 'neutral'}>{period.name}</Badge>
                        </div>
                        {leadership.length === 0 ? (
                            <p className="text-sm text-[#5B6779]">Belum ada pimpinan yang ditetapkan.</p>
                        ) : (
                            <div className="grid gap-x-8 gap-y-1 rounded-lg border border-[#DDE2E9] bg-white p-3 sm:grid-cols-2">
                                {leadership.map((e) => (
                                    <Person key={e.id} e={e} link={link} />
                                ))}
                            </div>
                        )}
                    </section>

                    <section>
                        <h2 className="mb-3 text-lg font-extrabold tracking-tight">Divisi</h2>
                        {divisions.length === 0 ? (
                            <p className="text-sm text-[#5B6779]">Belum ada divisi aktif.</p>
                        ) : (
                            <div className="grid gap-x-10 gap-y-8 md:grid-cols-2">
                                {divisions.map((d) => (
                                    <div key={d.id}>
                                        <h3 className="border-b-2 border-[#16202E] pb-1.5 text-base font-bold">{d.name}</h3>
                                        {d.entries.length === 0 ? (
                                            <p className="px-2 py-3 text-sm text-[#8A94A4]">Belum ada pengurus.</p>
                                        ) : (
                                            <div className="mt-1.5">
                                                {d.entries.map((e) => (
                                                    <Person key={e.id} e={e} link={link} />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            )}
        </AppLayout>
    );
}
