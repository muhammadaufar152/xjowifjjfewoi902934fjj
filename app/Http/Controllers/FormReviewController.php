<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\FormReview;
use App\Models\Step;
use App\Models\BpoUploadedFile; // <-- pastikan model & tabelnya ada
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class FormReviewController extends Controller
{
    /* ======================== LISTING ======================== */
    public function index()
    {
        $reviews = FormReview::with([
            'steps' => function ($q) {
                $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            },
            'bpoUser',
            'bpoUploads',
        ])->latest('id')->get();

        $reviews->each(function ($r) {
            $r->lastStep = ($r->steps ?? collect())->first();
        });

        return view('pages.form_review.index', compact('reviews'));
    }

    /* ======================== CREATE ======================== */
    public function create()
    {
        return view('pages.form_review.create');
    }

    /* ======================== STORE ======================== */
    public function store(Request $request)
    {
        $rules = [
            'tanggal_masuk'      => ['required','date'],
            'jenis_permohonan'   => ['required', Rule::in(['Baru','Revisi'])],
            'latar_belakang'     => ['nullable','string'],
            'usulan_revisi'      => ['nullable','string'],
            'jenis_dokumen'      => ['required','string'],
            'klasifikasi_siklus' => ['required','string'],
            'nama_dokumen'       => $request->jenis_permohonan === 'Baru' ? ['nullable','string'] : ['required','string'],
            'no_dokumen'         => $request->jenis_permohonan === 'Baru' ? ['nullable','string'] : ['required','string'],
            'level_dokumen'      => ['nullable','string'],
            'perihal'            => ['nullable','string'],
            'status'             => ['nullable','string'],
            'tanggal_approval'   => ['nullable','date'],
            'lampiran'           => ['nullable','file','mimes:pdf,doc,docx,xlsx,jpg,jpeg,png'],
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')->store('lampiran', 'public');
        }

        $validated['bpo_id']        = auth()->id();
        $validated['status']        = 'pending';
        $validated['current_stage'] = 'officer';

        $review = FormReview::create($validated);

        Step::create([
            'form_review_id' => $review->id,
            'tahapan'        => 'officer',
            'status'         => 'Pending',
            'tanggal'        => now(),
            'verifikator'    => auth()->user()->name,
            'keterangan'     => 'Dokumen dikirim untuk approval officer',
        ]);

        return redirect()->route('form_review.raw')->with('success', 'Form berhasil dibuat');
    }

    /* ======================== SHOW/EDIT/UPDATE ======================== */
    public function show($id)
    {
        $review = FormReview::with(['steps', 'bpoUploads'])->findOrFail($id);
        return view('pages.form_review.show', compact('review'));
    }

    public function edit($id)
    {
        $review = FormReview::with(['steps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
        }])->findOrFail($id);

        return view('pages.form_review.edit', compact('review'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'tanggal_masuk'       => ['required','date'],
            'jenis_permohonan'    => ['required', Rule::in(['Baru','Revisi'])],
            'latar_belakang'      => ['nullable','string'],
            'usulan_revisi'       => ['nullable','string'],
            'jenis_dokumen'       => ['required','string'],
            'klasifikasi_siklus'  => ['required','string'],
            'nama_dokumen'        => $request->jenis_permohonan === 'Baru' ? ['nullable','string'] : ['required','string'],
            'no_dokumen'          => $request->jenis_permohonan === 'Baru' ? ['nullable','string'] : ['required','string'],
            'level_dokumen'       => ['nullable','string'],
            'perihal'             => ['nullable','string'],
            'status'              => ['nullable','string'],
            'tanggal_approval'    => ['nullable','date'],
            'lampiran'            => ['nullable','file','mimes:pdf,doc,docx,xlsx,jpg,jpeg,png'],
            'keterangan_resubmit' => ['nullable','string','max:2000'],
        ];

        $validated = $request->validate($rules);

        $review = FormReview::with(['steps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
        }])->findOrFail($id);

        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran')->store('lampiran', 'public');
        }

        $review->update($validated);

        if ($request->has('resubmit')) {
            $last = $review->steps->first();

            $statusNow = strtolower($review->status ?? '');
            $tahapLast = strtolower($last->tahapan ?? '');
            $statLast  = strtolower($last->status ?? '');

            $allowResubmit =
                ($tahapLast === 'officer' && in_array($statLast, ['tidak setuju','return','ditolak'])) ||
                ($tahapLast === 'manager' && in_array($statLast, ['tidak setuju','return'])) ||
                ($statusNow === 'return');

            if (!$allowResubmit) {
                return redirect()->route('form_review.raw')
                    ->with('danger', 'Resubmit hanya tersedia ketika dokumen terakhir DITOLAK/RETURN.');
            }

            $review->steps()->create([
                'tahapan'     => 'bpo',
                'status'      => 'Resubmit',
                'keterangan'  => $request->input('keterangan_resubmit', 'BPO melakukan perbaikan & resubmit'),
                'verifikator' => auth()->user()->name,
                'tanggal'     => now(),
            ]);

            $review->steps()->create([
                'tahapan'     => 'officer',
                'status'      => 'Pending',
                'keterangan'  => 'Resubmit dari BPO',
                'verifikator' => 'Officer',
                'tanggal'     => now(),
            ]);

            $review->update([
                'status'        => 'pending',
                'current_stage' => 'officer',
            ]);

            return redirect()->route('form_review.raw')
                ->with('success', 'Perbaikan terkirim. Menunggu persetujuan Officer.');
        }

        return redirect()->route('form_review.raw')->with('success', 'Form berhasil diperbarui');
    }

    /* ======================== DELETE ======================== */
    public function destroy($id)
    {
        $review = FormReview::findOrFail($id);
        $review->delete();

        return redirect()->route('form_review.raw')->with('success', 'Form berhasil dihapus');
    }

    /* ======================== DOWNLOAD LAMPIRAN ======================== */
    public function downloadFile($id)
    {
        $review = FormReview::findOrFail($id);

        if (!$review->lampiran || !Storage::disk('public')->exists($review->lampiran)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($review->lampiran);
    }

    /* ======================== VIEW PDF ======================== */
    public function viewPdf($id)
    {
        $review = FormReview::with('bpoUser')->findOrFail($id);

        if (!empty($review->pdf_path) && Storage::disk('public')->exists($review->pdf_path)) {
            return response()->file(Storage::disk('public')->path($review->pdf_path));
        }

        $meta = is_array($review->pdf_meta ?? null) ? $review->pdf_meta : [];

        try {
            if (!view()->exists('pdf.form_review_full')) {
                throw new \RuntimeException("View [pdf.form_review_full] tidak ditemukan. Letakkan file di resources/views/pdf/form_review_full.blade.php");
            }

            $html = view('pdf.form_review_full', [
                'review' => $review,
                'meta'   => $meta,
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            return $pdf->stream("form-review-{$review->id}.pdf");
        } catch (\Throwable $e) {
            Log::error('Preview PDF gagal', [
                'review_id' => $review->id,
                'error'     => $e->getMessage(),
            ]);
            return back()->with('pdf_error', 'Preview PDF gagal: '.$e->getMessage());
        }
    }

    /* ======================== GENERATE & SIMPAN PDF ======================== */
    public function generatePdf(Request $request, $id)
    {
        $review = FormReview::with('bpoUser')->findOrFail($id);

        $request->validate([
            'action'                          => 'required|in:generate',
            'pdf_meta.nama_dokumen'           => 'nullable|string|max:255',
            'pdf_meta.no_dokumen'             => 'nullable|string|max:255',
            'pdf_meta.jenis_dokumen'          => 'nullable|string|max:255',
            'pdf_meta.klasifikasi_siklus'     => 'nullable|string|max:255',
            'pdf_meta.bpo'                    => 'nullable|string|max:255',
            'pdf_meta.tanggal_permohonan'     => 'nullable|date',
            'review_items'                    => 'nullable|array',
            'review_items.*'                  => 'nullable|string|max:2000',
            'rekomendasi'                     => 'nullable|string',
            'exec_summary.judul'              => 'nullable|string|max:255',
            'exec_summary.ruang_lingkup'      => 'nullable|array',
            'exec_summary.ruang_lingkup.*'    => 'nullable|string|max:2000',
            'exec_summary.ketentuan_dicabut'  => 'nullable|string',
            'exec_summary.tanggal_berlaku'    => 'nullable|date',
            'exec_summary.lain_lain'          => 'nullable|string',
            'signers.dibuat.nama'             => 'nullable|string|max:255',
            'signers.dibuat.jabatan'          => 'nullable|string|max:255',
            'signers.ditinjau.nama'           => 'nullable|string|max:255',
            'signers.ditinjau.jabatan'        => 'nullable|string|max:255',
            'signers.disetujui.nama'          => 'nullable|string|max:255',
            'signers.disetujui.jabatan'       => 'nullable|string|max:255',
            'lokasi_tanggal'                  => 'nullable|string|max:255',
        ]);

        $reviewForView = clone $review;
        $reviewForView->setAttribute('review_items',    $request->input('review_items', []));
        $reviewForView->setAttribute('rekomendasi',     $request->input('rekomendasi'));
        $reviewForView->setAttribute('exec_summary',    $request->input('exec_summary', []));
        $reviewForView->setAttribute('signers',         $request->input('signers', []));
        $reviewForView->setAttribute('lokasi_tanggal',  $request->input('lokasi_tanggal'));

        try {
            if (!view()->exists('pdf.form_review_full')) {
                throw new \RuntimeException("View [pdf.form_review_full] tidak ditemukan. Letakkan file di resources/views/pdf/form_review_full.blade.php");
            }

            $meta = $request->input('pdf_meta', []);

            $html = view('pdf.form_review_full', [
                'review' => $reviewForView,
                'meta'   => $meta,
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4','portrait');

            $nextVersion = 1;
            if (!empty($review->pdf_path) && preg_match('/v(\d+)\.pdf$/i', $review->pdf_path, $m)) {
                $nextVersion = ((int) $m[1]) + 1;
            }

            $dir  = "reviews/{$review->id}/generated";
            $file = "review-{$review->id}-v{$nextVersion}.pdf";
            $path = "$dir/$file";

            Storage::disk('public')->makeDirectory($dir);
            Storage::disk('public')->put($path, $pdf->output());

            $review->pdf_path = $path;
            $review->save();

            $backRoute = 'approval.officer.show';
            $stage = strtolower($review->current_stage ?? '');
            if ($stage === 'manager') $backRoute = 'approval.manager.show';
            if ($stage === 'avp' || $review->status === 'verifikasi 2') $backRoute = 'approval.avp.show';

            return redirect()
                ->route($backRoute, $review->id)
                ->with('success', 'PDF berhasil disimpan. Gunakan tombol "" untuk membuka.');

        } catch (\Throwable $e) {
            Log::error('Generate PDF gagal', [
                'review_id' => $review->id,
                'error'     => $e->getMessage(),
            ]);
            return back()->with('pdf_error', 'Gagal membuat PDF: '.$e->getMessage())->withInput();
        }
    }

    /* ======================== UPLOAD FILE OLEH BPO ======================== */
    /* ======================== UPLOAD FILE OLEH BPO ======================== */
    public function uploadByBpo(Request $request, $id)
    {
        $review = FormReview::findOrFail($id);
    
        // validasi 2 input file (20MB)
        $request->validate([
            'file1' => ['nullable','file','max:20480'],
            'file2' => ['nullable','file','max:20480'],
            'ket1'  => ['nullable','string','max:255'],
            'ket2'  => ['nullable','string','max:255'],
        ]);
    
        $uploaded = 0;
    
        foreach ([1,2] as $idx) {
            $file = $request->file('file'.$idx);
            if (!$file) continue;
    
            // simpan ke disk public
            $storedPath = $file->store('bpo_uploads', 'public');
    
            BpoUploadedFile::create([
                'form_review_id' => $review->id,
                'path'           => $storedPath,
                'original_name'  => $file->getClientOriginalName(),
                'keterangan'     => $request->input('ket'.$idx),
                'uploaded_by'    => auth()->id(),
                'uploaded_role'  => 'bpo',
            ]);
    
            $uploaded++;
        }
    
        if ($uploaded === 0) {
            return back()->with('pdf_error', 'Pilih minimal satu file untuk diunggah.');
        }
    
        // === KEEP ONLY LAST 2 FILES ===
        $all = BpoUploadedFile::where('form_review_id', $review->id)
                ->orderBy('created_at', 'desc')
                ->get();
    
        if ($all->count() > 2) {
            foreach ($all->slice(2) as $old) {
                if ($old->path && \Storage::disk('public')->exists($old->path)) {
                    \Storage::disk('public')->delete($old->path);
                }
                $old->delete();
            }
        }
    
        // (opsional) kalau minimal 2 file ada -> set status 'selesai'
        if ($all->take(2)->count() >= 2) {
            $review->update(['status' => 'selesai']);
        }
    
        return back()->with('success', "Berhasil mengunggah {$uploaded} file.");
    }
    


    /* ======================== STREAM FILE BPO (Lihat) ======================== */
    public function streamBpoFile(BpoUploadedFile $file)
    {
        if (!$file->path || !Storage::disk('public')->exists($file->path)) {
            abort(404);
        }

        $downloadName = $file->original_name ?: basename($file->path);
        $absolutePath = Storage::disk('public')->path($file->path);

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"'
        ]);
    }
}
