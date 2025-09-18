@extends('layouts.app-document')

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endpush

@section('content')
<div class="container-fluid">
  {{-- HEADER --}}
  <div class="row justify-content-center mt-4">
    <div class="col-10 text-center">
      <h3 class="fw-bold text-black border-bottom pb-2">Approval - Officer</h3>
      @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if(session('danger'))  <div class="alert alert-danger">{{ session('danger') }}</div>  @endif
      @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
      @if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div>     @endif
    </div>
  </div>

  {{-- TABLE --}}
  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-3">
      <div class="table-responsive">
        <table id="reviewTableOfficer" class="table table-bordered text-center align-middle w-100">
          <thead class="table-light">
            <tr>
              <th>No Dokumen</th>
              <th>Level Dokumen</th>
              <th>Klasifikasi Siklus</th>
              <th>Jenis Dokumen</th>
              <th>BPO</th>
              <th>Perihal</th>
              <th>Status</th>
              <th>Riwayat</th>
              <th>Action</th>
            </tr>
            <tr>
              <th><input class="form-control form-control-sm column-search" data-column="0" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="1" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="2" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="3" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="4" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="5" placeholder="Cari..."></th>
              <th><input class="form-control form-control-sm column-search" data-column="6" placeholder="Cari..."></th>
              <th></th><th></th>
            </tr>
          </thead>

          <tbody>
          @foreach($reviews as $review)
            @php
              $statusRaw = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $review->status ?? ''))));
              if ($statusRaw === 'open') $statusRaw = 'pending';

              $cls='secondary'; $icon='bi-hourglass-split'; $label='Open'; $sub=null;
              if (in_array($statusRaw, ['verifikasi 1','verifikasi 2','verifikasi 3'])) {
                $cls='primary'; $icon='bi-activity'; $label='Progress'; $sub='Verifikasi '.explode(' ', $statusRaw)[1];
              } elseif ($statusRaw === 'return') {
                $cls='warning'; $icon='bi-arrow-counterclockwise'; $label='Return';
              } elseif ($statusRaw === 'sirkulir') {
                $cls='info'; $icon='bi-flag'; $label='Sirkulir';
              } elseif ($statusRaw === 'selesai') {
                $cls='success'; $icon='bi-check-circle'; $label='Done';
              }

              // ===== Riwayat TANPA Pending =====
              $allSteps = ($review->steps ?? collect())->sortBy([['tanggal','asc'],['id','asc']]);
              $timeline = $allSteps->reject(fn($s) => strtolower($s->status ?? '') === 'pending');
            @endphp
            <tr>
              <td>{{ $review->no_dokumen ?? '-' }}</td>
              <td>{{ $review->level_dokumen ?? '-' }}</td>
              <td>{{ $review->klasifikasi_siklus ?? '-' }}</td>
              <td>{{ $review->jenis_dokumen ?? '-' }}</td>
              <td>{{ $review->bpoUser->name ?? '-' }}</td>
              <td>{{ $review->perihal ?? '-' }}</td>

              {{-- STATUS --}}
              <td>
                <span class="badge bg-{{ $cls }}"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
                @if($sub)<div class="small text-muted mt-1">{{ $sub }}</div>@endif
              </td>

              {{-- RIWAYAT --}}
              <td>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#riwayatModal{{ $review->id }}">
                  <i class="bi bi-clock-history me-1"></i> Riwayat
                </button>

                <div class="modal fade" id="riwayatModal{{ $review->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Riwayat Dokumen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        @if($timeline->count())
                          <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover text-center align-middle">
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
                              @foreach($timeline as $s)
                                @php
                                  $sr = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $s->status ?? ''))));
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
                                  [$bg,$ic,$lbl,$extra] = $map[$sr] ?? ['light','bi-question-circle',($s->status ?? '-'),'text-dark'];
                                @endphp
                                <tr>
                                  <td>{{ $loop->iteration }}</td>
                                  <td><span class="badge bg-primary px-3 py-2">{{ ucfirst($s->tahapan) }}</span></td>
                                  <td><span class="badge bg-{{ $bg }} {{ $extra }}"><i class="bi {{ $ic }} me-1"></i>{{ $lbl }}</span></td>
                                  <td>{{ $s->verifikator ?? '-' }}</td>
                                  <td>{{ \Carbon\Carbon::parse($s->tanggal ?? $s->created_at)->format('d/m/Y') }}</td>
                                  <td class="text-start">{{ $s->keterangan ?? '-' }}</td>
                                </tr>
                              @endforeach
                              </tbody>
                            </table>
                          </div>
                        @else
                          <p class="text-muted mb-0">Tidak ada riwayat.</p>
                        @endif
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>
              </td>

              {{-- ACTION --}}
              <td>
                <div class="dropdown">
                  <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical me-1"></i> Action
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="{{ route('approval.officer.show', $review->id) }}">
                        <i class="bi bi-eye me-2"></i> View
                      </a>
                    </li>
                    @if(!empty($review->lampiran))
                      <li>
                        <a class="dropdown-item" target="_blank" href="{{ route('form_review.download', $review->id) }}">
                          <i class="bi bi-paperclip me-2"></i> Lampiran
                        </a>
                      </li>
                    @else
                      <li><span class="dropdown-item text-muted"><i class="bi bi-paperclip me-2"></i> Lampiran (tidak ada)</span></li>
                    @endif
                    @if(!empty($review->pdf_path))
                      <li>
                        <a class="dropdown-item" target="_blank" href="{{ route('form_review.pdf', $review->id) }}">
                          <i class="bi bi-file-earmark-pdf me-2"></i> Hasil Form Review (PDF)
                        </a>
                      </li>
                    @endif
                  </ul>
                </div>
              </td>
            </tr>
          @endforeach
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
  $(function() {
    const dt = $('#reviewTableOfficer').DataTable({
      dom: 'lrt',
      paging: false,
      info: false,
      ordering: true,
      searching: true
    });
    $('.column-search').on('keyup change', function () {
      dt.column($(this).data('column')).search(this.value).draw();
    });
  });
</script>
@endpush
@endsection
