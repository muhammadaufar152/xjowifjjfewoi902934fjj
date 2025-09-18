<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // <-- tambah
use App\Models\FormReview;

class AvpApprovalController extends Controller
{
    /**
     * Daftar dokumen yang siap diproses AVP.
     */
    public function index()
    {
        $reviews = \App\Models\FormReview::with([
                'bpoUser',
                'steps' => function ($q) {
                    $q->orderBy('tanggal')->orderBy('created_at');
                },
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function ($r) {
                $statusNow = strtolower($r->status ?? '');

                // FINAL: selain 'selesai', anggap 'sirkulir' juga final
                $isDone = in_array($statusNow, ['selesai', 'sirkulir'], true);

                $stage = strtolower($r->current_stage ?? '');

                $last = ($r->steps ?? collect())
                    ->sortByDesc(function ($s) {
                        return $s->tanggal ?? $s->created_at;
                    })
                    ->first();

                $tahap  = strtolower($last->tahapan ?? '');
                $status = strtolower($last->status ?? '');

                $hasOfficerApproved = ($r->steps ?? collect())->contains(function ($s) {
                    return strtolower($s->tahapan ?? '') === 'officer'
                        && in_array(strtolower($s->status ?? ''), ['setuju','approve','approved'], true);
                });

                $hasManagerApproved = ($r->steps ?? collect())->contains(function ($s) {
                    return strtolower($s->tahapan ?? '') === 'manager'
                        && in_array(strtolower($s->status ?? ''), ['setuju','approve','approved'], true);
                });

                $hasAvpPending = ($r->steps ?? collect())->contains(function ($s) {
                    return strtolower($s->tahapan ?? '') === 'avp'
                        && in_array(strtolower($s->status ?? ''), ['pending','menunggu'], true);
                });

                $routedToAvp =
                    $stage === 'avp'
                    || ($tahap === 'manager' && in_array($status, ['setuju','approve','approved'], true))
                    || ($tahap === 'avp' && in_array($status, ['pending','menunggu'], true))
                    || $hasAvpPending;

                $eligible = ($hasOfficerApproved || $hasManagerApproved)
                    ? ($routedToAvp && $hasOfficerApproved && $hasManagerApproved)
                    : $routedToAvp;

                return $eligible || $isDone;
            })
            ->values();

        return view('pages.approval.avp', compact('reviews'));
    }

    /**
     * Detail AVP.
     */
    public function show($id)
    {
        $review = \App\Models\FormReview::with([
            'steps',
            'bpoUploads',      // <-- penting
            'bpoUser',
        ])->findOrFail($id);    
        
        $review = FormReview::with(['steps','bpoUser'])->findOrFail($id);
        return view('pages.approval.avp-detail', compact('review'));
    }

    /**
     * AVP setujui (final) -> ubah status menjadi "Sirkulir".
     */
    public function setujui(Request $request, $id)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $id) {
            $review = FormReview::with('steps')->findOrFail($id);

            // Step AVP: Setuju
            $review->steps()->create([
                'tahapan'     => 'avp',
                'status'      => 'Setuju',
                'keterangan'  => $request->keterangan,
                'verifikator' => auth()->user()->name ?? 'AVP',
                'tanggal'     => now(),
            ]);

            // Finalisasi -> status Sirkulir
            $review->update([
                'status'           => 'Sirkulir',
                'current_stage'    => 'sirkulir',   // tandai fase akhir sirkulir
                'tanggal_approval' => now(),
            ]);
        });

        return redirect()
            ->route('approval.avp.index')
            ->with('success', '✅ Disetujui oleh AVP — Status: Sirkulir');
    }

    /**
     * AVP tolak -> kembali ke Manager untuk perbaikan & bisa kirim ulang ke AVP.
     */
    public function tolak(Request $request, $id)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $id) {
            $review = FormReview::with('steps')->findOrFail($id);

            // Catat penolakan AVP
            $review->steps()->create([
                'tahapan'     => 'avp',
                'status'      => 'Tidak Setuju',
                'keterangan'  => $request->keterangan,
                'verifikator' => auth()->user()->name ?? 'AVP',
                'tanggal'     => now(),
            ]);

            // Enqueue ke Manager
            $review->steps()->create([
                'tahapan'     => 'manager',
                'status'      => 'Pending',
                'keterangan'  => 'Revisi dari AVP',
                'verifikator' => 'Manager',
                'tanggal'     => now(),
            ]);

            $review->update([
                'status'        => 'Verifikasi 2',
                'current_stage' => 'manager',
            ]);
        });

        return redirect()
            ->route('approval.avp.index')
            ->with('danger', 'Dokumen dikembalikan AVP (revisi ke Manager).');
    }

    /** Alias approve -> setujui */
    public function approve(Request $request, $id)
    {
        return $this->setujui($request, $id);
    }

    /** NEW: Upload 1–2 file sirkulir (hanya saat status Sirkulir) */
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
