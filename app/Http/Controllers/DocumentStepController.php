<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentStepController extends Controller
{
    public function approve(DocumentStep $step)
    {
        $mustRole = strtolower($step->tahapan);
        abort_unless(auth()->user()->hasRole($mustRole), 403);

        DB::transaction(function () use ($step) {
            // Lock document agar aman dari race condition
            $doc = $step->document()->lockForUpdate()->first();

            // Validasi status document (sesuaikan dengan konstanta yang kamu punya)
            abort_unless($doc->status === Document::ST_REQ_CLOSE, 422, 'Document bukan Request Close');
            abort_unless($step->status === DocumentStep::STATUS_PENDING, 422, 'Step bukan Pending');

            // pastikan ini next pending step
            $next = $doc->documentSteps()->where('status', DocumentStep::STATUS_PENDING)->orderBy('id')->first();
            abort_unless($next && $next->id === $step->id, 422, 'Bukan step berikutnya');

            // approve step
            $step->update([
                'status'      => DocumentStep::STATUS_APPROVED,
                'verifikator' => auth()->user()->name,
                'tanggal'     => now(),
            ]);

            // cek kalau masih ada step pending
            $stillPending = $doc->documentSteps()->where('status', DocumentStep::STATUS_PENDING)->exists();
            if (!$stillPending) {
                $doc->update(['status' => Document::ST_CLOSED]);
            }
        });

        return back()->with('ok', 'Step disetujui.');
    }

    public function reject(Request $r, DocumentStep $step)
    {
        $data = $r->validate(['keterangan' => 'required|string|max:1000']);

        $mustRole = strtolower($step->tahapan);
        abort_unless(auth()->user()->hasRole($mustRole), 403);

        DB::transaction(function () use ($step, $data) {
            $doc = $step->document()->lockForUpdate()->first();

            // tandai ditolak
            $step->update([
                'status'      => DocumentStep::STATUS_REJECTED,
                'keterangan'  => $data['keterangan'],
                'verifikator' => auth()->user()->name,
                'tanggal'     => now(),
            ]);

            // reset step setelahnya ke Pending
            $doc->documentSteps()
                ->where('id', '>', $step->id)
                ->update([
                    'status'      => DocumentStep::STATUS_PENDING,
                    'keterangan'  => null,
                    'verifikator' => null,
                    'tanggal'     => null,
                ]);

            // document kembali ke Progress
            $doc->update(['status' => Document::ST_PROGRESS]);
        });

        return back()->with('ok', 'Step ditolak.');
    }
}
