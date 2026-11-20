<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ArchiveDocument;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Arsip dokumentasi organisasi: notulen, surat, SK, proposal, laporan. Berkas privat. */
class ArchiveController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
            'period'   => (string) $request->query('period', ''),
            'year'     => (string) $request->query('year', ''),
        ];

        $docs = ArchiveDocument::query()
            ->visibleTo($user)
            ->with(['activity:id,code,title', 'period:id,name'])
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('title', 'like', $s)->orWhere('document_number', 'like', $s)->orWhere('description', 'like', $s);
            }))
            ->when(array_key_exists($filters['category'], ArchiveDocument::CATEGORIES), fn ($q) => $q->where('category', $filters['category']))
            ->when(ctype_digit($filters['period']) && $filters['period'] !== '', fn ($q) => $q->where('period_id', (int) $filters['period']))
            ->when(preg_match('/^\d{4}$/', $filters['year']), fn ($q) => $q->whereYear('document_date', (int) $filters['year']))
            ->orderByDesc('document_date')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ArchiveDocument $d) => [
                'id'              => $d->id,
                'title'           => $d->title,
                'category'        => $d->category,
                'category_label'  => ArchiveDocument::CATEGORIES[$d->category] ?? $d->category,
                'document_number' => $d->document_number,
                'document_date'   => $d->document_date?->format('d/m/Y'),
                'description'     => $d->description,
                'activity'        => $d->activity?->title,
                'period'          => $d->period?->name,
                'original_name'   => $d->original_name,
                'size'            => $this->humanSize($d->size_bytes),
                'access'          => $d->access,
                'access_label'    => ArchiveDocument::ACCESS[$d->access] ?? $d->access,
            ]);

        return Inertia::render('Archive/Index', [
            'documents'  => $docs,
            'filters'    => $filters,
            'categories' => ArchiveDocument::CATEGORIES,
            'periods'    => Period::orderByDesc('start_date')->get(['id', 'name']),
            'can_manage' => $user->can('archive.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Archive/Form', ['document' => null, 'options' => $this->options($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, fileRequired: true);

        $file = $request->file('file');
        $doc = ArchiveDocument::create($this->withoutFile($data) + [
            'file_path'     => $file->store('archive/'.date('Y'), 'local'),
            'original_name' => $this->safeName($file->getClientOriginalName()),
            'mime'          => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
            'uploaded_by'   => $request->user()->id,
        ]);

        return to_route('archive.index')->with('success', "Dokumen \"{$doc->title}\" diarsipkan.");
    }

    public function edit(Request $request, ArchiveDocument $document): Response
    {
        return Inertia::render('Archive/Form', [
            'document' => [
                'id' => $document->id, 'title' => $document->title, 'category' => $document->category,
                'document_number' => $document->document_number, 'document_date' => $document->document_date?->format('Y-m-d'),
                'description' => $document->description, 'activity_id' => $document->activity_id,
                'period_id' => $document->period_id, 'access' => $document->access, 'original_name' => $document->original_name,
            ],
            'options' => $this->options($request),
        ]);
    }

    public function update(Request $request, ArchiveDocument $document): RedirectResponse
    {
        $data = $this->validated($request, fileRequired: false);
        $extra = [];

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($document->file_path);
            $file = $request->file('file');
            $extra = [
                'file_path'     => $file->store('archive/'.date('Y'), 'local'),
                'original_name' => $this->safeName($file->getClientOriginalName()),
                'mime'          => $file->getMimeType(),
                'size_bytes'    => $file->getSize(),
            ];
        }

        $document->update($this->withoutFile($data) + $extra);

        return to_route('archive.index')->with('success', 'Dokumen diperbarui.');
    }

    public function destroy(ArchiveDocument $document): RedirectResponse
    {
        $document->delete();   // soft delete: berkas tetap tersimpan sampai dibersihkan manual

        return back()->with('success', 'Dokumen dihapus dari arsip.');
    }

    public function download(Request $request, ArchiveDocument $document): StreamedResponse|RedirectResponse
    {
        abort_unless($request->user()->can('view', $document), 403);

        if (! Storage::disk('local')->exists($document->file_path)) {
            return back()->with('error', 'Berkas tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function validated(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'title'           => ['required', 'string', 'max:150'],
            'category'        => ['required', Rule::in(array_keys(ArchiveDocument::CATEGORIES))],
            'document_number' => ['nullable', 'string', 'max:60'],
            'document_date'   => ['nullable', 'date'],
            'description'     => ['nullable', 'string', 'max:500'],
            'activity_id'     => ['nullable', 'integer', 'exists:activities,id'],
            'period_id'       => ['nullable', 'integer', 'exists:periods,id'],
            'access'          => ['required', Rule::in(array_keys(ArchiveDocument::ACCESS))],
            'file'            => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png', 'max:10240'],
        ], [
            'title.required'    => 'Judul dokumen wajib diisi.',
            'file.required'     => 'Pilih berkas yang akan diarsipkan.',
            'file.mimes'        => 'Format yang diizinkan: PDF, DOC(X), XLS(X), PPT(X), JPG, PNG.',
            'file.max'          => 'Ukuran berkas maksimal 10 MB.',
        ]);
    }

    private function withoutFile(array $data): array
    {
        unset($data['file']);

        return $data;
    }

    private function options(Request $request): array
    {
        return [
            'categories' => ArchiveDocument::CATEGORIES,
            'access'     => ArchiveDocument::ACCESS,
            'periods'    => Period::orderByDesc('start_date')->get(['id', 'name']),
            'activities' => Activity::visibleTo($request->user())->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title']),
        ];
    }

    private function safeName(string $name): string
    {
        $name = preg_replace('/[^\w\-. ()]+/u', '_', basename($name));

        return mb_substr($name ?: 'dokumen', 0, 150);
    }

    private function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024    => number_format($bytes / 1024, 0).' KB',
            default           => $bytes.' B',
        };
    }
}
