<?php

return [

    'finance' => [
        /*
         | Pemisahan tugas (governance). Jika true:
         |  - yang mengajukan laporan tidak boleh mereview atau menyetujuinya,
         |  - pereview tidak boleh menjadi penyetuju laporan yang sama,
         |  - penjawab sanggahan, pereview, dan pemutus harus orang berbeda dan bukan pengaju sanggahan.
         | Matikan (SIMPEM_FINANCE_SEPARATION=false) hanya jika organisasi terlalu kecil untuk memisahkan peran.
         */
        'separation_of_duties' => env('SIMPEM_FINANCE_SEPARATION', true),

        /*
         | Laporan bulanan baru boleh diajukan setelah bulannya berakhir (agar transaksi tidak terkunci di tengah bulan).
         | Ubah ke true (SIMPEM_FINANCE_EARLY_SUBMISSION=true) hanya untuk uji coba.
         */
        'allow_early_submission' => env('SIMPEM_FINANCE_EARLY_SUBMISSION', false),
    ],

];
