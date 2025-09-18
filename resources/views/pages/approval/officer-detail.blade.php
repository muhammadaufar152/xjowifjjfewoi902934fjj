@extends('layouts.app-document')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    // === Normalisasi status & mapping tampilan (sama dengan Manager Detail) ===
    $statusLower = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $review->status ?? ''))));
    if ($statusLower === 'open') $statusLower = 'pending';

    $badgeClass = 'secondary';
    $badgeIcon  = 'bi-hourglass-split';
    $badgeText  = 'Open';
    $badgeSub   = null;

    if (in_array($statusLower, ['verifikasi 1','verifikasi 2','verifikasi 3'], true)) {
        $badgeClass = 'primary';
        $badgeIcon  = 'bi-activity';
        $badgeText  = 'Progress';
        $badgeSub   = 'Verifikasi ' . explode(' ', $statusLower)[1];
    } elseif ($statusLower === 'sirkulir') {
        $badgeClass = 'success';
        $badgeIcon  = 'bi-flag';
        $badgeText  = 'Sirkulir';
    } elseif ($statusLower === 'selesai') {
        $badgeClass = 'success';
        $badgeIcon  = 'bi-check-circle';
        $badgeText  = 'Done';
    } elseif ($statusLower === 'return') {
        $badgeClass = 'warning';
        $badgeIcon  = 'bi-arrow-counterclockwise';
        $badgeText  = 'Return';
    } elseif ($statusLower === 'pending') {
        $badgeClass = 'secondary';
        $badgeIcon  = 'bi-hourglass-split';
        $badgeText  = 'Open';
    } else {
        $badgeClass = 'light text-dark';
        $badgeIcon  = 'bi-question-circle';
        $badgeText  = $review->status ?? '-';
    }

    $tglMasukStr = ($review->tanggal_masuk instanceof \Carbon\Carbon)
        ? $review->tanggal_masuk->format('Y-m-d H:i:s')
        : (is_string($review->tanggal_masuk ?? null) ? $review->tanggal_masuk : '-');

    // giliran Officer seperti sebelumnya
    $last = $review->steps
        ? $review->steps->sortByDesc('tanggal')->sortByDesc('id')->first()
        : null;
    $lastTahap  = strtolower($last->tahapan ?? '');
    $lastStatus = strtolower($last->status ?? '');

    $atOfficerInitial = ($statusLower === 'pending')
                        && in_array($lastTahap, ['officer', '', null], true)
                        && in_array($lastStatus, ['pending', 'menunggu', '', null], true);

    $managerReturned  = ($statusLower === 'pending')
                        && ($lastTahap === 'manager')
                        && in_array($lastStatus, ['tidak setuju','return'], true);

    $officerTurn = $atOfficerInitial || $managerReturned;

    $hasPdf = !empty($review->pdf_path);

    // File sirkulir (legacy)
    $sirkulir1 = $review->sirkulir_file_1 ?? null;
    $sirkulir2 = $review->sirkulir_file_2 ?? null;

    // <<<<<< NEW: flag supaya bagian sirkulir tetap tampil kalau sudah ada file
    $hasSirkulirFiles = ($review->sirkulirFiles && $review->sirkulirFiles->count()) || $sirkulir1 || $sirkulir2;

    // === BPO uploads (fix: TANPA skip/OFFSET). Ambil semua → 2 terbaru + sisanya ===
    $bpoAll   = method_exists($review, 'bpoUploads') ? $review->bpoUploads()->latest()->get() : collect();
    $bpoTwo   = $bpoAll->take(2);
    $bpoCount = $bpoAll->count();
    $bpoRest  = $bpoAll->slice(2);
@endphp

<div class="container mt-5">
  <h4 class="fw-bold text-center mb-4">Detail Dokumen - Officer</h4>

  {{-- Info --}}
  <div class="card mb-4">
    <div class="card-header bg-light">
      <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Informasi Dokumen</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="mb-2"><strong>Nama Dokumen:</strong> <span class="text-primary">{{ $review->nama_dokumen ?? '-' }}</span></div>
          <div class="mb-2"><strong>No Dokumen:</strong> <span class="text-primary">{{ $review->no_dokumen ?? '-' }}</span></div>
          <div class="mb-2"><strong>Tanggal Masuk:</strong> <span class="text-primary">{{ $tglMasukStr }}</span></div>
        </div>
        <div class="col-md-6">
          <div class="mb-2"><strong>Jenis Dokumen:</strong> <span class="text-primary">{{ $review->jenis_dokumen ?? '-' }}</span></div>
          <div class="mb-2"><strong>Level Dokumen:</strong> <span class="text-primary">{{ $review->level_dokumen ?? '-' }}</span></div>
          <div class="mb-2">
            <strong>Status:</strong>
            <span class="badge bg-{{ $badgeClass }}"><i class="bi {{ $badgeIcon }} me-1"></i>{{ $badgeText }}</span>
            @if($badgeSub)
              <div class="small text-muted mt-1">{{ $badgeSub }}</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Lampiran --}}
  <div class="card mb-4">
    <div class="card-header bg-light">
      <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>File Lampiran</h6>
    </div>
    <div class="card-body">
      @if ($review->lampiran)
        <div class="d-flex align-items-center">
          <div class="me-3"><i class="fas fa-file-alt fa-2x text-primary"></i></div>
          <div class="flex-grow-1">
            <h6 class="mb-1">File Lampiran</h6>
            <p class="text-muted mb-2">{{ basename($review->lampiran) }}</p>
            <a href="{{ route('form_review.download', $review->id) }}" target="_blank" class="btn btn-primary btn-sm">
              <i class="bi bi-eye me-1"></i>Lihat File
            </a>
          </div>
        </div>
      @else
        <div class="text-center py-3 text-muted">Tidak ada file lampiran</div>
      @endif
    </div>
  </div>

  {{-- ===== Upload Sirkulir ===== --}}
  @if ($statusLower === 'sirkulir' || $hasSirkulirFiles)
    <div class="card mb-4">
      <div class="card-header bg-light d-flex align-items-center">
        <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Upload File Sirkulir ke BPO</h6>
      </div>
      <div class="card-body">
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle me-2"></i>
          <div>Unggah maksimal 2 file. File ini akan tampil sebagai lampiran di halaman BPO.</div>
        </div>

        {{-- === DAFTAR SEMUA FILE SIRKULIR (terbaru → lama) === --}}
        @if($review->sirkulirFiles && $review->sirkulirFiles->count())
          <div class="mb-3">
            <div class="fw-semibold mb-2">
              <i class="bi bi-paperclip me-1"></i>Semua File Sirkulir (terbaru → lama)
            </div>
            <ul class="mb-0">
              @foreach(($review->sirkulirFiles ?? collect())->sortByDesc('created_at') as $sf)
                <li class="mb-1">
                  <a target="_blank" href="{{ Storage::disk('public')->url($sf->path) }}">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i>
                    {{ $sf->original_name ?? basename($sf->path) }}
                  </a>
                  <small class="text-muted">
                    • {{ strtoupper($sf->uploaded_role) }} • {{ $sf->created_at?->format('d/m/Y H:i') }}
                  </small>
                </li>
              @endforeach
            </ul>
          </div>
        @elseif($sirkulir1 || $sirkulir2)
          {{-- fallback lama --}}
          <div class="mb-3">
            <div class="fw-semibold mb-2"><i class="bi bi-paperclip me-1"></i>File Sirkulir yang sudah ada</div>
            <ul class="mb-0">
              @if($sirkulir1)
                <li><a target="_blank" href="{{ Storage::disk('public')->url($sirkulir1) }}"><i class="bi bi-file-earmark-arrow-down me-1"></i> File 1</a></li>
              @endif
              @if($sirkulir2)
                <li><a target="_blank" href="{{ Storage::disk('public')->url($sirkulir2) }}"><i class="bi bi-file-earmark-arrow-down me-1"></i> File 2</a></li>
              @endif
            </ul>
          </div>
        @endif

        {{-- FORM upload: aktif hanya saat status = sirkulir --}}
        @if ($statusLower === 'sirkulir')
          <form action="{{ route('approval.officer.sirkulir.upload', $review->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
              </div>
            @endif

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-file-earmark-plus me-1"></i>File 1</label>
                <input type="file" name="file1" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                <div class="form-text">Maks 20MB</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-file-earmark-plus me-1"></i>File 2</label>
                <input type="file" name="file2" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                <div class="form-text">Maks 20MB</div>
              </div>
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-cloud-arrow-up me-1"></i> Unggah
              </button>
            </div>
          </form>
        @else
          <div class="small text-muted">Status bukan <em>Sirkulir</em>; unggah file baru dinonaktifkan, namun daftar file tetap ditampilkan.</div>
        @endif
      </div>
    </div>
  @endif

  {{-- ====== File setelah revisi (BPO) — 2 terbaru + lihat semua ====== --}}
  <div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="bi bi-folder2-open me-2"></i>File setelah revisi (BPO)</h6>
      @if($bpoCount > 2)
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#bpoAllOfficer{{ $review->id }}">
          Lihat semua ({{ $bpoCount }})
        </button>
      @endif
    </div>
    <div class="card-body">
      @if($bpoTwo->count())
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:50%">Nama File</th>
                <th>Keterangan</th>
                <th style="width:160px">Tanggal</th>
                <th style="width:90px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($bpoTwo as $f)
                <tr>
                  <td class="text-truncate">{{ $f->original_name ?: ($f->path ? basename($f->path) : '(tanpa nama)') }}</td>
                  <td class="text-muted">{{ $f->keterangan ?: '-' }}</td>
                  <td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('form_review.bpo_file', $f->id) }}">Lihat</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if($bpoCount > 2)
          <div class="collapse mt-3" id="bpoAllOfficer{{ $review->id }}">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:50%">Nama File</th>
                    <th>Keterangan</th>
                    <th style="width:160px">Tanggal</th>
                    <th style="width:90px">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($bpoRest as $f)
                    <tr>
                      <td class="text-truncate">{{ $f->original_name ?: ($f->path ? basename($f->path) : '(tanpa nama)') }}</td>
                      <td class="text-muted">{{ $f->keterangan ?: '-' }}</td>
                      <td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                      <td>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('form_review.bpo_file', $f->id) }}">Lihat</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

      @else
        <div class="text-muted">Belum ada file dari BPO.</div>
      @endif
    </div>
  </div>

  {{-- ===== (Bagian Form Hasil Review & Executive Summary — original kamu) ===== --}}
  @php /* bagian di bawah ini tidak diubah */ @endphp

  <div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="fas fa-pen-to-square me-2"></i>Form Hasil Review & Executive Summary</h6>
      @if($hasPdf)
        <a href="{{ route('form_review.pdf', $review->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-eye me-1"></i> Lihat PDF
        </a>
      @endif
    </div>

    <div class="card-body">
      @if ($officerTurn)
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif

        <form id="generatePdfForm" action="{{ route('form_review.generate_pdf', $review->id) }}" method="POST">
          @csrf
          <input type="hidden" name="action" value="generate">

          {{-- Identitas --}}
          <h6 class="mb-2">Identitas Dokumen</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Nama Dokumen</label>
              <input type="text" name="pdf_meta[nama_dokumen]" class="form-control"
                     value="{{ old('pdf_meta.nama_dokumen', $review->nama_dokumen) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nomor Dokumen</label>
              <input type="text" name="pdf_meta[no_dokumen]" class="form-control"
                     value="{{ old('pdf_meta.no_dokumen', $review->no_dokumen) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Jenis Dokumen</label>
              <input type="text" name="pdf_meta[jenis_dokumen]" class="form-control"
                     value="{{ old('pdf_meta.jenis_dokumen', $review->jenis_dokumen) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Klasifikasi Siklus</label>
              <input type="text" name="pdf_meta[klasifikasi_siklus]" class="form-control"
                     value="{{ old('pdf_meta.klasifikasi_siklus', $review->klasifikasi_siklus) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Business Process Owner (BPO)</label>
              <input type="text" name="pdf_meta[bpo]" class="form-control"
                     value="{{ old('pdf_meta.bpo', $review->bpoUser->name ?? '') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Permohonan</label>
              <input type="date" name="pdf_meta[tanggal_permohonan]" class="form-control"
                     value="{{ old('pdf_meta.tanggal_permohonan',
                          ($review->tanggal_masuk instanceof \Carbon\Carbon)
                          ? $review->tanggal_masuk->format('Y-m-d')
                          : (is_string($review->tanggal_masuk ?? null) ? \Carbon\Carbon::parse($review->tanggal_masuk)->format('Y-m-d') : '')
                     ) }}">
            </div>
          </div>

          <hr>

          <h6 class="mb-2">Hasil Review</h6>
          <textarea id="review_textarea" class="form-control" rows="4"
                    placeholder="Tulis poin hasil review di sini (tanpa penomoran otomatis)"></textarea>
          <div id="review-hidden"></div>

          <div class="mt-3">
            <label class="form-label fw-bold">Rekomendasi</label>
            <textarea name="rekomendasi" class="form-control" rows="3"></textarea>
          </div>

          <hr>

          <h6 class="mb-2">B. Executive Summary</h6>
          <div class="mb-2">
            <label class="form-label">Latar Belakang Permohonan</label>
            <textarea name="exec_summary[latar_belakang]" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Maksud &amp; Tujuan Permohonan</label>
            <textarea name="exec_summary[maksud_tujuan]" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Ruang Lingkup Permohonan</label>
            <textarea id="scope_textarea" class="form-control" rows="3"
                      placeholder="Satu item per baris (tanpa penomoran otomatis)"></textarea>
            <div id="scope-hidden"></div>
          </div>
          <div class="mb-2">
            <label class="form-label">Ketentuan yang Dicabut</label>
            <textarea name="exec_summary[ketentuan_dicabut]" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-2" style="max-width:280px;">
            <label class="form-label">Tanggal Berlaku Dokumen</label>
            <input type="date" name="exec_summary[tanggal_berlaku]" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Lain-lain</label>
            <textarea name="exec_summary[lain_lain]" class="form-control" rows="2"></textarea>
          </div>
          <div class="small text-muted border p-2 rounded">
            <strong>Disclaimer Clause:</strong><br>
            Executive Summary ini hanyalah ringkasan dan bukan rekomendasi dalam pengambilan keputusan.
          </div>

          <hr>

          {{-- C. Tanda Tangan & Tanggal (opsional) --}}
          <h6 class="mb-2">C. Tanda Tangan & Tanggal</h6>
          <div class="row g-3">
            <div class="col-md-4 text-center">
              <label class="form-label fw-bold">Dibuat Oleh - Nama</label>
              <input type="text" name="signers[dibuat][nama]" class="form-control text-center">
              <label class="form-label mt-2">Jabatan</label>
              <input type="text" name="signers[dibuat][jabatan]" class="form-control text-center">
            </div>
            <div class="col-md-4 text-center">
              <label class="form-label fw-bold">Ditinjau Oleh - Nama</label>
              <input type="text" name="signers[ditinjau][nama]" class="form-control text-center">
              <label class="form-label mt-2">Jabatan</label>
              <input type="text" name="signers[ditinjau][jabatan]" class="form-control text-center">
            </div>
            <div class="col-md-4 text-center">
              <label class="form-label fw-bold">Disetujui Oleh - Nama</label>
              <input type="text" name="signers[disetujui][nama]" class="form-control text-center">
              <label class="form-label mt-2">Jabatan</label>
              <input type="text" name="signers[disetujui][jabatan]" class="form-control text-center">
            </div>
          </div>

          <div class="mt-3" style="max-width:420px;">
            <label class="form-label fw-bold">Lokasi & Tanggal</label>
            <input type="text" name="lokasi_tanggal" class="form-control" placeholder="cth: Bogor, 11 Agustus 2025">
          </div>

          <div class="mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i> Simpan PDF
            </button>
          </div>
        </form>
      @else
        <div class="text-muted">Gunakan tombol di kanan atas untuk melihat Form Hasil Review dan Executive Summary.</div>
      @endif
    </div>
  </div>

  {{-- Persetujuan Officer --}}
  @if($officerTurn)
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Form Persetujuan</h6>
      </div>
      <div class="card-body">
        <form action="{{ route('approval.officer.setujui', $review->id) }}" method="POST">
          @csrf
          <div class="mb-3" style="max-width:420px;">
            <label class="form-label fw-bold">Status Persetujuan</label>
            <select name="status" class="form-select" required>
              <option value="Setuju">✅ Setuju (kirim ke Manager)</option>
              <option value="Tidak Setuju">❌ Tidak Setuju (kembalikan)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="3" placeholder="Opsional"></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i>Kirim</button>
            <a href="{{ route('approval.officer.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('generatePdfForm');
  form?.addEventListener('submit', function(){
    const reviewBox = document.getElementById('review_textarea');
    const reviewHolder = document.getElementById('review-hidden');
    reviewHolder.innerHTML = '';
    if (reviewBox && reviewBox.value) {
      reviewBox.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean).forEach(v => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'review_items[]'; i.value = v; reviewHolder.appendChild(i);
      });
    }
    const scopeBox = document.getElementById('scope_textarea');
    const scopeHolder = document.getElementById('scope-hidden');
    scopeHolder.innerHTML = '';
    if (scopeBox && scopeBox.value) {
      scopeBox.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean).forEach(v => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'exec_summary[ruang_lingkup][]'; i.value = v; scopeHolder.appendChild(i);
      });
    }
  });
});
</script>
@endsection