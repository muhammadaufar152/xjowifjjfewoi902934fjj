@extends('layouts.app-document')

@section('content')
{{-- DataTables CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
{{-- Font Awesome (ikon dropdown) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

{{-- jQuery & DataTables --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10 text-center">
      <h3 class="fw-bold text-black border-bottom pb-2">Form Review</h3>
    </div>
  </div>

  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-3">

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('pdf_error'))
        <div class="alert alert-danger">{{ session('pdf_error') }}</div>
      @endif

      <div class="table-responsive">
        <table id="reviewTable" class="table table-bordered text-center align-middle w-100">
          <thead class="table-light">
            <tr>
              <th>No Dokumen</th>
              <th>Level Dokumen</th>
              <th>Klasifikasi Siklus</th>
              <th>Jenis Dokumen</th>
              <th>BPO</th>
              <th>Perihal Review</th>
              <th>Tanggal Approval</th>
              <th>Status</th>
              <th>Riwayat</th>
              <th>Action</th>
            </tr>
            <tr>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="0" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="1" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="2" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="3" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="4" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="5" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="6" placeholder="Cari..."></th>
              <th><input type="text" class="form-control form-control-sm column-search" data-column="7" placeholder="Cari..."></th>
              <th></th>
              <th></th>
            </tr>
          </thead>

          <tbody>
          @forelse ($reviews as $review)
            @php
              $avpStep = ($review->steps ?? collect())
                          ->first(fn($s) => strtolower($s->tahapan ?? '') === 'avp');
              $tglApproval = $avpStep
                  ? \Carbon\Carbon::parse($avpStep->tanggal ?? $avpStep->created_at)->format('d/m/Y')
                  : '-';

              $raw = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $review->status ?? ''))));
              if ($raw === 'open') $raw = 'pending';
              $cls='secondary'; $ico='bi-hourglass-split'; $label='Open'; $sub=null;

              if (in_array($raw, ['verifikasi 1','verifikasi 2','verifikasi 3'])) {
                $cls='primary'; $ico='bi-activity'; $label='Progress'; $sub='Verifikasi '.explode(' ', $raw)[1];
              } elseif ($raw === 'return') {
                $cls='warning'; $ico='bi-arrow-counterclockwise'; $label='Return';
              } elseif ($raw === 'sirkulir') {
                $cls='info'; $ico='bi-flag'; $label='Sirkulir';
              } elseif ($raw === 'selesai') {
                $cls='success'; $ico='bi-check-circle'; $label='Done';
              }

              // Riwayat untuk modal — sinkron dgn halaman approval: SEMUA 'pending' disembunyikan
              $stepsForTimeline = collect($review->steps ?? [])
                  ->sortBy([['tanggal','asc'], ['id','asc']])
                  ->reject(function ($s) {
                      // normalisasi status + alias
                      $status = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $s->status ?? ''))));
                      $aliases = ['open' => 'pending', 'menunggu' => 'pending'];
                      if (isset($aliases[$status])) $status = $aliases[$status];

                      // buang semua pending
                      return $status === 'pending';
                  })
                  ->values();
              $isSirkulir = ($raw === 'sirkulir');
            @endphp

            <tr>
              <td>{{ $review->no_dokumen }}</td>
              <td>{{ $review->level_dokumen ?? '-' }}</td>
              <td>{{ $review->klasifikasi_siklus ?? '-' }}</td>
              <td>{{ $review->jenis_dokumen ?? '-' }}</td>
              <td>{{ $review->bpoUser->name ?? 'User' }}</td>
              <td>{{ $review->perihal ?? '---' }}</td>
              <td>{{ $tglApproval }}</td>

              {{-- STATUS --}}
              <td>
                <span class="badge bg-{{ $cls }}"><i class="bi {{ $ico }} me-1"></i>{{ $label }}</span>
                @if($sub)<div class="small text-muted mt-1">{{ $sub }}</div>@endif
              </td>

              {{-- RIWAYAT --}}
              <td>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#riwayatModal{{ $review->id }}">
                  <i class="bi bi-clock-history me-1"></i> Lihat
                </button>

                <div class="modal fade" id="riwayatModal{{ $review->id }}" tabindex="-1" aria-labelledby="riwayatModalLabel{{ $review->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="riwayatModalLabel{{ $review->id }}">Riwayat Komunikasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body text-start">
                        @if ($stepsForTimeline->count())
                          <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover align-middle text-center">
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
                                @foreach ($stepsForTimeline as $step)
                                  @php
                                    $sr = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', ($step->status ?? '')))));
                                    $aliases = ['open'=>'pending','tidak-setuju'=>'tidak setuju','verifikasi1'=>'verifikasi 1','verifikasi2'=>'verifikasi 2','verifikasi3'=>'verifikasi 3'];
                                    if (isset($aliases[$sr])) $sr = $aliases[$sr];
                                    $map = [
                                      'setuju'        => ['success','bi-check2-circle','Setuju',''],
                                      'tidak setuju'  => ['warning','bi-x-circle','Tidak Setuju','text-dark'],
                                      'pending'       => ['secondary','bi-hourglass-split','Pending',''],
                                      'return'        => ['warning','bi-arrow-counterclockwise','Return',''],
                                      'resubmit'      => ['info','bi-arrow-repeat','Resubmit',''],
                                      'verifikasi 1'  => ['success','bi-1-circle','Verifikasi 1',''],
                                      'verifikasi 2'  => ['info','bi-2-circle','Verifikasi 2',''],
                                      'verifikasi 3'  => ['primary','bi-3-circle','Verifikasi 3',''],
                                      'selesai'       => ['success','bi-check-circle','Selesai',''],
                                      'sirkulir'      => ['info','bi-flag','Sirkulir',''],
                                    ];
                                    [$bg,$ic,$lbl,$extra] = $map[$sr] ?? ['light','bi-question-circle',($step->status ?? '-'),'text-dark'];
                                  @endphp
                                  <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-primary px-3 py-2">{{ ucfirst($step->tahapan) }}</span></td>
                                    <td><span class="badge bg-{{ $bg }} {{ $extra }}"><i class="bi {{ $ic }} me-1"></i>{{ $lbl }}</span></td>
                                    <td>{{ $step->verifikator }}</td>
                                    <td>{{ \Carbon\Carbon::parse($step->tanggal ?? $step->created_at)->format('d/m/Y') }}</td>
                                    <td class="text-muted text-start">{{ $step->keterangan ?? '-' }}</td>
                                  </tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>
                        @else
                          <p class="text-muted">Tidak ada riwayat komunikasi.</p>
                        @endif
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>
              </td>

              {{-- ACTION DROPDOWN --}}
              <td style="position: relative;">
                <div class="dropdown">
                  <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical me-1"></i> Action
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" style="min-width: 13rem;">
                    <li>
                      <a class="dropdown-item" href="{{ route('form_review.show', $review->id) }}">
                        <i class="fas fa-eye me-2"></i> Detail
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('form_review.edit', $review->id) }}">
                        <i class="fas fa-pen me-2"></i> Edit
                      </a>
                    </li>
                    @if(!empty($review->lampiran))
                      <li>
                        <a class="dropdown-item" href="{{ route('form_review.download', $review->id) }}" target="_blank">
                          <i class="bi bi-paperclip me-2"></i> Lampiran
                        </a>
                      </li>
                    @endif

                    {{-- Add File (BPO) -> muncul hanya saat status = sirkulir --}}
                    @if($isSirkulir)
                    @php
                      // ambil 2 nama file terbaru (kalau ada)
                      $existingNames = ($review->bpoUploads ?? collect())
                          ->sortByDesc('created_at')
                          ->take(2)
                          ->map(fn($f) => $f->original_name ?? basename($f->path))
                          ->values();
                    @endphp
                    <button type="button"
                            class="dropdown-item"
                            data-bpo-upload-id="{{ $review->id }}"
                            data-existing='@json($existingNames)'>
                      <i class="bi bi-upload me-1"></i> Add File
                    </button>
                  @endif


                    <li><hr class="dropdown-divider"></li>

                    {{-- Delete SELALU ada (sesuai permintaan) --}}
                    <li>
                      <form action="{{ route('form_review.destroy', $review->id) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="dropdown-item text-danger" type="submit">
                          <i class="fas fa-trash me-2"></i> Delete
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>

                {{-- MODAL per-baris (biarkan ada; sudah disesuaikan nama inputnya) --}}
                @if($isSirkulir)
                  <div class="modal fade" id="bpoUpload{{ $review->id }}" tabindex="-1" aria-labelledby="bpoUploadLabel{{ $review->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="bpoUploadLabel{{ $review->id }}">
                            <i class="bi bi-upload me-1"></i> Add File (BPO)
                          </h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="{{ route('form_review.bpo_upload', $review->id) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          <div class="modal-body">

                            @if ($errors->any())
                              <div class="alert alert-danger">
                                <ul class="mb-0">
                                  @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                  @endforeach
                                </ul>
                              </div>
                            @endif

                            <div class="mb-2 small text-muted">Pilih file (maksimal 2 file, 20MB per file).</div>

                            <div class="mb-2">
                              <label class="form-label fw-semibold">File 1</label>
                              <input type="file" name="file1" class="form-control"
                                     accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                              <input type="text" name="ket1" class="form-control mt-2" placeholder="keterangan (opsional)">
                            </div>

                            <div class="mb-2">
                              <label class="form-label fw-semibold">File 2 (opsional)</label>
                              <input type="file" name="file2" class="form-control"
                                     accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                              <input type="text" name="ket2" class="form-control mt-2" placeholder="keterangan (opsional)">
                            </div>

                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Unggah</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-muted">Belum ada histori review.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        <a href="{{ route('form_review.create') }}" class="btn text-white" style="background-color: #a56b4c;">+ Form Review</a>
      </div>
    </div>
  </div>
</div>

{{-- Modal Add File (BPO) versi global --}}
<div class="modal fade" id="bpoUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="bpoUploadForm" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bpoUploadTitle">Add File (BPO)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="text-muted small mb-2">Pilih maksimal 2 file. Maks 20MB per file.</div>
          
          {{-- daftar file yang sudah di-upload --}}
          <div id="uploadedFilesBox" class="mb-3 d-none">
            <div class="small text-muted mb-1">File yang sudah di upload:</div>
            <ul id="uploadedFilesList" class="list-unstyled small mb-0"></ul>
            <hr class="my-2">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">File 1</label>
            <input type="file" name="file1" class="form-control" />
            <input type="text" name="ket1" class="form-control mt-2" placeholder="keterangan (opsional)" />
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">File 2 (opsional)</label>
            <input type="file" name="file2" class="form-control" />
            <input type="text" name="ket2" class="form-control mt-2" placeholder="keterangan (opsional)" />
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Unggah</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- JS untuk buka modal & set action form sesuai ID --}}
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-bpo-upload-id]');
  if (!btn) return;

  const id   = btn.getAttribute('data-bpo-upload-id');
  const form = document.getElementById('bpoUploadForm');

  // set action form
  form.action = "{{ url('/review') }}/" + id + "/bpo-upload";

  // set judul
  document.getElementById('bpoUploadTitle').textContent = 'Add File (BPO) – ID #' + id;

  // === isi daftar "file yang sudah di upload" ===
  const box  = document.getElementById('uploadedFilesBox');
  const list = document.getElementById('uploadedFilesList');
  list.innerHTML = '';
  try {
    const arr = JSON.parse(btn.getAttribute('data-existing') || '[]');
    if (arr.length) {
      box.classList.remove('d-none');
      arr.forEach(name => {
        const li = document.createElement('li');
        li.textContent = '• ' + name;
        list.appendChild(li);
      });
    } else {
      box.classList.add('d-none');
    }
  } catch (_) {
    box.classList.add('d-none');
  }

  // buka modal
  const modal = new bootstrap.Modal(document.getElementById('bpoUploadModal'));
  modal.show();
});
</script>


<script>
  $(document).ready(function () {
    const table = $('#reviewTable').DataTable({
      paging   : true,
      info     : true,
      ordering : false,
      searching: false
    });

    $('.column-search').on('keyup change', function () {
      table.column($(this).data('column')).search(this.value).draw();
    });
  });
</script>
@endsection
