@extends('layouts.app-document')

@push('styles')
  {{-- Bootstrap Icons --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

@section('content')
@php
  $rows = collect($reviews ?? $documents ?? []);
@endphp

<div class="container-fluid">
  {{-- HEADER --}}
  <div class="row justify-content-center mt-4">
    <div class="col-10 text-center">
      <h3 class="fw-bold text-black border-bottom pb-2">Approval - AVP</h3>
      @if(session('success')) <div class="alert alert-success mt-3">{{ session('success') }}</div> @endif
      @if(session('danger'))  <div class="alert alert-danger mt-3">{{ session('danger') }}</div>  @endif
      @if(session('warning')) <div class="alert alert-warning mt-3">{{ session('warning') }}</div> @endif
      @if(session('info'))    <div class="alert alert-info mt-3">{{ session('info') }}</div>     @endif
    </div>
  </div>

  {{-- TABLE WRAPPER --}}
  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-3">
      <div class="table-responsive">
        <table id="reviewTableAvp" class="table table-bordered text-center align-middle w-100">
          <thead class="table-light">
            <tr>
              <th>No Dokumen</th>
              <th>Level Dokumen</th>
              <th>Klasifikasi Siklus</th>
              <th>Jenis Dokumen</th>
              <th>BPO</th>
              <th>Perihal</th>
              <th>Tanggal Masuk</th>
              <th>Status</th>
              <th>Riwayat</th>
              <th>Action</th>
            </tr>
            {{-- FILTER PER KOLOM --}}
            <tr>
              <th><input class="form-control form-control-sm column-search" data-column="0" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="1" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="2" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="3" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="4" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="5" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="6" placeholder="YYYY-MM-DD"></th>
              <th><input class="form-control form-control-sm column-search" data-column="7" placeholder="Cari..."></th>
              <th></th><th></th>
            </tr>
          </thead>

          <tbody>
          @forelse($rows as $r)
            @php
              // Normalisasi status
              $statusRaw = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $r->status ?? ''))));
              if ($statusRaw === 'open') $statusRaw = 'pending';

              // Mapping utama
              $cls = 'secondary'; $ico = 'bi-hourglass-split'; $lbl = 'Open'; $sub = null;
              if (in_array($statusRaw, ['verifikasi 1','verifikasi 2','verifikasi 3'], true)) {
                $cls = 'primary'; $ico = 'bi-activity'; $lbl = 'Progress';
                $sub = 'Verifikasi ' . explode(' ', $statusRaw)[1];
              } elseif ($statusRaw === 'sirkulir') {
                $cls = 'info'; $ico = 'bi-flag'; $lbl = 'Sirkulir';
              } elseif ($statusRaw === 'selesai') {
                $cls = 'success'; $ico = 'bi-check-circle'; $lbl = 'Done';
              } elseif ($statusRaw === 'return') {
                $cls = 'warning'; $ico = 'bi-arrow-counterclockwise'; $lbl = 'Return';
              }

              $tglMasuk = $r->tanggal_masuk ?: $r->created_at;

              // ===== Riwayat TANPA Pending =====
              $allSteps = ($r->steps ?? collect())->sortBy([['tanggal','asc'],['id','asc']]);
              $timeline = $allSteps->reject(fn($s) => strtolower($s->status ?? '') === 'pending');
            @endphp
            <tr>
              <td>{{ $r->no_dokumen ?? '-' }}</td>
              <td>{{ $r->level_dokumen ?? '-' }}</td>
              <td>{{ $r->klasifikasi_siklus ?? '-' }}</td>
              <td>{{ $r->jenis_dokumen ?? '-' }}</td>
              <td>{{ $r->bpoUser->name ?? '-' }}</td>
              <td>{{ $r->perihal ?? $r->nama_dokumen ?? '-' }}</td>
              <td>{{ optional($tglMasuk)->format('Y-m-d') ?? '-' }}</td>
              <td>
                <span class="badge bg-{{ $cls }}"><i class="bi {{ $ico }} me-1"></i>{{ $lbl }}</span>
                @if($sub)<div class="small text-muted mt-1">{{ $sub }}</div>@endif
              </td>

              {{-- RIWAYAT --}}
              <td>
                @if($timeline->count())
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#riwayatModal{{ $r->id }}">
                    <i class="bi bi-clock-history me-1"></i> Riwayat
                  </button>

                  <div class="modal fade" id="riwayatModal{{ $r->id }}" tabindex="-1" aria-labelledby="riwayatLabel{{ $r->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="riwayatLabel{{ $r->id }}">Riwayat Komunikasi</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
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
                                @foreach($timeline as $step)
                                  @php
                                    $sr = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', ($step->status ?? '')))));
                                    $aliases = ['open'=>'pending','tidak-setuju'=>'tidak setuju','verifikasi1'=>'verifikasi 1','verifikasi2'=>'verifikasi 2','verifikasi3'=>'verifikasi 3'];
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
                                    [$bg,$icon,$label,$extra] = $map[$sr] ?? ['light','bi-question-circle', ($step->status ?? '-'), 'text-dark'];
                                  @endphp
                                  <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-primary px-3 py-2">{{ ucfirst($step->tahapan) }}</span></td>
                                    <td>
                                      <span class="badge bg-{{ $bg }} {{ $extra }}">
                                        <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                                      </span>
                                    </td>
                                    <td>{{ $step->verifikator ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($step->tanggal ?? $step->created_at)->format('d/m/Y') }}</td>
                                    <td class="text-start">{{ $step->keterangan ?? '-' }}</td>
                                  </tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                      </div>
                    </div>
                  </div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              {{-- ACTION (dropdown) --}}
              <td>
                <div class="dropdown">
                  <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical me-1"></i> Action
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="{{ route('approval.avp.show', $r->id) }}">
                        <i class="bi bi-eye me-2"></i> View
                      </a>
                    </li>
                    @if(!empty($r->lampiran))
                      <li>
                        <a class="dropdown-item" target="_blank" href="{{ route('form_review.download', $r->id) }}">
                          <i class="bi bi-paperclip me-2"></i> Lampiran
                        </a>
                      </li>
                    @else
                      <li><span class="dropdown-item text-muted"><i class="bi bi-paperclip me-2"></i> Lampiran (tidak ada)</span></li>
                    @endif
                    @if(!empty($r->pdf_path))
                      <li>
                        <a class="dropdown-item" target="_blank" href="{{ route('form_review.pdf', $r->id) }}">
                          <i class="bi bi-file-earmark-pdf me-2"></i> Hasil Form Review (PDF)
                        </a>
                      </li>
                    @endif
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center text-muted py-4">Belum ada dokumen untuk tahap AVP.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
  $(function () {
    const dt = $('#reviewTableAvp').DataTable({
      dom: 'lrt',
      paging: false,
      info: false,
      ordering: true,
      searching: true
    });
    $('.column-search').on('keyup change', function () {
      const col = $(this).data('column');
      dt.column(col).search(this.value).draw();
    });
  });
</script>
@endpush
@endsection
