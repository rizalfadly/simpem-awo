import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card, PageHeader, Pagination } from '@/Components/ui';
import { phaseLabel, phaseTone } from '@/Components/partner/PartnerPromos';

export default function Promotions({ promotions, viewer }) {
    return (
        <AppLayout>
            <Head title="Promosi Mitra" />
            <PageHeader title="Promosi mitra" description={viewer ? 'Seluruh promosi, termasuk draf dan yang sudah berakhir.' : 'Penawaran dari mitra dan UMKM yang sedang berlaku.'} />

            {promotions.data.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-14 text-center text-sm text-[#5B6779]">Belum ada promosi yang berlaku.</p>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {promotions.data.map((p) => (
                        <Card key={p.id} className="p-5">
                            <div className="flex items-start justify-between gap-2">
                                <h2 className="text-base font-bold leading-tight">{p.title}</h2>
                                <Badge tone={phaseTone[p.phase]}>{phaseLabel[p.phase]}</Badge>
                            </div>
                            <p className="mt-1 text-sm">
                                <Link href={`/partners/${p.partner.id}`} className="font-semibold text-[#B3202A] hover:underline">
                                    {p.partner.name}
                                </Link>
                                <span className="text-[#5B6779]"> · {p.period}</span>
                            </p>
                            {p.description && <p className="mt-3 whitespace-pre-line text-sm">{p.description}</p>}
                            {p.terms && <p className="mt-2 text-xs text-[#5B6779]">Syarat & ketentuan: {p.terms}</p>}
                            {viewer && <p className="mt-2 text-xs text-[#8A94A4]">{p.audience_label}</p>}
                        </Card>
                    ))}
                </div>
            )}
            <div className="mt-4">
                <Pagination links={promotions.links} />
            </div>
        </AppLayout>
    );
}
