<?php

namespace App\Services;

use App\Models\ActionItem;
use App\Models\ReviewStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActionItemWorkflow
{
    protected array $order = ['Officer', 'Manager', 'AVP'];

    public function startProgress(ActionItem $ai): void
    {
        if ($ai->status !== ActionItem::ST_OPEN) {
            return;
        }
        $ai->update(['status' => ActionItem::ST_PROGRESS]);
    }

    public function requestClose(ActionItem $ai): void
    {
        if (! in_array($ai->status, [
            ActionItem::ST_OPEN,
            ActionItem::ST_PROGRESS,
            ActionItem::ST_PENDING,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak valid untuk Request Close.',
            ]);
        }

        DB::transaction(function () use ($ai) {
            $this->ensureSteps($ai);
            $this->resetStepsToPending($ai);
            $ai->update(['status' => ActionItem::ST_REQ_CLOSE]);
        });
    }

    public function approveStep(ReviewStep $step, ?string $remark, string $approverName): void
    {
        DB::transaction(function () use ($step, $remark, $approverName) {
            /** @var ActionItem|null $ai */
            $ai = $step->actionItem()->lockForUpdate()->first();
            if (! $ai) return;

            $this->ensureSteps($ai);
            $must = $this->firstPendingStep($ai);
            if (! $must || $must->id !== $step->id) {
                throw ValidationException::withMessages([
                    'step' => 'Belum saatnya menyetujui tahapan ini.',
                ]);
            }

            $step->update([
                'status'      => 'Approved',
                'keterangan'  => $remark,
                'verifikator' => $approverName,
                'tanggal'     => now()->toDateString(),
            ]);

            $done = ! $ai->reviewSteps()->where('status', '!=', 'Approved')->exists();

            $ai->update([
                'status' => $done ? ActionItem::ST_CLOSED : ActionItem::ST_REQ_CLOSE,
            ]);
        });
    }

    public function rejectStep(ReviewStep $step, string $remark, string $approverName): void
    {
        DB::transaction(function () use ($step, $remark, $approverName) {
            /** @var ActionItem|null $ai */
            $ai = $step->actionItem()->lockForUpdate()->first();
            if (! $ai) return;

            $step->update([
                'status'      => 'Rejected',
                'keterangan'  => $remark,
                'verifikator' => $approverName,
                'tanggal'     => now()->toDateString(),
            ]);

            $ai->update(['status' => ActionItem::ST_PROGRESS]);
            $ai->reviewSteps()
               ->where('id', '!=', $step->id)
               ->where('status', '!=', 'Approved')
               ->update(['status' => 'Pending']);
        });
    }

    public function cancel(ActionItem $ai, string $reason): void
    {
        $reason = trim($reason);
        $notes  = trim(($ai->keterangan ?? '')."\nCancel: ".$reason);

        $ai->update([
            'status'     => ActionItem::ST_CANCEL,
            'keterangan' => $notes,
        ]);
    }

    public function generateDefaultSteps(ActionItem $ai): void
    {
        if ($ai->reviewSteps()->exists()) {
            return;
        }

        $ai->reviewSteps()->createMany([
            ['tahapan' => 'Officer', 'status' => 'Pending', 'form_review_id' => null, 'verifikator' => null, 'keterangan' => null, 'tanggal' => null],
            ['tahapan' => 'Manager', 'status' => 'Pending', 'form_review_id' => null, 'verifikator' => null, 'keterangan' => null, 'tanggal' => null],
            ['tahapan' => 'AVP',     'status' => 'Pending', 'form_review_id' => null, 'verifikator' => null, 'keterangan' => null, 'tanggal' => null],
        ]);

    }


    public function requestStatusChange(ActionItem $ai, string $requested, ?string $note = null): void
    {
        if (! in_array($requested, ['pending', 'cancel'], true)) {
            throw ValidationException::withMessages([
                'requested_status' => 'Status yang diminta tidak valid.',
            ]);
        }

        $tag  = strtoupper($requested);
        $line = '[REQUEST '.$tag.']'.($note ? ' '.$note : '');

        $ai->update([
            'keterangan' => trim(($ai->keterangan ? $ai->keterangan."\n" : '').$line),
        ]);
    }

    public function officerApproveRequestedStatus(ActionItem $ai, ?string $note = null, string $approverName = 'Officer'): void
    {
        $requested = $this->extractRequestedFromNotes($ai);
        if (! $requested) {
            throw ValidationException::withMessages([
                'requested_status' => 'Tidak ada permintaan status yang tertunda.',
            ]);
        }

        $status = $requested === 'PENDING' ? ActionItem::ST_PENDING : ActionItem::ST_CANCEL;

        $ai->update([
            'status'     => $status,
            'keterangan' => $this->appendDecisionNote($ai->keterangan, 'APPROVED', $requested, $approverName, $note),
        ]);
    }

    public function officerRejectRequestedStatus(ActionItem $ai, ?string $note = null, string $approverName = 'Officer'): void
    {
        $requested = $this->extractRequestedFromNotes($ai);
        if (! $requested) {
            throw ValidationException::withMessages([
                'requested_status' => 'Tidak ada permintaan status yang tertunda.',
            ]);
        }

        $ai->update([
            'keterangan' => $this->appendDecisionNote($ai->keterangan, 'REJECTED', $requested, $approverName, $note),
        ]);
    }

    protected function ensureSteps(ActionItem $ai): void
    {
        $existing = $ai->reviewSteps()->pluck('tahapan')->all();
        $needCreate = array_diff($this->order, $existing);

        if ($needCreate) {
            $rows = [];
            foreach ($this->order as $stage) {
                if (! in_array($stage, $existing, true)) {
                    $rows[] = ['tahapan' => $stage, 'status' => 'Pending'];
                }
            }
            if ($rows) {
                $ai->reviewSteps()->createMany($rows);
            }
        }
    }

    protected function resetStepsToPending(ActionItem $ai): void
    {
        $ai->reviewSteps()->update(['status' => 'Pending']);
    }

    protected function firstPendingStep(ActionItem $ai): ?ReviewStep
    {
        $steps = $ai->reviewSteps()->get()->keyBy('tahapan');
        foreach ($this->order as $stage) {
            $s = $steps[$stage] ?? null;
            if ($s && $s->status !== 'Approved') {
                return $s;
            }
        }
        return null;
    }

    protected function extractRequestedFromNotes(ActionItem $ai): ?string
    {
        $text = (string) $ai->keterangan;
        $posPending = strripos($text, '[REQUEST PENDING]');
        $posCancel  = strripos($text, '[REQUEST CANCEL]');
        if ($posPending === false && $posCancel === false) return null;
        return $posPending !== false && $posPending > $posCancel ? 'PENDING' : 'CANCEL';
    }

    protected function appendDecisionNote(?string $base, string $decision, string $requested, string $who, ?string $note): string
    {
        $line = '['.$requested.' '.$decision.' BY '.$who.']'.($note ? ' '.$note : '');
        return trim(($base ? $base."\n" : '').$line);
    }
}
