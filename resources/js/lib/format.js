const idr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

export const rupiah = (n) => (n === null || n === undefined || n === '' ? '-' : idr.format(n));

export const statusTone = (status) =>
    ({ draft: 'neutral', proposed: 'amber', approved: 'green', rejected: 'red', completed: 'green', closed: 'neutral', cancelled: 'red' })[status] ?? 'neutral';
