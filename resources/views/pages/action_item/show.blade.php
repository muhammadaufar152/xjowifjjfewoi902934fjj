@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10">
      <h3 class="fw-bold text-black border-bottom pb-2">Detail Action Item</h3>

      {{-- === DETAIL HEADER === --}}
      <div class="card mb-4">
        <div class="card-body">
          <table class="table table-borderless">
            <tr><th width="200">Nama Dokumen</th><td>{{ $item->nama_dokumen }}</td></tr>
            <tr><th>No Dokumen</th><td>{{ $item->no_dokumen }}</td></tr>
            <tr><th>Action Item</th><td>{{ $item->action_item }}</td></tr>
            <tr><th>BPO / UIC</th><td>{{ $item->bpo_uic }}</td></tr>
            <tr><th>Target</th><td>{{ optional($item->target)->format('d-m-Y') ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $item->status }}</td></tr>
            <tr><th>No FR</th><td>{{ $item->no_fr ?? '-' }}</td></tr>
            <tr><th>Keterangan</th><td>{{ $item->keterangan ?? '-' }}</td></tr>
            <tr>
              <th>Lampiran</th>
              <td>
                @if($item->lampirans && $item->lampirans->count())
                  <ul class="mb-0">
                    @foreach ($item->lampirans as $lampiran)
                      <li>
                        <a href="{{ Storage::url($lampiran->path) }}" target="_blank">
                          Lihat / Download ({{ $loop->iteration }})
                        </a>
                      </li>
                    @endforeach
                  </ul>
                @else
                  -
                @endif
              </td>
            </tr>
          </table>
        </div>
      </div>

      {{-- === AKSI BPO === --}}
      @hasrole('bpo')
        @if(in_array($item->status, [\App\Models\ActionItem::ST_OPEN, \App\Models\ActionItem::ST_PROGRESS]))
          <div class="card mb-3">
            <div class="card-header fw-bold">Mulai / Perbarui Progress</div>
            <div class="card-body">
              <form method="POST" action="{{ route('ai.startProgress', $item) }}" class="row g-2">
                @csrf
                @method('PATCH')
                <div class="col-md-3">
                  <label class="form-label">Target</label>
                  <input type="hidden" name="target" value="{{ optional($item->target)->format('Y-m-d') }}">
                  <input type="date" class="form-control" value="{{ optional($item->target)->format('Y-m-d') }}" disabled>
                </div>
              </form>
            </div>
          </div>
        @endif

        @if($item->status === \App\Models\ActionItem::ST_PROGRESS && $item->status !== \App\Models\ActionItem::ST_CANCELLED)
          <div class="card mb-3">
            <div class="card-header fw-bold">Upload Lampiran & Ajukan Approval</div>
            <div class="card-body">
              @if($item->uploadIsBlocked())
                <div class="alert alert-warning">
                  Submission sedang pending untuk waktu yang tidak ditentukan
                </div>
              @else
                <form method="POST" action="{{ route('ai.lampiran', $item) }}" enctype="multipart/form-data" class="row g-2">
                  @csrf
                  <div class="col-md-6">
                    <input type="file" name="lampiran[]" multiple class="form-control" accept="application/pdf" required>
                  </div>
                  <div class="col-md-3">
                    <button class="btn btn-success">Upload & Request Approval</button>
                  </div>
                </form>
              @endif
            </div>
          </div>
        @endif
      @endif

      {{-- === TOMBOL PENDING / RESUME + CANCEL (Officer/Manager/AVP) === --}}
      @hasanyrole('officer|manager|avp')
        <div class="d-flex gap-2 mb-3">
          @if(!$item->is_upload_locked)
            <form method="POST" action="{{ route('ai.hold', $item) }}" class="d-inline">
              @csrf @method('PATCH')
              <button class="btn btn-warning">Pending</button>
            </form>
          @else
            <form method="POST" action="{{ route('ai.resume', $item) }}" class="d-inline">
              @csrf @method('PATCH')
              <button class="btn btn-primary">Resume</button>
            </form>
            <span class="align-self-center text-muted">
              {{ $item->upload_locked_info }}
            </span>
          @endif

          @if(in_array($item->status, [\App\Models\ActionItem::ST_PROGRESS, \App\Models\ActionItem::ST_REQ_CLOSE], true)
              && $item->status !== \App\Models\ActionItem::ST_CANCELLED)
            <form method="POST" action="{{ route('ai.cancel', $item) }}" class="d-inline">
              @csrf @method('PATCH')
              <button class="btn btn-danger">Cancel</button>
            </form>
          @endif
        </div>
      @endhasanyrole

      {{-- === APPROVAL ACTION ITEM === --}}
      @php
        $steps = $item->reviewSteps;
        $nextPending = $steps?->firstWhere('status', \App\Models\ReviewStep::STATUS_PENDING);
        $nextRole    = $nextPending ? strtolower($nextPending->tahapan) : null; 
      @endphp

      <div class="card">
        <div class="card-header fw-bold">Approval Action Item</div>
        <div class="card-body">
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Tahapan</th>
                <th>Keterangan</th>
                <th>Verifikator</th>
                <th>Tanggal</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($steps as $i => $step)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $step->tahapan }}</td>
                  <td class="text-start">{{ $step->keterangan ?? '-' }}</td>
                  <td>{{ $step->verifikator ?? '-' }}</td>
                  <td>{{ optional($step->tanggal)->format('d-m-Y') ?? '-' }}</td>
                  <td class="text-nowrap">
                    @if($item->status === \App\Models\ActionItem::ST_REQ_CLOSE && $nextPending && $step->id === $nextPending->id)
                      @hasrole($nextRole)
                        <form action="{{ route('review_step.approve', $step) }}" method="POST" class="d-inline">
                          @csrf
                          <button class="btn btn-success btn-sm" onclick="return confirm('Setujui tahapan ini?')">
                            Approve
                          </button>
                        </form>

                        <form action="{{ route('review_step.reject', $step) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="text" name="keterangan" placeholder="Alasan"
                                 class="form-control form-control-sm d-inline-block mb-1" style="width:180px" required>
                          <button class="btn btn-danger btn-sm" onclick="return confirm('Tolak tahapan ini?')">
                            Reject
                          </button>
                        </form>
                      @else
                        <span class="text-muted">Menunggu {{ $nextPending->tahapan }}</span>
                      @endif
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-muted">Belum ada tahapan review</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-3">
        <a href="{{ route('ai.index') }}" class="btn btn-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>
@endsection
