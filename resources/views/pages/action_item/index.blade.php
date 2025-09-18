@extends('layouts.app-document')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10 text-center">
      <h3 class="fw-bold text-black border-bottom pb-2">Action Item</h3>
    </div>
  </div>

  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-3">
      <div class="table-responsive">
        {{-- table-bordered supaya ada outline/grid --}}
        <table id="reviewTable" class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Nama Dokumen</th>
              <th>No Dokumen</th>
              <th>Action Item</th>
              <th>BPO / UIC</th>
              <th>Target</th>
              <th>Status</th>
              <th>Approval</th>
              <th>Keterangan</th>
              <th>No FR</th>
              <th>Action</th>
            </tr>
            <tr>
              @for ($i = 0; $i < 9; $i++)
                <th><input type="text" class="form-control form-control-sm" placeholder="Cari..."></th>
              @endfor
              <th></th>
            </tr>
          </thead>

          <tbody>
            @forelse($items as $it)
              @php
                $steps = $it->reviewSteps;
                $next  = $steps?->firstWhere('status','!=','Approved');

                $approvalText = $it->status === \App\Models\ActionItem::ST_REQ_CLOSE
                                ? ($next ? 'Menunggu '.$next->tahapan : 'Selesai')
                                : '-';
                $displayStatus = ($it->is_upload_locked ?? false)
                                  ? \App\Models\ActionItem::ST_PENDING
                                  : $it->status;
              @endphp

              <tr>
                <td>{{ $it->nama_dokumen }}</td>
                <td>{{ $it->no_dokumen }}</td>
                <td class="text-start">{{ $it->action_item }}</td>
                <td>{{ $it->bpo_uic }}</td>
                <td>{{ $it->target ? strtolower(\Carbon\Carbon::parse($it->target)->format('Y-M-d')) : '-' }}</td>

                <td>
                  <span class="badge
                    @switch($displayStatus)
                      @case('Open') bg-secondary @break
                      @case('Progress') bg-warning text-dark @break
                      @case('Request Close') bg-info text-dark @break
                      @case('Closed') bg-success @break
                      @case('Pending') bg-secondary text-white @break
                      @case('Cancelled') bg-danger @break
                      @default bg-light text-dark
                    @endswitch">
                    {{ $displayStatus }}
                  </span>
                </td>

                <td>{{ $approvalText }}</td>
                <td class="text-start">{{ $it->keterangan }}</td>
                <td>{{ !empty($it->no_fr) ? $it->no_fr : '-' }}</td>

                <td>
                  <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                      Action
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                      @if(!empty($it->lampiran))
                        <li>
                          <a class="dropdown-item" href="{{ Storage::url($it->lampiran) }}" target="_blank">
                            Lihat Lampiran
                          </a>
                        </li>
                      @endif

                      @if(isset($it->lampirans) && $it->lampirans->count())
                        <li><h6 class="dropdown-header">Lampiran</h6></li>
                        @foreach($it->lampirans as $idx => $lp)
                          <li>
                            <a class="dropdown-item" href="{{ Storage::url($lp->path) }}" target="_blank">
                              Lampiran ({{ $loop->iteration }})
                            </a>
                          </li>
                        @endforeach
                      @endif

                      @hasrole('bpo')
                        @if(in_array($it->status, [\App\Models\ActionItem::ST_OPEN, \App\Models\ActionItem::ST_PROGRESS], true))
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <form action="{{ route('ai.startProgress', $it->id) }}" method="POST" class="m-0">
                              @csrf
                              @method('PATCH')
                              <button type="submit" class="dropdown-item">Follow up</button>
                            </form>
                          </li>
                        @endif
                      @endhasrole

                      <li>
                        <a class="dropdown-item" href="{{ route('ai.show', $it) }}">Detail</a>
                      </li>

                      @hasanyrole('officer|manager|avp')
                        <li><hr class="dropdown-divider"></li>

                        @if(!($it->is_upload_locked ?? false))
                          <li>
                            <form action="{{ route('ai.hold', $it) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('Jadikan Pending dan kunci upload?')">
                              @csrf @method('PATCH')
                              <button type="submit" class="dropdown-item">Set Pending (Lock Upload)</button>
                            </form>
                          </li>
                        @else
                          <li>
                            <form action="{{ route('ai.resume', $it) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('Lepas Pending dan buka upload?')">
                              @csrf @method('PATCH')
                              <button type="submit" class="dropdown-item">Resume (Unlock Upload)</button>
                            </form>
                          </li>
                        @endif

                        @if(in_array($it->status, [\App\Models\ActionItem::ST_PROGRESS, \App\Models\ActionItem::ST_REQ_CLOSE], true)
                            && $it->status !== \App\Models\ActionItem::ST_CANCELLED)
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <form action="{{ route('ai.cancel', $it) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('Yakin cancel action item ini?')">
                              @csrf
                              @method('PATCH')
                              <button type="submit" class="dropdown-item text-danger">Cancel</button>
                            </form>
                          </li>
                        @endif
                      @endhasanyrole
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-center">Belum ada data</td></tr>
            @endforelse
          </tbody>
        </table>

        <style>
          div.dataTables_filter{display:none}
          #reviewTable th, #reviewTable td{ white-space: nowrap; vertical-align: middle; }
          #reviewTable thead input{ min-width:120px; }
          .dropdown-menu form{ margin:0; } 
          .dropdown-menu .dropdown-item{ padding:.25rem .75rem; }
        </style>
      </div>

      <div class="mt-3">
        @hasrole('officer')
          <a href="{{ route('ai.create') }}" class="btn text-white" style="background-color:#a56b4c;">Add File</a>
        @endhasrole
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    const table = $('#reviewTable').DataTable({
      orderCellsTop: true,
      fixedHeader: true,
      searching: true,
      order: [] // urutan sesuai dari server (latest first)
    });

    $('#reviewTable thead tr:eq(1) th').each(function (i) {
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
</script>
@endsection
