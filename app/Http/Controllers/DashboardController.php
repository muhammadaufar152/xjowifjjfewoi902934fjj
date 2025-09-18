<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormReview;
use App\Models\Step;
use App\Models\Document;
use App\Models\ActionItem;
use App\Models\BusinessCycle;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        $role = strtolower($user->role ?? 'bpo'); // bpo|officer|manager|avp

        /** ===================== SUMMARY DOCUMENT ===================== **/
        $totalDocuments = Document::count();

        $hasStatusCol = Schema::hasColumn('documents', 'status');

        $updateCount     = 0;
        $obsoleteCount   = 0;
        $recentDocuments = collect();

        if ($hasStatusCol) {
            // FIX: kurangi satu ")" di akhir ekspresi normalisasi
            $statusExpr = "LOWER(TRIM(REPLACE(REPLACE(REPLACE(IFNULL(status,''), CHAR(13), ''), CHAR(10), ''), CHAR(9), '')))";

            $updateCount = Document::whereRaw("$statusExpr IN ('update','updated')")->count();
            $obsoleteCount = Document::whereRaw("$statusExpr IN ('obsolete','obselete')")->count();

            $recentDocuments = Document::select('*', DB::raw("$statusExpr AS status_norm"))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } else {
            $recentDocuments = Document::orderBy('created_at', 'desc')->take(5)->get();
        }

        /** ===================== FORM REVIEW ===================== **/
        $reviews = FormReview::with([
            'steps' => function ($q) {
                $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            }
        ])->get();

        $norm = fn($v) => trim(mb_strtolower((string)$v));
        $last = function (FormReview $r) { return optional($r->steps)->first(); };

        $isPendingAt = function ($step, string $tahap) use ($norm) {
            return $norm($step->tahapan ?? '') === $norm($tahap)
                && in_array($norm($step->status ?? ''), ['pending','menunggu'], true);
        };

        $hasStep = function (FormReview $r, string $tahap, array $statuses) use ($norm) {
            $statuses = array_map($norm, $statuses);
            return optional($r->steps)->contains(function ($s) use ($norm, $tahap, $statuses) {
                return $norm($s->tahapan ?? '') === $norm($tahap)
                    && in_array($norm($s->status ?? ''), $statuses, true);
            });
        };

        switch ($role) {
            case 'officer':
                $waiting_for_me = $reviews->filter(fn($r) => $norm($r->status ?? '') === 'pending')->count();
                break;
            case 'manager':
                $waiting_for_me = $reviews->filter(fn($r) => $isPendingAt($last($r), 'manager'))->count();
                break;
            case 'avp':
                $waiting_for_me = $reviews->filter(function ($r) use ($last, $isPendingAt, $norm) {
                    $ls = $last($r);
                    return $isPendingAt($ls, 'avp') && $norm($r->status ?? '') === 'verifikasi 2';
                })->count();
                break;
            default: // bpo
                $waiting_for_me = $reviews->filter(function ($r) use ($last, $isPendingAt, $norm) {
                    if ($isPendingAt($last($r), 'bpo')) return true;
                    $stage = $norm($r->current_stage ?? '');
                    $st    = $norm($r->status ?? '');
                    return $stage === 'bpo' && in_array($st, ['pending','return'], true);
                })->count();
                break;
        }

        if ($role === 'bpo') {
            $returned_to_me = $waiting_for_me;
        } elseif ($role === 'manager') {
            $returned_to_me = $reviews->filter(function ($r) use ($last, $isPendingAt, $hasStep) {
                $ls = $last($r);
                if (!$isPendingAt($ls, 'manager')) return false;
                return $hasStep($r, 'avp', ['tidak setuju', 'return']);
            })->count();
        } elseif ($role === 'officer') {
            $returned_to_me = $reviews->filter(function ($r) use ($hasStep, $norm) {
                return $norm($r->status ?? '') === 'pending'
                    && $hasStep($r, 'manager', ['tidak setuju', 'return']);
            })->count();
        } else {
            $returned_to_me = 0;
        }

        $in_progress = FormReview::whereNotIn('status', ['selesai', 'Selesai'])->count();

        $completed_this_month = FormReview::whereIn('status', ['selesai', 'Selesai'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $metrics = [
            'waiting_for_me'       => $waiting_for_me,
            'returned_to_me'       => $returned_to_me,
            'in_progress'          => $in_progress,
            'completed_this_month' => $completed_this_month,
        ];

        $queueTahap = in_array($role, ['bpo','officer','manager','avp']) ? $role : 'bpo';

        $myQueue = $reviews->filter(function ($r) use ($queueTahap, $last, $isPendingAt, $hasStep, $norm) {
                if ($queueTahap === 'officer') {
                    return $norm($r->status ?? '') === 'pending';
                }
                if ($queueTahap === 'bpo') {
                    if ($isPendingAt($last($r), 'bpo')) return true;
                    $stage = $norm($r->current_stage ?? '');
                    $st    = $norm($r->status ?? '');
                    return $stage === 'bpo' && in_array($st, ['pending','return'], true);
                }
                return $isPendingAt($last($r), $queueTahap);
            })
            ->sortByDesc('updated_at')
            ->take(8)
            ->values()
            ->map(function ($r) {
                return (object) [
                    'id'            => $r->id,
                    'no_dokumen'    => $r->no_dokumen,
                    'nama_dokumen'  => $r->nama_dokumen,
                    'perihal'       => $r->perihal,
                    'status'        => $r->status,
                    'updated_at'    => $r->updated_at,
                ];
            });

        $chart = [
            'labels' => ['Dokumen Progress', 'Dokumen Return', 'Dokumen Request', 'Done'],
            'data'   => [
                $in_progress,
                FormReview::whereIn('status', ['return','Return'])->count(),
                FormReview::count(),
                FormReview::whereIn('status', ['selesai','Selesai'])->count(),
            ],
        ];

        $recentActivity = Step::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('tanggal')
            ->take(12)
            ->get(['tahapan','status','verifikator','updated_at','created_at','tanggal'])
            ->map(function ($s) {
                $when = $s->updated_at ?? $s->created_at ?? $s->tanggal;
                return [
                    'text' => ucfirst((string)$s->tahapan) . ' — ' . (string)$s->status . ' (' . (string)$s->verifikator . ')',
                    'time' => $when,
                ];
            });

        /** ============== AGREGASI SIKLUS & JENIS (FIX kurung) ============== */
        $byCycle = [];
        $byType  = [];

        $normSql = fn($col) => "LOWER(TRIM(REPLACE(REPLACE(REPLACE(IFNULL($col,''), CHAR(13), ''), CHAR(10), ''), CHAR(9), '')))";

        try {
            $byCycle = Document::selectRaw("$normSql('business_cycle_id') AS k, COUNT(*) AS c")
                        ->groupByRaw($normSql('business_cycle_id'))
                        ->pluck('c', 'k')->toArray();

            $byType  = Document::selectRaw("$normSql('document_type_id') AS k, COUNT(*) AS c")
                        ->groupByRaw($normSql('document_type_id'))
                        ->pluck('c', 'k')->toArray();
        } catch (\Throwable $e) {
            $docsAgg = Document::select('business_cycle_id', 'document_type_id')->get();

            $normalizeAgg = function ($v) {
                $v = is_null($v) ? '' : (string)$v;
                $v = str_replace(["\r", "\n", "\t"], ' ', $v);
                $v = preg_replace('/\s+/', ' ', trim($v));
                return strtolower($v);
            };

            $byCycle = $docsAgg->pluck('business_cycle_id')->map($normalizeAgg)->countBy()->toArray();
            $byType  = $docsAgg->pluck('document_type_id')->map($normalizeAgg)->countBy()->toArray();
        }

        /** ============== SUMMARY ACTION ITEM (pie + cards) ============== */
        $aiRows = ActionItem::query()->select('status')->get();

        $normalizeAI = function ($v) {
            $v = str_replace(["\r","\n","\t"], ' ', (string) $v);
            $v = preg_replace('/\s+/', ' ', $v);
            return strtolower(trim($v));
        };

        $alias = [
            'open'           => 'open',
            'progress'       => 'progress',
            'in progress'    => 'progress',
            'ongoing'        => 'progress',
            'request close'  => 'request close',
            'requestclose'   => 'request close',
            'req close'      => 'request close',
            'closed'         => 'closed',
            'close'          => 'closed',
            'cancel'         => 'cancel',
            'canceled'       => 'cancel',
            'cancelled'      => 'cancel',
            'pending'        => 'pending',
            'menunggu'       => 'pending',
        ];

        $aiKeys = ['open','progress','cancel','request close','closed','pending'];
        $aiCounts = array_fill_keys($aiKeys, 0);

        foreach ($aiRows as $r) {
            $k = $normalizeAI($r->status ?? '');
            $k = $alias[$k] ?? $k;
            if (isset($aiCounts[$k])) $aiCounts[$k]++;
        }

        $aiStats = [
            'total'    => $aiRows->count(),
            'byStatus' => $aiCounts,
            'labels'   => ['Open','Progress','Cancel','Request Close','Closed','Pending'],
        ];

        $labelMapCycle = BusinessCycle::pluck('name', 'id'); 
        $labelMapType = DocumentType::pluck('name', 'id'); 
        
        return view('dashboard', compact(
            'metrics',
            'myQueue',
            'recentActivity',
            'chart',
            'totalDocuments',
            'updateCount',
            'obsoleteCount',
            'recentDocuments',
            'byCycle',
            'byType',
            'aiStats',
            'labelMapCycle',
            'labelMapType'
        ));
    }
}
