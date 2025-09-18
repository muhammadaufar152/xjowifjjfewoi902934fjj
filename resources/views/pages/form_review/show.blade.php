@extends('layouts.app-document')

@push('styles')
  {{-- Bootstrap Icons (dipakai untuk ikon status) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

@section('content')
@php
  use Illuminate\Support\Facades\Storage;
  use Carbon\Carbon;

  // === Normalisasi status global -> seragam di semua halaman ===
  $raw = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $review->status ?? ''))));
  if ($raw === 'open') $raw = 'pending';

  // === Mapping tampilan ===
  $cls='secondary'; $ico='bi-hourglass-split'; $lbl='Open'; $sub=null;
  if (in_array($raw, ['verifikasi 1','verifikasi 2','verifikasi 3'], true)) {
    $cls='primary'; $ico='bi-activity'; $lbl='Progress';
    $sub = 'Verifikasi ' . explode(' ', $raw)[1];
  } elseif ($raw === 'return') {
    $cls='warning'; $ico='bi-arrow-counterclockwise'; $lbl='Return';
  } elseif ($raw === 'sirkulir') {
    $cls='info'; $ico='bi-flag'; $lbl='Sirkulir';
  } elseif ($raw === 'selesai') {
    $cls='success'; $ico='bi-check-circle'; $lbl='Done';
  } elseif ($raw === 'pending') {
    $cls='secondary'; $ico='bi-hourglass-split'; $lbl='Open';
  } else {
    $cls='light text-dark'; $ico='bi-question-circle'; $lbl=$review->status ?? '-';
  }

  // tanggal approval (opsional, dari step AVP)
  $avpStep = ($review->steps ?? collect())->first(fn($s) => strtolower($s->tahapan ?? '') === 'avp' && !empty($s->tanggal));
  $tanggalApproval = $avpStep ? Carbon::parse($avpStep->tanggal)->format('Y-m-d') : ($review->tanggal_approval ?? '-');

  // ===== File sirkulir dari approval =====
  $sf = $review->sirkulirFiles ?? collect();
  $sfSorted = $sf->sortByDesc(fn($f) => $f->created_at ?? now())->values();
  $firstTwo = $sfSorted->take(2);
  $rest     = $sfSorted->slice(2);

  // ===== 2 file terakhir dari BPO (yang menandai DONE) =====
  $bpoTwo = method_exists($review,'bpoUploads')
            ? $review->bpoUploads()->latest()->take(2)->get()
            : collect();

  // ===== Riwayat tanpa "Pending" =====
  $stepsNoPending = ($review->steps ?? collect())->filter(function($s){
    $st = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $s->status ?? ''))));
    return $st !== 'pending' && $st !== 'menunggu';
  })->values();
@endphp

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <h4 class="fw-bold bg-light p-2 mb-3">Detail Form Review Dokumen</h4>

      <div class="border rounded p-3 bg-white">
        <p><strong>Tanggal Masuk:</strong> {{ $review->tanggal_masuk }}</p>
        <p><strong>Jenis Permohonan:</strong> {{ $review->jenis_permohonan }}</p>
        <p><strong>Latar Belakang:</strong> {{ $review->latar_belakang ?? '-' }}</p>
        <p><strong>Usulan Revisi:</strong> {{ $review->usulan_revisi ?? '-' }}</p>
        <p><strong>Jenis Dokumen:</strong> {{ $review->jenis_dokumen }}</p>
        <p><strong>Klasifikasi Siklus:</strong> {{ $review->klasifikasi_siklus }}</p>
        <p><strong>Nama Dokumen:</strong> {{ $review->nama_dokumen }}</p>
        <p><strong>No Dokumen:</strong> {{ $review->no_dokumen }}</p>
        <p><strong>Level Dokumen:</strong> {{ $review->level_dokumen ?? '-' }}</p>
        <p><strong>Perihal:</strong> {{ $review->perihal ?? '-' }}</p>
        <p><strong>BPO:</strong> {{ $review->bpoUser->name ?? '-' }}</p>

        {{-- STATUS sinkron --}}
        <p class="mb-1">
          <strong>Status:</strong>
          <span class="badge bg-{{ $cls }}"><i class="bi {{ $ico }} me-1"></i>{{ $lbl }}</span>
          @if($sub)<small class="text-muted ms-2">{{ $sub }}</small>@endif
        </p>

        <p><strong>Tanggal Approval:</strong> {{ $tanggalApproval }}</p>

        <p class="mb-0"><strong>Lampiran:</strong>
          @if ($review->lampiran)
            <a href="{{ route('form_review.download', $review->id) }}" target="_blank">Lihat file</a>
          @else
            Tidak ada
          @endif
        </p>
      </div>

      {{-- ===== Lampiran (dari Approval) — 2 terbaru + tombol lihat semua ===== --}}
      @if($sfSorted->count())
        <div class="border rounded p-3 bg-white mt-3">
          <h6 class="fw-bold mb-2"><i class="bi bi-paperclip me-2"></i>File sirkulir (Officer/Manager/AVP)</h6>

          {{-- 2 TERBARU --}}
          <ul class="list-group list-group-flush" id="files-brief">
            @foreach($firstTwo as $f)
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span>
                  <a href="{{ Storage::disk('public')->url($f->path) }}" target="_blank" class="text-decoration-none">
                    {{ $f->original_name ?? basename($f->path) }}
                  </a>
                  <span class="text-muted ms-2">
                    · <strong class="text-uppercase">{{ $f->uploaded_role }}</strong>
                    @if($f->created_at) · {{ $f->created_at->format('d/m/Y H:i') }} @endif
                  </span>
                </span>
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ Storage::disk('public')->url($f->path) }}">Lihat</a>
              </li>
            @endforeach
          </ul>

          {{-- SISA FILE (disembunyikan dulu) --}}
          @if($rest->count())
            <button class="btn btn-link p-0 mt-2" id="toggle-all"
                    data-count="{{ $rest->count() }}">
              Lihat keseluruhan ({{ $rest->count() }})
            </button>

            <ul class="list-group list-group-flush mt-2" id="files-all" style="display:none;">
              @foreach($rest as $f)
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                  <span>
                    <a href="{{ Storage::disk('public')->url($f->path) }}" target="_blank" class="text-decoration-none">
                      {{ $f->original_name ?? basename($f->path) }}
                    </a>
                    <span class="text-muted ms-2">
                      · <strong class="text-uppercase">{{ $f->uploaded_role }}</strong>
                      @if($f->created_at) · {{ $f->created_at->format('d/m/Y H:i') }} @endif
                    </span>
                  </span>
                  <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ Storage::disk('public')->url($f->path) }}">Lihat</a>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @endif

      {{-- ===== File revisi (BPO) — penanda DONE ===== --}}
      @if($bpoTwo->count())
        <div class="border rounded p-3 bg-white mt-3">
          <h6 class="fw-bold mb-2"><i class="bi bi-folder2-open me-2"></i>File revisi (BPO) — pemenuhan <span class="badge bg-success">Done</span></h6>
          <ul class="list-group list-group-flush">
            @foreach($bpoTwo as $f)
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span>
                  <a href="{{ route('form_review.bpo_file', $f->id) }}" target="_blank" class="text-decoration-none">
                    {{ $f->original_name ?? basename($f->path) }}
                  </a>
                  <span class="text-muted ms-2">· BPO · {{ $f->created_at?->format('d/m/Y H:i') }}</span>
                </span>
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('form_review.bpo_file', $f->id) }}">Lihat</a>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- ===== Riwayat Approval (tanpa Pending) ===== --}}
      @if($stepsNoPending->count() > 0)
        <div class="border rounded p-3 bg-white mt-3">
          <h6 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Riwayat Approval</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>No</th>
                  <th>Tahapan</th>
                  <th>Status</th>
                  <th>Verifikator</th>
                  <th>Tanggal</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
              @foreach($stepsNoPending as $step)
                @php
                  $sr = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', ($step->status ?? '')))));
                  $aliases = [
                    'open'=>'pending','tidak-setuju'=>'tidak setuju',
                    'verifikasi1'=>'verifikasi 1','verifikasi2'=>'verifikasi 2','verifikasi3'=>'verifikasi 3'
                  ];
                  if (isset($aliases[$sr])) $sr = $aliases[$sr];

                  $map = [
                    'setuju'        => ['success','bi-check2-circle','Setuju',''],
                    'tidak setuju'  => ['warning','bi-x-circle','Tidak Setuju','text-dark'],
                    'return'        => ['warning','bi-arrow-counterclockwise','Return',''],
                    'resubmit'      => ['info','bi-arrow-repeat','Resubmit',''],
                    'verifikasi 1'  => ['success','bi-1-circle','Verifikasi 1',''],
                    'verifikasi 2'  => ['info','bi-2-circle','Verifikasi 2',''],
                    'verifikasi 3'  => ['primary','bi-3-circle','Verifikasi 3',''],
                    'sirkulir'      => ['info','bi-flag','Sirkulir',''],
                    'selesai'       => ['success','bi-check-circle','Selesai',''],
                  ];
                  [$bg,$ic,$lblx,$extra] = $map[$sr] ?? ['light','bi-question-circle',($step->status ?? '-'),'text-dark'];
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td><span class="badge bg-primary">{{ ucfirst($step->tahapan) }}</span></td>
                  <td>
                    <span class="badge bg-{{ $bg }} {{ $extra }}"><i class="bi {{ $ic }} me-1"></i>{{ $lblx }}</span>
                  </td>
                  <td>{{ $step->verifikator }}</td>
                  <td>{{ \Carbon\Carbon::parse($step->tanggal ?? $step->created_at)->format('d/m/Y') }}</td>
                  <td class="text-muted">{{ $step->keterangan ?? '-' }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      <div class="mt-3 text-end">
        <a href="{{ route('form_review.index') }}" class="btn btn-secondary">Back</a>
      </div>

    </div>
  </div>
</div>

{{-- Toggle "Lihat keseluruhan" --}}
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('toggle-all');
  if (!btn) return;
  const all = document.getElementById('files-all');
  const count = btn.getAttribute('data-count') || '';
  const openLabel = 'Sembunyikan';
  const closeLabel = `Lihat keseluruhan (${count})`;
  let open = false;

  btn.addEventListener('click', function(e){
    e.preventDefault();
    open = !open;
    all.style.display = open ? '' : 'none';
    btn.textContent = open ? openLabel : closeLabel;
  });
});
</script>
@endsection
