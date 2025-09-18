@extends('layouts.app-document')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    // === Normalisasi status (sinkron semua halaman) ===
    $statusRaw = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $review->status ?? ''))));
    if ($statusRaw === 'open') $statusRaw = 'pending';

    // === Mapping Open/Progress/Return/Sirkulir/Done + ikon & subteks ===
    $cls = 'secondary'; $ico = 'bi-hourglass-split'; $lbl = 'Open'; $sub = null;
    if (in_array($statusRaw, ['verifikasi 1','verifikasi 2','verifikasi 3'], true)) {
      $cls = 'primary'; $ico = 'bi-activity'; $lbl = 'Progress';
      $sub = 'Verifikasi ' . explode(' ', $statusRaw)[1];
    } elseif ($statusRaw === 'sirkulir') {             // <-- Sirkulir
      $cls = 'success'; $ico = 'bi-flag'; $lbl = 'Sirkulir';
    } elseif ($statusRaw === 'selesai') {
      $cls = 'success'; $ico = 'bi-check-circle'; $lbl = 'Done';
    } elseif ($statusRaw === 'return') {
      $cls = 'warning'; $ico = 'bi-arrow-counterclockwise'; $lbl = 'Return';
    } elseif ($statusRaw === 'pending') {
      $cls = 'secondary'; $ico = 'bi-hourglass-split'; $lbl = 'Open';
    } else {
      $cls = 'light text-dark'; $ico = 'bi-question-circle'; $lbl = $review->status ?? '-';
    }

    $canAct = ($statusRaw === 'verifikasi 2');
    $alreadyFinal = in_array($statusRaw, ['selesai', 'sirkulir']); // <-- final termasuk sirkulir

    $hasPdf = !empty($review->pdf_path);

    $formReviewLatest       = method_exists($review, 'formReviewDocuments') ? $review->formReviewDocuments()->latest()->first() : null;
    $executiveSummaryLatest = method_exists($review, 'executiveSummaries')    ? $review->executiveSummaries()->latest()->first() : null;

    $hasFR = $formReviewLatest && !empty($formReviewLatest->file_path) &&
             (Storage::disk('public')->exists($formReviewLatest->file_path) || Str::startsWith($formReviewLatest->file_path, ['http://','https://','/storage/']));

    $hasES = $executiveSummaryLatest && !empty($executiveSummaryLatest->file_path) &&
             (Storage::disk('public')->exists($executiveSummaryLatest->file_path) || Str::startsWith($executiveSummaryLatest->file_path, ['http://','https://','/storage/']));

    // file sirkulir (kalau sudah ada)
    $sirkulir1 = $review->sirkulir_file_1 ?? null;
    $sirkulir2 = $review->sirkulir_file_2 ?? null;

    // === BPO uploads (untuk tabel "File setelah revisi (BPO)") ===
    $bpoTwo   = method_exists($review, 'bpoUploads') ? $review->bpoUploads()->latest()->take(2)->get() : collect();
    $bpoCount = method_exists($review, 'bpoUploads') ? $review->bpoUploads()->count() : 0;
    $bpoRest  = ($bpoCount > 2 && method_exists($review,'bpoUploads'))
                  ? $review->bpoUploads()->latest()->skip(2)->get()
                  : collect();
@endphp

<div class="container mt-4">
  <h4 class="fw-bold mb-3">Detail Dokumen - AVP</h4>

  {{-- Info --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold">Informasi Dokumen</div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p class="mb-2"><span class="fw-semibold">Nama Dokumen:</span> {{ $review->nama_dokumen ?? '-' }}</p>
          <p class="mb-2"><span class="fw-semibold">No Dokumen:</span> {{ $review->no_dokumen ?? '-' }}</p>
          <p class="mb-2">
            <span class="fw-semibold">Tanggal Masuk:</span>
            {{ optional($review->tanggal_masuk ?? $review->created_at)->format('Y-m-d H:i:s') ?? '-' }}
          </p>
        </div>
        <div class="col-md-6">
          <p class="mb-2"><span class="fw-semibold">Jenis Dokumen:</span> {{ $review->jenis_dokumen ?? '-' }}</p>
          <p class="mb-2"><span class="fw-semibold">Level Dokumen:</span> {{ $review->level_dokumen ?? '-' }}</p>
          <p class="mb-2">
            <span class="fw-semibold">Status:</span>
            <span class="badge bg-{{ $cls }}"><i class="bi {{ $ico }} me-1"></i>{{ $lbl }}</span>
            @if($sub) <small class="text-muted ms-2">{{ $sub }}</small> @endif
          </p>
        </div>
      </div>
    </div>
  </div>

  {{-- Lampiran --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold">File Lampiran</div>
    <div class="card-body">
      @if(!empty($review->lampiran))
        <div class="mb-1 fw-semibold">File Lampiran</div>
        <div class="text-truncate text-muted">{{ basename($review->lampiran) }}</div>
        <a class="btn btn-primary btn-sm mt-2" target="_blank" href="{{ route('form_review.download', $review->id) }}">
          <i class="bi bi-eye me-1"></i> Lihat File
        </a>
      @else
        <span class="text-muted">Tidak ada lampiran.</span>
      @endif
    </div>
  </div>

  {{-- ====== File setelah revisi (BPO) ====== --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
      <span><i class="bi bi-folder2-open me-2"></i>File setelah revisi (BPO)</span>
      @if($bpoCount > 2)
        <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#bpoAllAvp{{ $review->id }}">
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
                  <td class="text-truncate">{{ $f->original_name ?? basename($f->path) }}</td>
                  <td class="text-muted">{{ $f->keterangan ?: '-' }}</td>
                  <td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" target="_blank"
                       href="{{ route('form_review.bpo_file', $f->id) }}">Lihat</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if($bpoCount > 2)
          <div class="collapse mt-3" id="bpoAllAvp{{ $review->id }}">
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
                      <td class="text-truncate">{{ $f->original_name ?? basename($f->path) }}</td>
                      <td class="text-muted">{{ $f->keterangan ?: '-' }}</td>
                      <td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                      <td>
                        <a class="btn btn-sm btn-outline-primary" target="_blank"
                           href="{{ route('form_review.bpo_file', $f->id) }}">Lihat</a>
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

  {{-- ===== Upload Sirkulir (SELALU tampil kalau status = sirkulir ATAU sudah ada file sirkulir) ===== --}}
  @php
    $hasSirkulirFiles = ($review->sirkulirFiles && $review->sirkulirFiles->count()) || $sirkulir1 || $sirkulir2;
  @endphp
  @if ($statusRaw === 'sirkulir' || $hasSirkulirFiles)
    <div class="card mb-3">
      <div class="card-header fw-semibold d-flex align-items-center">
        <span><i class="bi bi-upload me-2"></i>Upload File Sirkulir ke BPO</span>
      </div>
      <div class="card-body">
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle me-2"></i>
          <div>Unggah maksimal 2 file. File ini akan tampil sebagai lampiran di halaman BPO.</div>
        </div>

        {{-- === DAFTAR SEMUA FILE SIRKULIR (TERBARU DI ATAS) === --}}
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
          {{-- fallback lama jika data relasi belum ada --}}
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
        @if ($statusRaw === 'sirkulir')
          <form action="{{ route('approval.avp.sirkulir.upload', $review->id) }}" method="POST" enctype="multipart/form-data">
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
        @endif
      </div>
    </div>
  @endif

  {{-- Form Hasil Review & ES --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
      <span>Form Hasil Review & Executive Summary</span>
      @if($hasPdf)
        <a href="{{ route('form_review.pdf', $review->id) }}" target="_blank" class="btn btn-primary btn-sm">
          <i class="bi bi-eye me-1"></i> Lihat PDF Hasil Form Review
        </a>
      @endif
    </div>
    <div class="card-body">
      @if($hasFR || $hasES)
        <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
          @if($hasFR)
            <a href="{{ Str::startsWith($formReviewLatest->file_path, ['http://','https://','/storage/']) ? $formReviewLatest->file_path : Storage::url($formReviewLatest->file_path) }}"
               target="_blank" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-eye me-1"></i> Lihat Form Review (Manager)
            </a>
          @endif
          @if($hasES)
            <a href="{{ Str::startsWith($executiveSummaryLatest->file_path, ['http://','https://','/storage/']) ? $executiveSummaryLatest->file_path : Storage::url($executiveSummaryLatest->file_path) }}"
               target="_blank" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-eye me-1"></i> Lihat Executive Summary (Manager)
            </a>
          @endif
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
      @endif

      <form id="generatePdfForm" action="{{ route('form_review.generate_pdf', $review->id) }}" method="POST">
        @csrf
        <input type="hidden" name="action" value="generate">

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
                   value="{{ old('pdf_meta.tanggal_permohonan', optional($review->tanggal_masuk)->format('Y-m-d')) }}">
          </div>
        </div>

        <hr>

        <h6 class="mb-2">Hasil Review</h6>
        <textarea id="review_textarea" class="form-control" rows="4" placeholder="Tulis poin hasil review di sini"></textarea>
        <div id="review-hidden"></div>

        <div class="mt-3">
          <label class="form-label fw-bold">Rekomendasi</label>
          <textarea name="rekomendasi" class="form-control" rows="3"></textarea>
        </div>

        <hr>

        <h6 class="mb-2">B. Executive Summary</h6>
        <div class="mb-2">
          <label class="form-label fw-bold">Judul</label>
          <input type="text" name="exec_summary[judul]" class="form-control">
        </div>
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
          <textarea id="scope_textarea" class="form-control" rows="3" placeholder="Satu item per baris"></textarea>
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
          <strong>Disclaimer Clause:</strong> Executive Summary ini hanyalah ringkasan, bukan rekomendasi keputusan.
        </div>

        <hr>

        {{-- Signers & lokasi (opsional) --}}
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

        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-success">
            <i class="fas fa-file-pdf me-1"></i> Save & Generate PDF
        </div>
      </form>
    </div>
  </div>

  {{-- Keputusan AVP --}}
  @if($canAct && !$alreadyFinal)
    <div class="card mb-4">
      <div class="card-header fw-semibold">Keputusan</div>
      <div class="card-body">
        <form method="POST">
          @csrf
          <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="submit" formaction="{{ route('approval.avp.approve', $review->id) }}" class="btn btn-success">
              <i class="bi bi-check-circle me-1"></i> Setujui
            </button>
            <button type="submit" formaction="{{ route('approval.avp.tolak', $review->id) }}" class="btn btn-danger">
              <i class="bi bi-x-circle me-1"></i> Tidak Setuju / Kembalikan
            </button>
            <a href="{{ route('approval.avp.index') }}" class="btn btn-secondary ms-auto">Kembali</a>
          </div>

          <div class="mb-0">
            <label class="form-label fw-bold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
          </div>
        </form>
      </div>
    </div>
  @else
    {{-- Tombol kembali biar konsisten --}}
    <div class="mb-4">
      <a href="{{ route('approval.avp.index') }}" class="btn btn-secondary">Kembali</a>
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
        i.type='hidden'; i.name='review_items[]'; i.value=v; reviewHolder.appendChild(i);
      });
    }
    const scopeBox = document.getElementById('scope_textarea');
    const scopeHolder = document.getElementById('scope-hidden');
    scopeHolder.innerHTML = '';
    if (scopeBox && scopeBox.value) {
      scopeBox.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean).forEach(v => {
        const i = document.createElement('input');
        i.type='hidden'; i.name='exec_summary[ruang_lingkup][]'; i.value=v; scopeHolder.appendChild(i);
      });
    }
  });
});
</script>
@endsection
