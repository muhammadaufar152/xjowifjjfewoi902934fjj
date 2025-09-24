@extends('layouts.app-document')

@section('content')

@php
  $userRole = Auth::user()->getRoleNames()->first();
  $user = Auth::user()->role;
  $canEdit = in_array($user, ['officer', 'manager', 'avp', 'vp', 'admin']);
@endphp


<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10 text-center">
      <h3 class="fw-bold text-black border-bottom pb-2">DOCUMENT</h3>
      @if(!request()->query())
        @if($canEdit)
          <div class="float-end">
            <a href="{{ route('document.create') }}" class="btn text-white" style="background-color: #A252F3; width: 115%;"><i class="bi bi-plus-lg"></i> Add</a>
          </div>
        @endif
      @endif
    </div>
  </div>
  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-3">
      <div class="table-responsive">
        <table id="documentTable" class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>No. Dokumen</th>
              <th>Nama Dokumen</th>
              <th>Tanggal Terbit</th>
              <th>Siklus Bisnis</th>
              <th>Jenis Dokumen</th>
              <th>BPO</th>
              <th>Version</th>
              <th>Status</th>
              @if($canEdit)
                <th>History</th>
              @endif
              <th>Action</th>
              <th style="display:none">Created At</th>
            </tr>
            {{-- <form id="filterForm" action="{{ route('document.search') }}" method="GET" enctype="multipart/form-data"> --}}
              <tr>
                <th><input name="no" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                <th><input name="nama" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                <th><input name="tanggal_terbit" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                <th><input name="siklus_bisnis" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                {{-- <th><input type="text" placeholder="Cari..." class="form-control form-control-sm" /> </th> --}}
                <th><input name="jenis_dokumen" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                <th><input name="bpo" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                <th></th>
                <th><input name="status" type="text" placeholder="Cari..." class="form-control form-control-sm" /></th>
                @if($canEdit)
                  <th></th>
                @endif
                <th></th>
                <th style="display:none"></th>
              </tr>
            {{-- </form> --}}
          </thead>

          <tbody>
            @forelse ($documents as $document)
              @php
                // Tentukan induk keluarga versi (root)
                $root = $document->parent ?: $document;

                // Tanggal dasar: selalu dari induk (YYYYMMDD)
                $baseDate = $document->tanggal_terbit
                  ? \Illuminate\Support\Carbon::parse($document->tanggal_terbit)->format('Ymd')
                  : null;

                $childCount = \App\Models\Document::where('parent_id', $root->id)->count();

                // dd($baseDate);
                // Rantai versi dari paling awal (induk) ke terbaru (urut naik)
                // $ascChain = collect([$root])
                //   ->merge(($root->relatedVersions ?? collect())->sortBy('created_at')->values());

                // Posisi dokumen saat ini (1-based) untuk versi
                // $position = optional($ascChain)->search(fn ($d) => $d->id === $document->id);
                // $position = is_int($position) ? ($position + 1) : 1;
                // $verNo    = str_pad($position, 3, '0', STR_PAD_LEFT);
              @endphp

              <tr>
                <td>{{ $document->nomor_document }}</td>
                <td>{{ $document->nama_document }}</td>
                <td>{{ \Carbon\Carbon::parse($document->tanggal_terbit)->format('Y-M-d') }}</td>
                <td>{{ $document->businessCycle->name }}</td>
                {{-- <td>{{ $document->proses_bisnis }}</td> --}}
                <td>{{ $document->documentType->name }}</td>
                <td>{{ $document->bpo->nama }}</td>
                
                {{-- Version dengan tanggal parent --}}
                <td class="text-center">
                  {{ $baseDate ? ($baseDate . '-' . $document->version) : '-' }}
                </td>

                {{-- status --}}
                <td>{{ $document->status }}</td>
                
                @if($canEdit)
                {{-- History download --}}
                <td>
                  @if ($document->downloads->count())
                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#historyModal{{ $document->id }}">
                      🕓
                    </button>

                    <!-- Modal History -->
                    <div class="modal fade" id="historyModal{{ $document->id }}" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Riwayat Download</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <ul class="list-group list-group-flush">
                              @foreach ($document->downloads as $log)
                                <li class="list-group-item">
                                  {{ ($log->user->username . ' - ' . $log->user->name . ' - ' . strtoupper($log->user->role)) }}<br>
                                  <small class="text-muted">{{ $log->downloaded_at->format('d M Y H:i') }}</small>
                                </li>
                              @endforeach
                            </ul>
                          </div>
                        </div>
                      </div>
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              @endif

                {{-- Actions --}}
                <td>
                  <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                      Action
                    </button>
                    <ul class="dropdown-menu">
                      @if ($canEdit)
                        <li>
                          <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#versionModal{{ $document->id }}">
                            Lihat Semua Versi
                          </a>
                        </li>
                      @endif
                      <li><a class="dropdown-item" href="{{ route('document.show', $document->id) }}">View</a></li>
                      @if ($canEdit && $document->parent_id == null)
                        <li><a class="dropdown-item" href="{{ route('document.edit', $document->id) }}">Edit</a></li>
                      @endif
                      @if ($canEdit && $childCount < 2)
                        <li><a class="dropdown-item" href="{{ route('document.updateVersion', ['id' => $document->id, 'is_edit' => 0]) }}">Add Version</a></li>
                      @endif
                      @if ($document->additional_file)
                        <li><a class="dropdown-item" href="{{ route('document.download', $document->id) }}">Download PDF</a></li>
                      @endif
                      @if ($canEdit)
                        <li>
                          <form action="{{ route('document.destroy', $document->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="dropdown-item text-danger" type="submit">Delete</button>
                          </form>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>

                <td style="display:none">{{ $document->created_at }}</td>
              </tr>

              <!-- Modal Semua Versi (tanggal pakai parent untuk semua item) -->
              <div class="modal fade" id="versionModal{{ $document->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Versi Dokumen: {{ $document->nama_document }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <ul class="list-group">
                        @php
                          // Semua versi, terbaru di atas
                          $allVersions = collect([$root])
                            ->merge(($root->relatedVersions ?? collect()))
                            ->sortByDesc('created_at')
                            ->values();


                          // Tanggal dasar dari induk untuk semua baris
                          $baseDateForModal = $baseDate;

                          // ID versi terbaru (elemen pertama setelah sort desc)
                          $latestId = optional($allVersions->first())->id;
                        @endphp

                        @foreach ($allVersions as $i => $version)
                          @php
                            // Penomoran versi: yang teratas (terbaru) mendapat nomor terbesar
                            $num = str_pad($allVersions->count() - $i, 3, '0', STR_PAD_LEFT);
                            $isLatestRow = $version->id === $latestId;
                          @endphp
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                              <strong>{{ $baseDateForModal }}—{{ $document->version }}</strong><br>
                              <small>{{ $version->nama_document }}</small><br>
                              @if ($isLatestRow)
                                <span class="badge bg-success">Up to date</span>
                              @else
                                <span class="badge bg-secondary">Obsolete</span>
                              @endif
                            </div>
                            <a href="{{ route('document.show', $version->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <tr>
                <td colspan="12">Tidak ada data dokumen.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <style>
          /* Sembunyikan filter global DataTables */
          div.dataTables_filter {
            display: none;
          }

          /* Pastikan semua kolom tidak pecah ke 2 baris */
          #documentTable th,
          #documentTable td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 0.5rem 1rem;
          }
        </style>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
      // Find the "Created At" column by looking for the hidden column
      let createdAtColumnIndex = -1;
      $('#documentTable thead tr:first th').each(function(index) {
          if ($(this).css('display') === 'none') {
              createdAtColumnIndex = index;
              return false; // break the loop
          }
      });
      
      // Fallback: if not found by display:none, assume it's the last column
      if (createdAtColumnIndex === -1) {
          createdAtColumnIndex = $('#documentTable thead tr:first th').length - 1;
      }
      
      const table = $('#documentTable').DataTable({
          order: [[createdAtColumnIndex, 'desc']], 
          orderCellsTop: true,
          fixedHeader: true,
          searching: true,
          columnDefs: [
              {
                  targets: [createdAtColumnIndex],
                  visible: false
              }
          ]
      });

      // Search per kolom di baris header kedua
      $('#documentTable thead tr:eq(1) th').each(function (i) {
          const input = $('input', this);
          if (input.length) {
              input.on('keyup change', function () {
                  if (table.column(i).search() !== this.value) {
                      table.column(i).search(this.value).draw();
                  }
              });
          }
      });
  });

    // document.querySelectorAll('#filterForm input').forEach(input => {
    //     let timer;
    //     console.log('a');
        
    //     input.addEventListener('keyup', function () {
    //         clearTimeout(timer);
    //         timer = setTimeout(() => {
    //             let form = document.getElementById('filterForm');
    //             let params = new URLSearchParams(new FormData(form));

    //             fetch(form.action + '?' + params.toString(), {
    //                 headers: { 'X-Requested-With': 'XMLHttpRequest' }
    //             })
    //             .then(response => response.text())
    //             .then(html => {
    //                 document.querySelector('#documentTable tbody').innerHTML = html;
    //             });
    //         }, 300); // debounce 300ms
    //     });
    // });
  // });
</script>
@endsection