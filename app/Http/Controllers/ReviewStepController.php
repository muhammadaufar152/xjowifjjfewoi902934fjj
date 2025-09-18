<?php

namespace App\Http\Controllers;

use App\Models\ReviewStep;
use App\Models\ActionItem;
use App\Services\ActionItemWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ReviewStepController extends Controller
{
    public function __construct(private ActionItemWorkflow $flow) {}

    public function approve(ReviewStep $step)
    {
        $mustRole = strtolower($step->tahapan);
        abort_unless(auth()->user()->hasRole($mustRole), 403);

        DB::transaction(function () use ($step) {
            $ai = $step->actionItem()->lockForUpdate()->first();

            abort_unless($ai->status === ActionItem::ST_REQ_CLOSE, 422, 'AI bukan Request Close');
            abort_unless($step->status === ReviewStep::STATUS_PENDING, 422, 'Step bukan Pending');

            // pastikan ini memang next pending
            $next = $ai->reviewSteps()->where('status', ReviewStep::STATUS_PENDING)->orderBy('id')->first();
            abort_unless($next && $next->id === $step->id, 422, 'Bukan step berikutnya');

            // approve step
            $step->update([
                'status'      => ReviewStep::STATUS_APPROVED,
                'verifikator' => auth()->user()->name,
                'tanggal'     => now(),
            ]);

            // kalau masih ada Pending setelah ini -> tetap Request Close
            $stillPending = $ai->reviewSteps()->where('status', ReviewStep::STATUS_PENDING)->exists();
            if (!$stillPending) {
                $ai->update(['status' => ActionItem::ST_CLOSED]);
            }
        });

        return back()->with('ok', 'Step disetujui.');
    }

    public function reject(Request $r, ReviewStep $step)
    {
        $data = $r->validate(['keterangan' => 'required|string|max:1000']);

        $mustRole = strtolower($step->tahapan);
        abort_unless(auth()->user()->hasRole($mustRole), 403);

        DB::transaction(function () use ($step, $data) {
            $ai = $step->actionItem()->lockForUpdate()->first();

            // tandai ditolak
            $step->update([
                'status'      => ReviewStep::STATUS_REJECTED,
                'keterangan'  => $data['keterangan'],
                'verifikator' => auth()->user()->name,
                'tanggal'     => now(),
            ]);

            // reset step setelahnya ke Pending
            $ai->reviewSteps()
            ->where('id', '>', $step->id)
            ->update([
                'status'      => ReviewStep::STATUS_PENDING,
                'keterangan'  => null,
                'verifikator' => null,
                'tanggal'     => null,
            ]);

            // AI kembali ke Progress
            $ai->update(['status' => ActionItem::ST_PROGRESS]);
        });

        return back()->with('ok', 'Step ditolak.');
    }

    private function routeFor(ReviewStep $step): array
    {
        return $step->action_item_id
            ? ['ai.show', $step->action_item_id]
            : ['form_review.show', $step->form_review_id];
    }
}
