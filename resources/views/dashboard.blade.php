@extends('layouts.app-document')

@section('content')
@php
  /** @var \App\Models\User|null $user */
  $user      = auth()->user();
  $roleLabel = strtoupper($user->role ?? 'BPO');

  // ================= Guard data dari controller =================
  $metrics        = $metrics        ?? ['waiting_for_me'=>0,'returned_to_me'=>0,'in_progress'=>0,'completed_this_month'=>0];
  $myQueue        = $myQueue        ?? collect();
  $recentActivity = $recentActivity ?? collect();
  $chart          = $chart          ?? ['labels'=>['Dokumen Progress','Dokumen Return','Dokumen Sirkulir','Done'], 'data'=>[0,0,0,0]];

  // Summary Document
  $totalDocuments  = (int) ($totalDocuments  ?? 0);
  $updateCount     = (int) ($updateCount     ?? 0);
  $obsoleteCount   = (int) ($obsoleteCount   ?? 0);
  $recentDocuments = $recentDocuments ?? collect();

  // ---------------- AGREGASI SIKLUS & JENIS (fallback bila controller tidak kirim) ----------------
  $byCycle = $byCycle ?? [];
  $byType  = $byType  ?? [];

  // normalizer reusable (dipakai byCycle/byType & matriks)
  $normalize = function ($v) {
      $v = trim((string)$v);
      $v = str_replace(["\r", "\n", "\t"], ' ', $v);
      $v = preg_replace('/\s+/', ' ', $v);
      $v = strtolower($v);
      return $v;
  };

  // Jika controller belum kirim, ambil kolom minimal lalu hitung di PHP (tanpa raw SQL)
  if (empty($byCycle) || empty($byType)) {
      $docs = \App\Models\Document::query()
          ->select('business_cycle_id', 'document_type_id')
          ->get();

      if (empty($byCycle)) {
          $byCycle = $docs->pluck('business_cycle_id')
              ->map($normalize)
              ->countBy()
              ->toArray();
      }

      if (empty($byType)) {
          $byType = $docs->pluck('document_type_id')
              ->map($normalize)
              ->countBy()
              ->toArray();
      }
  }

  // Label map -> pretty case
  // $labelMapCycle = [
  //   'revenue'=>'Revenue','cost'=>'Cost','tax'=>'Tax',
  //   'procurement & asset management'=>'Procurement & Asset Management',
  //   'financial reporting'=>'Financial Reporting','treasury'=>'Treasury',
  //   'planning & system management'=>'Planning & System Management',
  //   'general affair'=>'General Affair','it management'=>'IT Management', 'inventory'=>'Inventory',
  //   // 'assurance'=>'Assurance','fulfillment'=>'Fulfillment',
  // ];
  // $labelMapType  = [
  //   'bispro'=>'Bispro (L2, L3, Dst)','prosedur'=>'Prosedur','instruksi kerja'=>'Instruksi Kerja','form'=>'Form',
  // ];

  // Bentuk list terurut desc + info label utk filter
  // ===============================
  //  display all type
  // ===============================
  $cyclesList = collect($labelMapCycle)->map(function ($label, $key) use ($byCycle) {
    $count = (int) ($byCycle[$key] ?? 0);
    return [
      'label'  => $label,
      'key'    => $key,
      'count'  => $count,
      'filter' => $label,
    ];
  })->values();

  $typesList = collect($labelMapType)->map(function ($label, $key) use ($byType) {
    $count = (int) ($byType[$key] ?? 0);

    $shortLabel = $key === 'bispro' ? 'Bispro' : $label;

    return [
      'label'  => $shortLabel,
      'title'  => $label,
      'key'    => $key,
      'count'  => $count,
      'filter' => $shortLabel,
    ];
  })->values();

  // ===============================
  //  type berdasarkan ada tidaknya data
  // ===============================
  // $cyclesList = collect($byCycle)->map(function($c,$k) use($labelMapCycle){
  //   $label = $labelMapCycle[$k] ?? ($k === '-' ? '-' : ucwords($k));
  //   return ['label'=>$label,'key'=>$k,'count'=>(int)$c,'filter'=>$label];
  // })->sortByDesc('count')->values();

  // $typesList = collect($byType)->map(function($c,$k) use($labelMapType){
  //   $label = $labelMapType[$k] ?? ($k === '-' ? '-' : ucwords($k));
  //   return ['label'=>$label,'key'=>$k,'count'=>(int)$c,'filter'=>$label];
  // })->sortByDesc('count')->values();

  $cycleTotal = max($cyclesList->sum('count'), $totalDocuments, 1);
  $typeTotal  = max($typesList->sum('count'),  $totalDocuments, 1);

  $topCycle   = $cyclesList->first();
  $topType    = $typesList->first();

  $topCycleLabel = $topCycle['label'] ?? '-';
  $topCycleVal   = (int)($topCycle['count'] ?? 0);
  $topCyclePct   = $cycleTotal ? round($topCycleVal / $cycleTotal * 100) : 0;

  $topTypeLabel  = $topType['label'] ?? '-';
  $topTypeVal    = (int)($topType['count'] ?? 0);
  $topTypePct    = $typeTotal ? round($topTypeVal / $typeTotal * 100) : 0;

  // Palet WARNA kontras (beda2)
  $cycleColors = ['#2563eb','#f97316','#a855f7','#10b981','#ef4444','#0ea5e9','#eab308','#14b8a6','#f43f5e','#22c55e','#8b5cf6','#06b6d4'];
  $typeColors  = ['#0ea5e9','#ef4444','#22c55e','#8b5cf6','#eab308','#06b6d4'];

  $percent = fn($v,$t) => $t > 0 ? round(($v/$t)*100) : 0;

  // Data chart (FR) – tidak dipakai langsung oleh JS baru
  $chartLabels = is_array($chart['labels'] ?? null) ? array_values($chart['labels']) : ['Dokumen Progress','Dokumen Return','Dokumen Sirkulir','Done'];
  $chartData   = is_array($chart['data']   ?? null) ? array_values($chart['data'])   : [0,0,0,0];

  // Tetap dipakai di tempat lain
  $updatePct   = $totalDocuments > 0 ? round(($updateCount   / $totalDocuments) * 100) : 0;
  $obsoletePct = $totalDocuments > 0 ? round(($obsoleteCount / $totalDocuments) * 100) : 0;

  // ---------- AGREGASI MATRIX (Siklus × Jenis) ----------
  $docsAll = \App\Models\Document::query()
      ->select('business_cycle_id','document_type_id')
      ->get();

  $matrix = [];
  foreach ($docsAll as $d) {
      $s = $normalize($d->business_cycle_id ?? '');
      $j = $normalize($d->document_type_id ?? '');
      $s = $s === '' ? '-' : $s;
      $j = $j === '' ? '-' : $j;
      $matrix[$s][$j] = ($matrix[$s][$j] ?? 0) + 1;
  }
@endphp

<div class="container-fluid">
  <div class="row justify-content-center mt-3">
    <div class="col-10">

      {{-- HEADER --}}
      <div class="mb-3">
        <h5 class="mb-1">Hi, {{ $user->name ?? 'User' }} 👋</h5>
        <div class="text-muted">
          Selamat datang di Bispro — Anda masuk sebagai <strong>{{ $roleLabel }}</strong>.
        </div>
      </div>

      @php
        // rute default & turunan
        $toAll      = route('document');
        $toTopCycle = $topCycle ? route('document', ['siklus'=>$topCycle['filter']]) : $toAll;
        $toTopType  = $topType  ? route('document', ['jenis' =>$topType['filter']])  : $toAll;
      @endphp

      {{-- ===================== SUMMARY DOCUMENT (3 kartu) ===================== --}}
      {{-- <div class="row g-4 mb-3">
        {{-- Total Documents (biru) --}}
        {{-- <div class="col-12 col-md-4">
          <div class="doc-kpi doc-kpi--blue doc-kpi--link" data-nav="{{ $toAll }}">
            <div class="doc-kpi__head">
              <i class="bi bi-files me-2"></i><span>Total Documents</span>
              <span class="doc-kpi__chip">All</span>
            </div>
            <div class="doc-kpi__num">
              <span class="countup" data-target="{{ $totalDocuments }}">{{ $totalDocuments }}</span>
            </div>
            <div class="doc-kpi__progress doc-kpi__progress--ghost"><div class="doc-kpi__bar"></div></div>
          </div>
        </div> --}}

        {{-- Siklus dominan (hijau) --}}
        {{-- <div class="col-12 col-md-4">
          <div class="doc-kpi doc-kpi--green doc-kpi--link" data-nav="{{ $toTopCycle }}">
            <div class="doc-kpi__head">
              <i class="bi bi-diagram-3 me-2"></i>
              <span>Siklus — <strong>{{ $topCycleLabel }}</strong></span>
              <span class="doc-kpi__chip">{{ $topCyclePct }}%</span>
            </div>
            <div class="doc-kpi__num">
              <span class="countup" data-target="{{ $topCycleVal }}">{{ $topCycleVal }}</span>
            </div>
            <div class="doc-kpi__progress"><div class="doc-kpi__bar" data-progress="{{ $topCyclePct }}"></div></div>
          </div>
        </div> --}}

        {{-- Jenis dominan (merah) --}}
        {{-- <div class="col-12 col-md-4">
          <div class="doc-kpi doc-kpi--red doc-kpi--link" data-nav="{{ $toTopType }}">
            <div class="doc-kpi__head">
              <i class="bi bi-journal-text me-2"></i>
              <span>Jenis — <strong>{{ $topTypeLabel }}</strong></span>
              <span class="doc-kpi__chip">{{ $topTypePct }}%</span>
            </div>
            <div class="doc-kpi__num">
              <span class="countup" data-target="{{ $topTypeVal }}">{{ $topTypeVal }}</span>
            </div>
            <div class="doc-kpi__progress"><div class="doc-kpi__bar" data-progress="{{ $topTypePct }}"></div></div>
          </div>
        </div>
      </div> --}}

      {{-- ===== Distribusi Siklus ===== --}}
      {{-- <div class="doc-stack mb-2">
        <div class="small text-muted fw-600 mb-1"><i class="bi bi-graph-up-arrow me-1"></i> Distribusi Siklus</div>
        <div class="doc-stack__bar">
          @foreach($cyclesList as $i => $row)
            @php $w = $percent($row['count'], $cycleTotal); $col = $cycleColors[$i % count($cycleColors)]; @endphp
            @if($w>0)
              <div class="doc-stack__seg" style="width:{{ $w }}%;background:{{ $col }}" title="{{ $row['label'] }} {{ $w }}%"></div>
            @endif
          @endforeach
        </div>
        <div class="doc-stack__legend flex-wrap mt-1">
          @foreach($cyclesList as $i => $row)
            @php $w = $percent($row['count'], $cycleTotal); $col = $cycleColors[$i % count($cycleColors)]; @endphp
            <span><span class="dot" style="background:{{ $col }}"></span> {{ $row['label'] }} ({{ $w }}%)</span>
          @endforeach
          <span class="ms-auto text-muted small">Total: {{ $cycleTotal }}</span>
        </div>
      </div> --}}

      {{-- ===== Kartu per Siklus (klik → filter) ===== --}}
      {{-- <div class="row g-3 mb-4">
        @foreach($cyclesList as $i => $row)
          @php
            $pct = $percent($row['count'],$cycleTotal);
            $col = $cycleColors[$i % count($cycleColors)];
            $url = route('document',['siklus'=>$row['filter']]);
          @endphp
          <div class="col-6 col-md-3 col-lg-2">
            <div class="mini-kpi" style="--mini-bg: {{ $col }};" data-nav="{{ $url }}" tabindex="0" role="button" aria-label="Filter siklus {{ $row['label'] }}">
              <div class="mini-kpi__head">
                <span class="mini-kpi__title text-truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                <span class="mini-kpi__chip">{{ $pct }}%</span>
              </div>
              <div class="mini-kpi__num"><span class="countup" data-target="{{ $row['count'] }}">{{ $row['count'] }}</span></div>
            </div>
          </div>
        @endforeach
      </div> --}}

      {{-- ===== Distribusi Jenis Dokumen ===== --}}
      {{-- <div class="doc-stack mb-2">
        <div class="small text-muted fw-600 mb-1"><i class="bi bi-graph-up-arrow me-1"></i> Distribusi Jenis Dokumen</div>
        <div class="doc-stack__bar">
          @foreach($typesList as $i => $row)
            @php $w = $percent($row['count'], $typeTotal); $col = $typeColors[$i % count($typeColors)]; @endphp
            @if($w>0)
              <div class="doc-stack__seg" style="width:{{ $w }}%;background:{{ $col }}" title="{{ $row['label'] }} {{ $w }}%"></div>
            @endif
          @endforeach
        </div>
        <div class="doc-stack__legend flex-wrap mt-1">
          @foreach($typesList as $i => $row)
            @php $w = $percent($row['count'], $typeTotal); $col = $typeColors[$i % count($typeColors)]; @endphp
            <span><span class="dot" style="background:{{ $col }}"></span> {{ $row['label'] }} ({{ $w }}%)</span>
          @endforeach
          <span class="ms-auto text-muted small">Total: {{ $typeTotal }}</span>
        </div>
      </div> --}}

      {{-- ===== Kartu per Jenis (klik → filter) ===== --}}
      {{-- <div class="row g-3 mb-4">
        @foreach($typesList as $i => $row)
          @php
            $pct = $percent($row['count'],$typeTotal);
            $col = $typeColors[$i % count($typeColors)];
            $url = route('document',['jenis'=>$row['filter']]);
          @endphp
          <div class="col-6 col-md-3 col-lg-2">
            <div class="mini-kpi" style="--mini-bg: {{ $col }};" data-nav="{{ $url }}" tabindex="0" role="button" aria-label="Filter jenis {{ $row['label'] }}">
              <div class="mini-kpi__head">
                <span class="mini-kpi__title text-truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                <span class="mini-kpi__chip">{{ $pct }}%</span>
              </div>
              <div class="mini-kpi__num"><span class="countup" data-target="{{ $row['count'] }}">{{ $row['count'] }}</span></div>
            </div>
          </div>
        @endforeach
      </div> --}}
      {{-- ===================== /SUMMARY DOCUMENT ===================== --}}

      {{-- ===================== MATRIX DOKUMEN (Siklus × Jenis) ===================== --}}
      <div class="mb-4">
        <div class="doc-section-title">
          <i class="bi bi-grid-3x3-gap me-1"></i> Matriks Dokumen (Siklus × Jenis)
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0 doc-matrix custom-table">
            <thead class="custom-thead">
              <tr class="main-header">
                <th class="sticky left-0 bg-slate-900 text-white text-center align-middle" rowspan="2">
                  Siklus
                </th>
                <th class="bg-slate-900 text-white text-center" colspan="{{ count($typesList) }}">
                  Jenis Dokumen
                </th>
              </tr>
              <tr class="sub-header">
                @foreach($typesList as $t)
                  <th class="text-center bg-slate-800 text-white">{{ $t['title'] }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($cyclesList as $cy)
                <tr>
                  <th class="sticky left-0 bg-slate-100 text-left">
                    {{ $cy['label'] }}
                  </th>
                  @foreach($typesList as $t)
                    @php
                      $rowKey = $cy['key'];
                      $colKey = $t['key'];
                      $count  = (int) ($matrix[$rowKey][$colKey] ?? 0);
                    @endphp
                    <td class="text-center fw-semibold">
                      @if($count > 0)
                          <a href="{{ route('document', ['siklus' => $cy['filter'], 'jenis' => $t['filter']]) }}" class="doc-matrix-link text-decoration-none">{{ $count }}</a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      {{-- ===================== /MATRIX ===================== --}}

      @php
        // ==== Summary FORM REVIEW cards ====
        $w = (int)($metrics['waiting_for_me'] ?? 0);
        $r = (int)($metrics['returned_to_me'] ?? 0);
        $p = (int)($metrics['in_progress'] ?? 0);
        $c = (int)($metrics['completed_this_month'] ?? 0);
        $frTotal = $w + $r + $p + $c;

        // total dokument Form Review (dipakai utk kartu Document Request)
        $frDocsTotal = \App\Models\FormReview::count();

        $pct = function ($val, $tot) { return $tot > 0 ? round(($val / $tot) * 100) : 0; };
        $wPct = $pct($w, $frTotal); $rPct = $pct($r, $frTotal);
        $pPct = $pct($p, $frTotal); $cPct = $pct($c, $frTotal);

        // === TARGET KLIK KARTU FORM REVIEW BERDASARKAN ROLE ===
        $roleSlug = strtolower($user->role ?? 'bpo');
        $frTarget = match ($roleSlug) {
            'officer' => route('approval.officer.index'),
            'manager' => route('approval.manager.index'),
            'avp'     => route('approval.avp.index'),
            default   => route('form_review.index'),
        };
      @endphp

      {{-- ===================== SUMMARY FORM REVIEW (cards only) ===================== --}}
{{-- <div class="row g-3 mt-1">
  <div class="col-12 col-md-3">
    <div class="metric-card metric-blue p-3 h-100 fr-link" data-nav="{{ $frTarget }}" role="button" tabindex="0">
      <span class="metric-chip">{{ $wPct }}%</span>
      <div class="d-flex align-items-center mb-2 metric-title">
        <i class="bi bi-inbox me-2 metric-icon"></i>
        <span>Butuh Tindakan</span>
      </div>
      <div class="metric-value metric-value--lg">
        <span class="countup" data-target="{{ $w }}">{{ $w }}</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="metric-card metric-amber p-3 h-100 fr-link" data-nav="{{ $frTarget }}" role="button" tabindex="0">
      <span class="metric-chip">{{ $rPct }}%</span>
      <div class="d-flex align-items-center mb-2 metric-title">
        <i class="bi bi-arrow-counterclockwise me-2 metric-icon"></i>
        <span>Di Return</span>
      </div>
      <div class="metric-value metric-value--lg">
        <span class="countup" data-target="{{ $r }}">{{ $r }}</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="metric-card metric-purple p-3 h-100 fr-link" data-nav="{{ $frTarget }}" role="button" tabindex="0">
      <span class="metric-chip">{{ $pPct }}%</span>
      <div class="d-flex align-items-center mb-2 metric-title">
        <i class="bi bi-hourglass-split me-2 metric-icon"></i>
        <span>Dalam Proses</span>
      </div>
      <div class="metric-value metric-value--lg">
        <span class="countup" data-target="{{ $p }}">{{ $p }}</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="metric-card metric-green p-3 h-100 fr-link" data-nav="{{ $frTarget }}" role="button" tabindex="0">
      <span class="metric-chip">{{ $cPct }}%</span>
      <div class="d-flex align-items-center mb-2 metric-title">
        <i class="bi bi-check-circle me-2 metric-icon"></i>
        <span>Selesai bulan ini</span>
      </div>
      <div class="metric-value metric-value--lg">
        <span class="countup" data-target="{{ $c }}">{{ $c }}</span>
      </div>
    </div>
  </div>
</div> --}}

{{-- <div class="row g-3 mt-2">
  <div class="col-12 col-md-3">
    <div class="metric-card metric-cyan p-3 h-100 fr-link"
         data-nav="{{ route('form_review.index') }}" role="button" tabindex="0">
      <div class="d-flex align-items-center mb-2 metric-title">
        <i class="bi bi-file-earmark-text me-2 metric-icon"></i>
        <span>Document Request</span>
      </div>
      <div class="metric-value metric-value--lg">
        <span class="countup" data-target="{{ $frDocsTotal }}">{{ $frDocsTotal }}</span>
      </div>
    </div>
  </div>
</div> --}}

      {{-- Mini composition bar FORM REVIEW --}}
      {{-- <div class="fr-mini mt-2 mb-3">
        <div class="fr-mini__bar">
          <div class="fr-mini__seg fr-mini__seg--blue"   style="width: {{ $wPct }}%"></div>
          <div class="fr-mini__seg fr-mini__seg--amber"  style="width: {{ $rPct }}%"></div>
          <div class="fr-mini__seg fr-mini__seg--purple" style="width: {{ $pPct }}%"></div>
          <div class="fr-mini__seg fr-mini__seg--green"  style="width: {{ $cPct }}%"></div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-1">
          <div class="small">
            <span class="dot dot--blue me-1"></span> Butuh Tindakan ({{ $wPct }}%)
            <span class="ms-3"><span class="dot dot--amber me-1"></span> Di Return ({{ $rPct }}%)</span>
            <span class="ms-3"><span class="dot dot--purple me-1"></span> Dalam Proses ({{ $pPct }}%)</span>
            <span class="ms-3"><span class="dot dot--green me-1"></span> Selesai ({{ $cPct }}%)</span>
          </div>
          <div class="small text-muted">Total: {{ $w + $r + $p + $c }}</div>
        </div>
      </div> --}}

      {{-- CHART + QUEUE --}}
      {{-- <div class="row g-3 mt-3">
        <div class="col-12 col-lg-7">
          <div class="border rounded p-3 h-100 card-clip">
            <div class="small text-muted mb-2">
              <i class="bi bi-pie-chart me-1"></i> Progress Review Dokumen
            </div>
            <div id="docChartWrap" style="position:relative;height:260px;max-height:260px;">
              <canvas id="docChart" style="width:100%;height:100%;"></canvas>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="border rounded p-3 h-100 card-clip">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small text-muted"><i class="bi bi-flag me-1"></i> Butuh aksi saya</div>
              <a href="{{ route('form_review.index') }}" class="btn btn-link btn-sm p-0">Lihat semua</a>
            </div>

            @if($myQueue->isEmpty())
              <div class="text-muted small">Tidak ada item yang menunggu aksi Anda.</div>
            @else
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Nama/No Dokumen</th>
                      <th>Perihal</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  @foreach($myQueue as $row)
                    @php
                      $st = strtolower(trim($row->status ?? '-'));
                      $st = str_replace(['  ','-'], [' ',' '], $st);
                    @endphp
                    <tr>
                      <td class="text-truncate" style="max-width: 220px;">
                        {{ $row->nama_dokumen ?? '-' }}
                        <span class="text-muted">/ {{ $row->no_dokumen ?? '-' }}</span>
                      </td>
                      <td class="text-truncate" style="max-width: 160px;">{{ $row->perihal ?? '-' }}</td>
                      <td>
                        @if(in_array($st, ['pending','menunggu']))
                          <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                        @elseif(in_array($st, ['return','tidak setuju','ditolak']))
                          <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise me-1"></i>Return</span>
                        @elseif(in_array($st, ['verifikasi 1','verifikasi1']))
                          <span class="badge bg-success"><i class="bi bi-1-circle me-1"></i>Verifikasi 1</span>
                        @elseif(in_array($st, ['verifikasi 2','verifikasi2']))
                          <span class="badge bg-info text-dark"><i class="bi bi-2-circle me-1"></i>Verifikasi 2</span>
                        @elseif(in_array($st, ['selesai']))
                          <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                        @else
                          <span class="badge bg-light text-dark">{{ $row->status ?? '-' }}</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      </div> --}}

      {{-- ======= Guard AI + PIE ======= --}}
      @php
        $ai = $aiStats ?? [
          'total'=>0,
          'byStatus'=>['open'=>0,'progress'=>0,'cancel'=>0,'request close'=>0,'closed'=>0,'pending'=>0],
          'labels'=>['Open','Progress','Cancel','Request Close','Closed','Pending'],
        ];
        $aiBy       = $ai['byStatus'] ?? [];
        $aiTotal    = (int)($ai['total'] ?? 0);
        $aiOpen     = (int)($aiBy['open'] ?? 0);
        $aiProg     = (int)($aiBy['progress'] ?? 0);
        $aiCancel   = (int)($aiBy['cancel'] ?? 0);
        $aiReqClose = (int)($aiBy['request close'] ?? 0);
        $aiClosed   = (int)($aiBy['closed'] ?? 0);
        $aiPending  = (int)($aiBy['pending'] ?? 0);

        $aiRoute = function($status = null) {
          try {
            return $status === null
              ? route('action_item.index')
              : route('action_item.index', ['status'=>$status]);
          } catch (\Throwable $e) { return '#'; }
        };
      @endphp

      {{-- ======= Summary Action Item: PIE + CARDS ======= --}}
      {{-- <div class="row g-3 mt-3">
        <div class="col-12 col-lg-5">
          <div class="border rounded p-3 h-100 card-clip">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="small text-muted">
                <i class="bi bi-clipboard-check me-1"></i> Summary Action Item
              </div>
              <span class="badge bg-secondary">Total: {{ $ai['total'] }}</span>
            </div>
            <div style="position:relative;height:260px;max-height:260px;">
              <canvas id="aiChart" style="width:100%;height:100%;"></canvas>
            </div>
            <div class="small mt-2">
              <span class="legend-dot" style="background:#0ea5e9"></span> Open
              <span class="legend-dot" style="background:#a855f7"></span> Progress
              <span class="legend-dot" style="background:#ef4444"></span> Cancel
              <span class="legend-dot" style="background:#f59e0b"></span> Request Close
              <span class="legend-dot" style="background:#10b981"></span> Closed
              <span class="legend-dot" style="background:#6b7280"></span> Pending
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="row g-3">
            <div class="col-6 col-xl-4">
              <div class="ai-card ai-blue ai-link p-3 h-100" data-nav="{{ $aiRoute(null) }}" role="button" tabindex="0">
                <div class="ai-chip">Total</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-collection me-2"></i><span>Action Item</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiTotal }}">{{ $aiTotal }}</span></div>
              </div>
            </div>

            <div class="col-6 col-xl-4">
              <div class="ai-card ai-sky ai-link p-3 h-100" data-nav="{{ $aiRoute('open') }}" role="button" tabindex="0">
                <div class="ai-chip">Open</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-door-open me-2"></i><span>Open</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiOpen }}">{{ $aiOpen }}</span></div>
              </div>
            </div>

            <div class="col-6 col-xl-4">
              <div class="ai-card ai-purple ai-link p-3 h-100" data-nav="{{ $aiRoute('progress') }}" role="button" tabindex="0">
                <div class="ai-chip">Progress</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-activity me-2"></i><span>Progress</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiProg }}">{{ $aiProg }}</span></div>
              </div>
            </div>

            <div class="col-6 col-xl-4">
              <div class="ai-card ai-red ai-link p-3 h-100" data-nav="{{ $aiRoute('cancel') }}" role="button" tabindex="0">
                <div class="ai-chip">Cancel</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-x-octagon me-2"></i><span>Cancel</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiCancel }}">{{ $aiCancel }}</span></div>
              </div>
            </div>

            <div class="col-6 col-xl-4">
              <div class="ai-card ai-amber ai-link p-3 h-100" data-nav="{{ $aiRoute('request close') }}" role="button" tabindex="0">
                <div class="ai-chip">Request Close</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-envelope-check me-2"></i><span>Request Close</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiReqClose }}">{{ $aiReqClose }}</span></div>
              </div>
            </div>

            <div class="col-6 col-xl-4">
              <div class="ai-card ai-green ai-link p-3 h-100" data-nav="{{ $aiRoute('closed') }}" role="button" tabindex="0">
                <div class="ai-chip">Closed</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-check2-circle me-2"></i><span>Closed</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiClosed }}">{{ $aiClosed }}</span></div>
              </div>
            </div>

            {{-- NEW: Pending -}}
            {{-- <div class="col-6 col-xl-4">
              <div class="ai-card ai-gray ai-link p-3 h-100" data-nav="{{ $aiRoute('pending') }}" role="button" tabindex="0">
                <div class="ai-chip">Pending</div>
                <div class="small d-flex align-items-center mb-2">
                  <i class="bi bi-hourglass-split me-2"></i><span>Pending</span>
                </div>
                <div class="fs-3 fw-bold"><span class="countup" data-target="{{ $aiPending }}">{{ $aiPending }}</span></div>
              </div>
            </div>

          </div>
        </div>
      </div> --}}

    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
(function () {
  // ===== Chart (donut) – ambil angka dari kartu, total dari "Document Request" =====
  const wrap   = document.getElementById('docChartWrap');
  const canvas = document.getElementById('docChart');
  if (!wrap || !canvas) return;

  const getVal = (sel) => {
    const el = document.querySelector(`${sel} .countup`);
    return parseInt((el?.getAttribute('data-target') ?? el?.textContent ?? '0').toString().replace(/[^\d-]/g,''), 10) || 0;
  };

  // Angka dari kartu
  const vWaiting = getVal('.metric-card.metric-blue');    // Butuh Tindakan
  const vReturn  = getVal('.metric-card.metric-amber');   // Di Return
  const vProg    = getVal('.metric-card.metric-purple');  // Dalam Proses
  const vDone    = getVal('.metric-card.metric-green');   // Selesai
  const vReq     = getVal('.metric-card.metric-cyan');    // Document Request (jika ada)

  const baseData = [vWaiting, vReturn, vProg, vDone];
  const sumBase  = baseData.reduce((a,b)=>a+b,0);
  const total    = (vReq > 0 ? vReq : sumBase) || 1;

  function scaleToTotal(arr, T) {
    const S = arr.reduce((a,b)=>a+b,0);
    if (S === 0) return [0,0,0,0];
    if (S === T) return arr.slice();
    const raw = arr.map(v => v * (T / S));
    const flo = raw.map(Math.floor);
    let rem = T - flo.reduce((a,b)=>a+b,0);
    const order = raw.map((v,i)=>({i,frac:v - flo[i]})).sort((a,b)=>b.frac - a.frac);
    for (let k = 0; k < rem; k++) flo[order[k % order.length].i]++;
    return flo;
  }

  const data = (vReq > 0 && sumBase !== vReq) ? scaleToTotal(baseData, total) : baseData;

  const labels = ['Butuh Tindakan','Di Return','Dalam Proses','Selesai'];
  const ctx = canvas.getContext('2d');

  const gBlue   = ctx.createLinearGradient(0, 0, 0, canvas.height); gBlue.addColorStop(0,'#1d4ed8'); gBlue.addColorStop(1,'#3b82f6');
  const gAmber  = ctx.createLinearGradient(0, 0, 0, canvas.height); gAmber.addColorStop(0,'#b45309'); gAmber.addColorStop(1,'#f59e0b');
  const gPurple = ctx.createLinearGradient(0, 0, 0, canvas.height); gPurple.addColorStop(0,'#6d28d9'); gPurple.addColorStop(1,'#a855f7');
  const gGreen  = ctx.createLinearGradient(0, 0, 0, canvas.height); gGreen.addColorStop(0,'#047857'); gGreen.addColorStop(1,'#10b981');

  const colors  = [gBlue, gAmber, gPurple, gGreen];

  const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart){
      const meta = chart.getDatasetMeta(0);
      if (!meta?.data?.length) return;
      const {x, y} = meta.data[0];
      const c = chart.ctx;
      c.save();
      c.textAlign = 'center';
      c.textBaseline = 'middle';
      c.fillStyle = '#111827';
      c.font = '700 14px system-ui,-apple-system,Segoe UI,Roboto,sans-serif';
      c.fillText('Total', x, y - 12);
      c.font = '800 20px system-ui,-apple-system,Segoe UI,Roboto,sans-serif';
      c.fillText(String(total), x, y + 10);
      c.restore();
    }
  };

  if (window.__docChartInstance) { try { window.__docChartInstance.destroy(); } catch(e){} }

  window.__docChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderColor: '#ffffff',
        borderWidth: 2,
        borderRadius: 8,
        spacing: 3,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      animation: { animateScale: true, animateRotate: true, duration: 600 },
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } },
        tooltip: {
          callbacks: {
            label(ctx){
              const val = ctx.parsed ?? 0;
              const pct = Math.round((val / (total || 1)) * 100);
              return `${ctx.label}: ${val} (${pct}%)`;
            }
          }
        }
      }
    },
    plugins: [centerTextPlugin]
  });

  const miniTotal = document.querySelector('.fr-mini .d-flex .small.text-muted');
  if (miniTotal) miniTotal.textContent = 'Total: ' + total;

  // ===== Count-up & progress anim =====
  document.querySelectorAll('.countup').forEach(el => {
    const target = parseInt(el.getAttribute('data-target') || '0', 10);
    const dur = 700;
    const start = performance.now();
    const fmt = new Intl.NumberFormat();
    function tick(t){
      const p = Math.min(1, (t - start) / dur);
      const val = Math.round(target * (0.2 + 0.8 * p));
      el.textContent = fmt.format(Math.min(val, target));
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  });
  document.querySelectorAll('.doc-kpi__bar').forEach(bar => {
    const pct = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-progress') || '0')));
    requestAnimationFrame(() => { bar.style.setProperty('--w', pct + '%'); });
  });

  // Kartu Summary Document klik
  document.querySelectorAll('.doc-kpi--link').forEach(el => {
    el.style.cursor = 'pointer';
    el.setAttribute('tabindex','0');
    el.addEventListener('click', () => {
      const url = el.getAttribute('data-nav');
      if (url) window.location.href = url;
    });
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });

  // Mini KPI (siklus & jenis) klik
  document.querySelectorAll('.mini-kpi').forEach(el => {
    el.style.cursor = 'pointer';
    el.addEventListener('click', () => {
      const url = el.getAttribute('data-nav');
      if (url) window.location.href = url;
    });
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });

  // Kartu Summary Form Review klik
  document.querySelectorAll('.fr-link').forEach(el => {
    el.style.cursor = 'pointer';
    el.addEventListener('click', () => {
      const url = el.getAttribute('data-nav');
      if (url) window.location.href = url;
    });
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });

  // ===== Pie Action Item =====
  (function () {
    const el = document.getElementById('aiChart');
    if (!el) return;

    const aiLabels = @json($ai['labels']);
    const aiData   = [
      {{ (int)($ai['byStatus']['open'] ?? 0) }},
      {{ (int)($ai['byStatus']['progress'] ?? 0) }},
      {{ (int)($ai['byStatus']['cancel'] ?? 0) }},
      {{ (int)($ai['byStatus']['request close'] ?? 0) }},
      {{ (int)($ai['byStatus']['closed'] ?? 0) }},
      {{ (int)($ai['byStatus']['pending'] ?? 0) }},
    ];
    const totalAI = aiData.reduce((a,b)=>a+b,0) || 1;

    const ctx = el.getContext('2d');
    const colors = ['#0ea5e9','#a855f7','#ef4444','#f59e0b','#10b981','#6b7280']; // + pending gray

    if (window.__aiChartInstance) { try { window.__aiChartInstance.destroy(); } catch(e){} }

    window.__aiChartInstance = new Chart(ctx, {
      type: 'pie',
      data: { labels: aiLabels, datasets: [{ data: aiData, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 2, hoverOffset: 8, spacing: 2 }] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { animateRotate: true, animateScale: true, duration: 600 },
        plugins: {
          legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } },
          tooltip: {
            callbacks: {
              label(ctx){
                const v = ctx.parsed ?? 0;
                const pct = Math.round((v/totalAI)*100);
                return `${ctx.label}: ${v} (${pct}%)`;
              }
            }
          }
        }
      }
    });
  })();

})();
</script>

<style>
.fw-600{font-weight:600}
.card-clip { overflow: hidden; }
.dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
:root{ --doc-kpi-num-size: 2.8rem; }
.doc-kpi{
  position: relative; background: #111827; color: #fff; border-radius: 14px;
  padding: 16px 16px 14px; overflow: hidden;
  box-shadow: 0 14px 30px -12px rgba(0,0,0,.35);
  border: 1px solid rgba(255,255,255,.08);
  transition: transform .18s cubic-bezier(.2,.8,.2,1), box-shadow .18s ease;
  display:flex; flex-direction:column;
}
.doc-kpi:hover,
.doc-kpi:focus-within{ transform: translateY(-6px) scale(1.01); }
.doc-kpi:hover::before,
.doc-kpi:focus-within::before{
  content:""; position:absolute; inset:-1px; border-radius:14px;
  box-shadow: inset 0 0 0 2px rgba(255,255,255,.28); pointer-events:none;
}
.doc-kpi__head{ display:flex; align-items:center; gap:.4rem; font-weight:700; letter-spacing:.2px; opacity:.95; }
.doc-kpi__chip{ margin-left:auto; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
  padding:.15rem .5rem; font-size:.75rem; border-radius:.4rem; font-weight:700; }
.doc-kpi__num{ font-size: var(--doc-kpi-num-size); font-weight:800; line-height:1; margin-top:.25rem; text-shadow: 0 2px 4px rgba(0,0,0,.25); }
.doc-kpi__progress{ height: 10px; margin-top:10px; border-radius: 999px; background: rgba(255,255,255,.22);
  border: 1px solid rgba(255,255,255,.32); overflow:hidden; }
.doc-kpi__progress--ghost{ visibility: hidden; }
.doc-kpi__bar{ --w: 0%; height:100%; width: var(--w); transition: width .8s cubic-bezier(.2,.8,.2,1);
  background: linear-gradient(90deg, rgba(255,255,255,.95), rgba(255,255,255,.7)); }
.doc-kpi--blue  { background: linear-gradient(135deg,#0b5ed7 0%, #1363ff 100%); }
.doc-kpi--green { background: linear-gradient(135deg,#0f766e 0%, #16a34a 100%); }
.doc-kpi--red   { background: linear-gradient(135deg,#b91c1c 0%, #ef4444 100%); }
.doc-kpi--link:focus { outline: 3px solid rgba(255,255,255,.6); outline-offset: 2px; }

.doc-stack__bar{ height: 12px; border-radius: 999px; overflow: hidden; background: #e5e7eb; border:1px solid #d1d5db; display:flex; }
.doc-stack__seg{ height:100%; }
.doc-stack__legend{ display:flex; align-items:center; gap:1rem; margin-top:.35rem; color:#111827; font-weight:600; }

.doc-matrix{
  border-collapse: separate;
  border-spacing: 0;
  overflow: hidden;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 10px 24px -16px rgba(0,0,0,.18);
}
.doc-matrix thead th{
  position: sticky; top: 0; z-index: 2;
  background: #0f172a; color:#fff;
  white-space: nowrap;
}
.doc-matrix th, .doc-matrix td{
  border: 1px solid #e5e7eb;
  padding: .55rem .7rem;
}
.doc-matrix .sticky-col{
  position: sticky; left: 0; z-index: 3;
  background: #f8fafc;
  min-width: 180px;
}
.doc-matrix tbody tr:nth-child(odd) .sticky-col{ background:#f1f5f9; }
.doc-matrix td{ text-align: center; }

.metric-card{ position:relative; border-radius:14px; color:#fff; border:none; box-shadow:0 12px 24px -10px rgba(16,24,40,.25); overflow:hidden;
  transition: transform .18s cubic-bezier(.2,.8,.2,1), box-shadow .18s ease; }
.metric-card .metric-icon{ color:rgba(255,255,255,.95); }
.metric-card .fs-3{ text-shadow:0 2px 4px rgba(0,0,0,.25); }
.metric-card .text-black-50{ color:rgba(255,255,255,.85) !important; }
.metric-blue{  background:linear-gradient(135deg,#1d4ed8 0%, #3b82f6 100%); }
.metric-amber{ background:linear-gradient(135deg,#b45309 0%, #f59e0b 100%); }
.metric-purple{background:linear-gradient(135deg,#6d28d9 0%, #a855f7 100%); }
.metric-green{ background:linear-gradient(135deg,#047857 0%, #10b981 100%); }
.metric-cyan{  background:linear-gradient(135deg,#0891b2 0%, #06b6d4 100%); }
.metric-card:hover,
.metric-card:focus-within{ transform: translateY(-6px); box-shadow:0 22px 50px -14px rgba(0,0,0,.45); }
.metric-card::after{ content:''; position:absolute; inset:0; background:radial-gradient(120% 80% at -10% -20%, rgba(255,255,255,.25), transparent 60%); pointer-events:none; }
.metric-title{ font-size:.95rem; color:rgba(255,255,255,.9); }
.metric-value{ font-weight:800; line-height:1; text-shadow:0 2px 4px rgba(0,0,0,.25); overflow:hidden; }
.metric-value--lg{ font-size:2rem; }
@media (min-width: 768px){ .metric-value--lg{ font-size:2.1rem; } }
@media (min-width:1200px){ .metric-value--lg{ font-size:2.25rem; } }

.fr-mini__bar{
  height: 8px; border-radius: 999px; background: #e5e7eb; border: 1px solid #d1d5db;
  overflow: hidden; position: relative; box-shadow: inset 0 1px 0 rgba(255,255,255,.5); display: flex;
}
.fr-mini__seg{ height: 100%; flex: 0 0 auto; }
.fr-mini__seg--blue   { background: #3b82f6; }
.fr-mini__seg--amber  { background: #f59e0b; }
.fr-mini__seg--purple { background: #a855f7; }
.fr-mini__seg--green  { background: #10b981; }
.dot--blue{background:#3b82f6}.dot--amber{background:#f59e0b}.dot--purple{background:#a855f7}.dot--green{background:#10b981}

.mini-kpi{
  background: var(--mini-bg);
  color:#fff; border-radius:12px; padding:10px 12px;
  box-shadow: 0 10px 24px -12px rgba(0,0,0,.35);
  border: 1px solid rgba(255,255,255,.18);
  transition: transform .15s cubic-bezier(.2,.8,.2,1), box-shadow .15s ease;
}
.mini-kpi:hover,.mini-kpi:focus-within{ transform: translateY(-4px); box-shadow:0 18px 36px -16px rgba(0,0,0,.4); outline: none; }
.mini-kpi__head{ display:flex; align-items:center; gap:.5rem; }
.mini-kpi__title{ font-weight:700; }
.mini-kpi__chip{ margin-left:auto; background: rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28);
  padding:.1rem .45rem; font-size:.73rem; border-radius:.4rem; font-weight:800; }
.mini-kpi__num{ font-weight:800; font-size:1.35rem; line-height:1; margin-top:.2rem; }

.fr-link:focus { outline: 3-x solid rgba(255,255,255,.6); outline-offset: 2px; }

.legend-dot{
  display:inline-block; width:10px; height:10px; border-radius:50%;
  margin-right:6px; margin-left:10px; vertical-align:middle;
}

.ai-card{
  position:relative; border:none; border-radius:14px; color:#fff; overflow:hidden;
  box-shadow:0 12px 24px -10px rgba(16,24,40,.25);
  transition: transform .18s cubic-bezier(.2,.8,.2,1), box-shadow .18s ease;
}
.ai-card:hover,.ai-card:focus-within{ transform: translateY(-6px); box-shadow:0 22px 50px -14px rgba(0,0,0,.45); }
.ai-card::after{
  content:''; position:absolute; inset:0;
  background:radial-gradient(120% 80% at -10% -20%, rgba(255,255,255,.25), transparent 60%);
  pointer-events:none;
}
.ai-chip{
  position:absolute; top:10px; right:10px;
  font-size:.72rem; font-weight:800; line-height:1;
  background: rgba(255,255,255,.22);
  border:1px solid rgba(255,255,255,.28);
  padding:.2rem .5rem; border-radius:999px;
}
.ai-blue   { background:linear-gradient(135deg,#1d4ed8 0%, #3b82f6 100%); }
.ai-sky    { background:linear-gradient(135deg,#0284c7 0%, #0ea5e9 100%); }
.ai-purple { background:linear-gradient(135deg,#6d28d9 0%, #a855f7 100%); }
.ai-red    { background:linear-gradient(135deg,#b91c1c 0%, #ef4444 100%); }
.ai-amber  { background:linear-gradient(135deg,#b45309 0%, #f59e0b 100%); }
.ai-green  { background:linear-gradient(135deg,#047857 0%, #10b981 100%); }
.ai-gray   { background:linear-gradient(135deg,#6b7280 0%, #9ca3af 100%); } /* Pending */

.custom-thead th {
  border: 3px solid #f4f4f4; /* garis tegas di bawah header */
  border-radius: 10px;
  background-color: #0098DE !important;
  background-clip: padding-box;

  /* Tambahan supaya font tidak terlalu tebal & pakai Poppins */
  font-family: 'Poppins', sans-serif;
  font-weight: 400; /* 400 = normal, bisa 500 kalau mau sedikit tebal */
  color: white; /* biar kontras sama bg biru */
}

.doc-matrix {
  background-color: #f4f4f4 !important;
}

.custom-table a:hover {
  text-decoration: underline !important;
}
.custom-table th {
  font-family: 'Poppins', sans-serif;
  font-weight: 400;
}
</style>
@endsection