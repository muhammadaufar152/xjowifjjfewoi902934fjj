<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormReview;
use App\Models\FormReviewDocument;
use App\Models\FormReviewHistory;
use App\Models\ExecutiveSummary;
use App\Models\ExecutiveSummaryHistory;
use Illuminate\Support\Facades\Storage; // <-- tambah
use Illuminate\Support\Facades\DB;       // <-- tambah

class ManagerApprovalController extends Controller
{
    public function index()
    {
        // Aman: kalau relasi lastStep BELUM ada, pakai whereHas('steps')
        $reviews = FormReview::with(['bpoUser', 'steps'])
            ->where(function ($q) {
                $q->whereHas('steps', function ($sq) {
                    $sq->where('tahapan', 'manager')
                       ->whereIn('status', ['Pending', 'pending']);
                })
                // tampilkan juga dokumen yang sudah final/sirkulir agar Manager tetap bisa "View" dan upload file sirkulir
                ->orWhereIn('status', ['selesai', 'Selesai', 'sirkulir', 'Sirkulir']); // <-- tambah Sirkulir
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('pages.approval.manager', compact('reviews'));
    }

    public function show($id)
    {
        $review = FormReview::with([
            'bpoUser',
            'steps' => function ($q) {
                $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            },
            'bpoUploads',
        ])->findOrFail($id);

        return view('pages.approval.manager-detail', compact('review'));
    }

    public function setujui(Request $request, $id)
    {
        $review = FormReview::findOrFail($id);

        $request->validate([
            'status'     => 'required|in:Setuju,Tidak Setuju',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        // log keputusan manager
        $review->steps()->create([
            'tahapan'     => 'manager',
            'status'      => $request->status, // Setuju / Tidak Setuju
            'keterangan'  => $request->keterangan,
            'verifikator' => 'Manager',
            'tanggal'     => now(),
        ]);

        if ($request->status === 'Setuju') {
            // kirim ke AVP
            $review->steps()->create([
                'tahapan'     => 'avp',
                'status'      => 'Pending',
                'keterangan'  => 'Menunggu verifikasi AVP',
                'verifikator' => 'AVP',
                'tanggal'     => now(),
            ]);

            $review->update([
                'status'        => 'verifikasi 2',
                'current_stage' => 'avp',
            ]);

            return redirect()
                ->route('approval.manager.index')
                ->with('success', 'Disetujui oleh Manager — diteruskan ke AVP (Verifikasi 2).');
        }

        // manager tidak setuju → balikan ke officer
        $review->steps()->create([
            'tahapan'     => 'officer',
            'status'      => 'Pending',
            'keterangan'  => 'Revisi dari Manager',
            'verifikator' => 'Officer',
            'tanggal'     => now(),
        ]);

        $review->update([
            'status'        => 'pending',
            'current_stage' => 'officer',
        ]);

        return redirect()
            ->route('approval.manager.index')
            ->with('danger', 'Dokumen dikembalikan oleh Manager (revisi ke Officer).');
    }

    public function approve(Request $request, $id)
    {
        return $this->setujui($request, $id);
    }

    /**
     * Tambahan aman: route /manager/{id}/tolak tidak 500
     * Akan memaksa status 'Tidak Setuju' dan reuse setujui().
     */
    public function tolak(Request $request, $id)
    {
        $request->merge(['status' => 'Tidak Setuju']);
        return $this->setujui($request, $id);
    }

    public function resubmit(Request $request, $id)
    {
        $review = FormReview::with('bpoUser')->findOrFail($id);

        $request->validate([
            'form_review_pdf'        => 'required|mimes:pdf|max:20480',
            'executive_summary_pdf'  => 'required|mimes:pdf|max:20480',
        ]);

        // arsip versi sebelumnya (jika ada)
        $currentDoc = method_exists($review, 'formReviewDocument') ? $review->formReviewDocument : null;
        if ($currentDoc) {
            FormReviewHistory::create([
                'form_review_id' => $review->id,
                'tahapan'        => 'manager',
                'hasil_review'   => $currentDoc->hasil_review ?? '',
                'rekomendasi'    => $currentDoc->rekomendasi ?? '',
                'uploaded_by'    => 'Manager',
                'uploaded_at'    => now(),
            ]);
        }

        $currentSummary = method_exists($review, 'executiveSummary') ? $review->executiveSummary : null;
        if ($currentSummary) {
            ExecutiveSummaryHistory::create([
                'form_review_id'     => $review->id,
                'tahapan'            => 'manager',
                'judul'              => $currentSummary->judul ?? '',
                'latar_belakang'     => $currentSummary->latar_belakang ?? '',
                'maksud_tujuan'      => $currentSummary->maksud_dan_tujuan ?? '',
                'ruang_lingkup'      => $currentSummary->ruang_lingkup ?? '',
                'hal_penting'        => $currentSummary->hal_penting ?? '',
                'ketentuan_dicabut'  => $currentSummary->ketentuan_dicabut ?? '',
                'tanggal_berlaku'    => $currentSummary->tanggal_berlaku ?? null,
                'lain_lain'          => $currentSummary->lain_lain ?? '',
                'disclaimer'         => $currentSummary->disclaimer_clause ?? '',
                'uploaded_by'        => 'Manager',
                'uploaded_at'        => now(),
            ]);
        }

        // upload versi manager
        $formReviewPath       = $request->file('form_review_pdf')->store('form_review_pdfs', 'public');
        $executiveSummaryPath = $request->file('executive_summary_pdf')->store('form_review_pdfs', 'public');

        // kalau relasi hasMany belum ada, buat manual via model
        if (method_exists($review, 'formReviewDocuments')) {
            $review->formReviewDocuments()->create([
                'nama_dokumen'       => $review->nama_dokumen,
                'nomor_dokumen'      => $review->no_dokumen,
                'jenis_dokumen'      => $review->jenis_dokumen,
                'klasifikasi_siklus' => $review->klasifikasi_siklus,
                'bpo'                => $review->bpoUser->name ?? '-',
                'file_path'          => $formReviewPath,
                'rekomendasi'        => '',
                'tahapan'            => 'manager',
            ]);
        }

        if (method_exists($review, 'executiveSummaries')) {
            $review->executiveSummaries()->create([
                'judul'             => '',
                'latar_belakang'    => '',
                'maksud_dan_tujuan' => '',
                'ruang_lingkup'     => '',
                'hal_penting'       => '',
                'ketentuan_dicabut' => '',
                'tanggal_berlaku'   => null,
                'lain_lain'         => '',
                'disclaimer_clause' => 'Executive Summary versi Manager.',
                'tahapan'           => 'manager',
                'file_path'         => $executiveSummaryPath,
            ]);
        }

        // tetap di verifikasi 2; antrean balik ke manager (pending)
        $review->update(['status' => 'verifikasi 2']);
        $review->steps()->create([
            'tahapan'     => 'manager',
            'status'      => 'Pending',
            'verifikator' => 'Manager',
            'tanggal'     => now(),
            'keterangan'  => 'Manager resubmit hasil review & executive summary',
        ]);

        return redirect()->route('approval.manager.index')
            ->with('success', 'File versi Manager berhasil di-upload. Silakan kirim ulang ke AVP.');
    }

    /* ===== NEW: Upload 1–2 file Sirkulir (status harus Sirkulir) ===== */
    public function uploadSirkulir(Request $request, $id)
    {
        $review = FormReview::findOrFail($id);

        // hanya boleh saat status = Sirkulir
        if (strtolower($review->status ?? '') !== 'sirkulir') {
            return back()->with('warning', 'Upload hanya tersedia saat status Sirkulir.');
        }

        $request->validate([
            'file1' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg|max:20480',
            'file2' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg|max:20480',
        ]);

        if (!$request->hasFile('file1') && !$request->hasFile('file2')) {
            return back()->with('warning', 'Pilih minimal satu file untuk diunggah.');
        }

        DB::transaction(function () use ($request, $review) {
            $dir = "sirkulir/{$review->id}";
            Storage::disk('public')->makeDirectory($dir);

            // file 1
            if ($request->hasFile('file1')) {
                if (!empty($review->sirkulir_file_1) && Storage::disk('public')->exists($review->sirkulir_file_1)) {
                    Storage::disk('public')->delete($review->sirkulir_file_1);
                }
                $ext = $request->file('file1')->getClientOriginalExtension();
                $p1  = $request->file('file1')->storeAs($dir, "sirkulir-1.".$ext, 'public');
                $review->sirkulir_file_1 = $p1;
            }

            // file 2
            if ($request->hasFile('file2')) {
                if (!empty($review->sirkulir_file_2) && Storage::disk('public')->exists($review->sirkulir_file_2)) {
                    Storage::disk('public')->delete($review->sirkulir_file_2);
                }
                $ext = $request->file('file2')->getClientOriginalExtension();
                $p2  = $request->file('file2')->storeAs($dir, "sirkulir-2.".$ext, 'public');
                $review->sirkulir_file_2 = $p2;
            }

            $review->save();
        });

        return back()->with('success', 'File sirkulir berhasil diunggah. File akan tampil di lampiran BPO.');
    }
}
