<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Services\ActionItemWorkflow;
use App\Models\ReviewStep;
use App\Http\Requests\UpdateActionItemStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActionItemController extends Controller
{
    public function __construct(private ActionItemWorkflow $flow) {}

    public function index()
    {
        $q = ActionItem::query();

        if ($st = request('status')) {
            $q->where('status', $st);
        }

        if ($kw = request('q')) {
            $q->where(function ($x) use ($kw) {
                $x->where('nama_dokumen', 'like', "%$kw%")
                  ->orWhere('no_dokumen', 'like', "%$kw%")
                  ->orWhere('action_item', 'like', "%$kw%")
                  ->orWhere('keterangan', 'like', "%$kw%")
                  ->orWhere('no_fr', 'like', "%$kw%");
            });
        }

        $items = $q->orderByDesc('created_at')->orderByDesc('id')->get();

        return view('pages.action_item.index', compact('items'));
    }

    public function create()
    {
        return view('pages.action_item.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama_dokumen' => 'required|string|max:255',
            'no_dokumen'   => 'required|string|max:255',
            'action_item'  => 'required|string',
            'bpo_uic'      => 'required|string|max:255',
            'target'       => 'nullable|date',
            'keterangan'   => 'nullable|string',
            'no_fr'        => 'nullable|string|max:255',
            'lampiran'     => 'required|file|mimes:pdf|max:20480',
        ]);

        $data['bpo_uic'] = collect(explode(',', $data['bpo_uic']))
            ->map(fn($s) => trim($s))->filter()->implode(', ');

        $data['status'] = ActionItem::ST_OPEN;

        $ai = ActionItem::create($data);

        if ($r->hasFile('lampiran')) {
            $dir  = "action_items/{$ai->id}";
            $name = "INIT_{$ai->id}_" . now()->format('YmdHis') . ".pdf";
            $path = $r->file('lampiran')->storeAs($dir, $name, 'public');
            $ai->update(['lampiran' => $path]);
        }

        $this->flow->generateDefaultSteps($ai);

        return redirect()->route('ai.index')->with('ok', 'Action Item dibuat (status: OPEN).');
    }

    public function show(ActionItem $ai)
    {
        $ai->load('reviewSteps');
        return view('pages.action_item.show', ['item' => $ai]);
    }

    public function startProgress(Request $r, ActionItem $ai)
    {
        $r->validate(['target' => 'nullable|date']);
    
        // simpan target kalau ada
        $ai->target = $r->target ?? $ai->target;
        $ai->status = ActionItem::ST_PROGRESS;
        $ai->save();
    
        try { $this->flow->startProgress($ai); } catch (\Throwable $e) {}
    
        // ⬇️ pindah ke halaman detail setelah update
        return redirect()->route('ai.show', $ai)
            ->with('ok', 'Status diubah ke PROGRESS.');
    }
    

    public function uploadLampiranAndRequestClose(Request $r, ActionItem $ai)
    {
        if ($ai->uploadIsBlocked()) {
            return back()->withErrors(['lampiran' => 'Upload sedang dikunci (Pending).']);
        }

        $r->validate([
            'lampiran'   => 'required|array',
            'lampiran.*' => 'file|mimes:pdf|max:20480',
        ]);

        if ($ai->lampirans()->exists()) {
            foreach ($ai->lampirans as $lamp) {
                try { Storage::disk('public')->delete($lamp->path); } catch (\Throwable $e) {}
                $lamp->delete();
            }
        }

        foreach ($r->file('lampiran') as $file) {
            $dir  = "action_items/{$ai->id}";
            $name = "LAMPIRAN_{$ai->id}_" . now()->format('YmdHis') . '_' . uniqid() . ".pdf";
            $path = $file->storeAs($dir, $name, 'public');

            $ai->lampirans()->create([
                'path'        => $path,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $ai->status = ActionItem::ST_REQ_CLOSE;
        $ai->save();

        if (!$ai->reviewSteps()->exists()) {
            foreach (['Officer','Manager','AVP'] as $stage) {
                $ai->reviewSteps()->create([
                    'tahapan' => $stage,
                    'status'  => ReviewStep::STATUS_PENDING,
                ]);
            }
        } else {
            $ai->reviewSteps()->update([
                'status'      => ReviewStep::STATUS_PENDING,
                'keterangan'  => null,
                'verifikator' => null,
                'tanggal'     => null,
            ]);
        }

        try { $this->flow->requestClose($ai); } catch (\Throwable $e) {}

        return back()->with('ok', 'Lampiran baru diunggah & REQUEST CLOSE diajukan.');
    }

    public function requestStatus(Request $r, ActionItem $ai)
    {
        $data = $r->validate([
            'requested_status' => 'required|in:pending,cancel',
            'note'             => 'nullable|string|max:1000',
        ]);

        if (!in_array($ai->status, [ActionItem::ST_PROGRESS, ActionItem::ST_REQ_CLOSE], true)) {
            return back()->with('err', 'Permintaan status hanya bisa diajukan saat status Progress atau Request Close.');
        }

        $map = [
            'pending' => ActionItem::ST_PENDING,
            'cancel'  => ActionItem::ST_CANCELLED,
        ];
        $targetStatus = $map[$data['requested_status']];
        $note         = trim($data['note'] ?? '');

        try {
            $this->flow->requestStatusChange($ai, $targetStatus, $note);
        } catch (\Throwable $e) {
            $tag = strtoupper($data['requested_status']);
            $ai->keterangan = trim(
                ($ai->keterangan ? $ai->keterangan . "\n" : '') .
                "[REQUEST {$tag}] " . ($note ?: '')
            );
            $ai->save();
        }

        return back()->with('ok', "Permintaan perubahan status ke {$targetStatus} telah dikirim.");
    }

    public function destroy(ActionItem $ai)
    {
        if ($ai->lampiran && Storage::disk('public')->exists($ai->lampiran)) {
            try { Storage::disk('public')->delete($ai->lampiran); } catch (\Throwable $e) {}
        }

        $ai->delete();

        return back()->with('ok', 'Action Item dihapus.');
    }

    public function reject(Request $r, ReviewStep $step)
    {
        $r->validate(['keterangan' => 'required|string|max:1000']);

        $ai = $step->actionItem;

        $step->update([
            'status'      => ReviewStep::STATUS_REJECTED,
            'keterangan'  => $r->keterangan,
            'verifikator' => auth()->user()->name ?? 'System',
            'tanggal'     => now(),
        ]);

        $ai->update(['status' => ActionItem::ST_PROGRESS]);

        foreach ($ai->lampirans as $lamp) {
            try { Storage::disk('public')->delete($lamp->path); } catch (\Throwable $e) {}
            $lamp->delete();
        }

        $ai->reviewSteps()
            ->where('id', '!=', $step->id)
            ->update([
                'status'      => ReviewStep::STATUS_PENDING,
                'keterangan'  => null,
                'verifikator' => null,
                'tanggal'     => null,
            ]);

        return back()->with('ok', 'Ditolak. Lampiran lama dihapus. Silakan upload lampiran terbaru.');
    }

    public function cancel(ActionItem $ai)
    {
        if (!auth()->user()->hasAnyRole(['officer','manager','avp'])) abort(403);

        $ai->status = ActionItem::ST_CANCELLED;
        $ai->save();

        return back()->with('ok', 'Action Item sudah di-Cancel.');
    }

    public function updateStatus(UpdateActionItemStatusRequest $request, ActionItem $ai)
    {
        $ai->status = $request->status;
        $ai->save();

        return back()->with('ok', 'Status berhasil diperbarui.');
    }

    public function progress(ActionItem $ai)
    {
        return $this->startProgress(request(), $ai);
    }

    public function requestClose(ActionItem $ai)
    {
        return $this->uploadLampiranAndRequestClose(request(), $ai);
    }

    public function hold(ActionItem $ai, Request $request)
    {
        $ai->lockUploads(auth()->id(), $request->input('reason'));
        $ai->status = ActionItem::ST_PENDING;
        $ai->save();

        return back()->with('status', 'Item dipending & upload dikunci.');
    }

    public function resume(ActionItem $ai)
    {
        $ai->resumeUploads();

        if ($ai->status === ActionItem::ST_PENDING) {
            $ai->status = ActionItem::ST_PROGRESS;
            $ai->save();
        }

        return back()->with('status', 'Pending dilepas & status dikembalikan.');
    }
}
