<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormReview;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OfficerApprovalController extends Controller
{
    /* ===== LIST ===== */
    public function index()
    {
        // load bpo user & seluruh steps supaya modal riwayat irit query
        $reviews = FormReview::with(['bpoUser', 'steps'])
            ->orderByDesc('updated_at')
            ->get();

        return view('pages.approval.officer', compact('reviews'));
    }

    /* ===== DETAIL ===== */
    public function show($id)
    {
        $review = FormReview::with([
            'steps' => function ($q) {
                $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            },
            'bpoUser'
        ])->findOrFail($id);

        // ====== TENTUKAN BOLEH AKSI (Officer) ======
        $last       = optional($review->steps)->first();
        $lastTahap  = strtolower($last->tahapan ?? '');
        $lastStatus = strtolower($last->status  ?? '');
        $statusG    = strtolower($review->status ?? '');
        $stageG     = strtolower($review->current_stage ?? '');

        // 1) last step = officer + pending/menunggu (termasuk saat status global Verifikasi 1)
        $caseLastOfficerPending = ($lastTahap === 'officer' && in_array($lastStatus, ['pending','menunggu']));

        // 2) legacy: status global pending & current_stage officer
        $caseStageOfficerPending = ($statusG === 'pending' && $stageG === 'officer');

        // 3) Verifikasi 1 dan last = manager (tidak setuju / return) -> balik ke Officer
        $caseManagerReturned = (
            in_array($statusG, ['verifikasi 1','verifikasi1','verifikasi-1']) &&
            $lastTahap === 'manager' &&
            in_array($lastStatus, ['tidak setuju','return'])
        );

        $canAct = $caseLastOfficerPending || $caseStageOfficerPending || $caseManagerReturned;

        return view('pages.approval.officer-detail', compact('review', 'canAct'));
    }

    /* ===== OFFICER SETUJU -> kirim ke MANAGER ===== */
    public function setujui(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:Setuju,Tidak Setuju',
            'keterangan'  => 'nullable|string|max:1000',
        ]);

        // kalau pilih "Tidak Setuju" lempar ke tolak()
        if (strtolower($request->status) !== 'setuju') {
            return $this->tolak($request, $id);
        }

        $review = FormReview::findOrFail($id);

        // catat keputusan Officer
        $review->steps()->create([
            'tahapan'     => 'officer',
            'status'      => 'Setuju',
            'keterangan'  => $request->keterangan,
            'verifikator' => auth()->user()->name ?? 'Officer',
            'tanggal'     => now(),
        ]);

        // lanjut antrean ke Manager
        $review->steps()->create([
            'tahapan'     => 'manager',
            'status'      => 'Pending',
            'keterangan'  => 'Menunggu penilaian Manager',
            'verifikator' => 'Manager',
            'tanggal'     => now(),
        ]);

        $review->update([
            'status'        => 'verifikasi 1',
            'current_stage' => 'manager',
        ]);

        return redirect()
            ->route('approval.officer.index')
            ->with('success', 'Disetujui oleh Officer — diteruskan ke Manager (Verifikasi 1).');
    }

    /* alias */
    public function approve(Request $request, $id)
    {
        return $this->setujui($request, $id);
    }

    /* ===== OFFICER TIDAK SETUJU -> kembalikan ke BPO ===== */
    public function tolak(Request $request, $id)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $review = FormReview::findOrFail($id);

        // catat penolakan oleh Officer
        $review->steps()->create([
            'tahapan'     => 'officer',
            'status'      => 'Tidak Setuju',
            'keterangan'  => $request->keterangan ?: 'Perlu perbaikan / penyusunan ulang Form Review',
            'verifikator' => auth()->user()->name ?? 'Officer',
            'tanggal'     => now(),
        ]);

        // antrean kembali ke BPO (BUKAN officer pending lagi)
        $review->steps()->create([
            'tahapan'     => 'bpo',
            'status'      => 'Pending',
            'keterangan'  => 'Perlu perbaikan / penyusunan ulang Form Review',
            'verifikator' => 'BPO User',
            'tanggal'     => now(),
        ]);

        $review->update([
            'status'        => 'return',
            'current_stage' => 'bpo',
        ]);

        return redirect()
            ->route('approval.officer.index')
            ->with('danger', 'Dokumen dikembalikan Officer (revisi ke BPO).');
    }

    /* tombol opsional kalau kamu sediakan */
    public function resubmit(Request $request, $id)
    {
        return redirect()->route('approval.officer.show', $id);
    }

    /* ====== NEW: Upload 1–2 file Sirkulir (status harus Sirkulir) ====== */
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