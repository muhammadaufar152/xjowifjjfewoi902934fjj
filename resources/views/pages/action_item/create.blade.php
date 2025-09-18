@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10">
      <h3 class="fw-bold text-center text-black border-bottom pb-2">Create Action Item</h3>
    </div>
  </div>

  <div class="row justify-content-center mt-3">
    <div class="col-10 bg-white rounded p-4">

      @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('ai.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label for="nama_dokumen" class="form-label">Nama Dokumen</label>
          <input type="text" class="form-control" id="nama_dokumen" name="nama_dokumen"
                 value="{{ old('nama_dokumen') }}" required>
        </div>

        <div class="mb-3">
          <label for="no_dokumen" class="form-label">No Dokumen</label>
          <input type="text" class="form-control" id="no_dokumen" name="no_dokumen"
                 value="{{ old('no_dokumen') }}" required>
        </div>

        <div class="mb-3">
          <label for="action_item" class="form-label">Action Item</label>
          <textarea class="form-control" id="action_item" name="action_item" rows="3" required>{{ old('action_item') }}</textarea>
        </div>

        {{-- BPO / UIC (boleh lebih dari satu) --}}
        <div class="mb-3">
          <label class="form-label">BPO / UIC <small class="text-muted">(bisa pilih lebih dari satu)</small></label>

          {{-- hidden yang akan dikirim ke controller sebagai string koma --}}
          <input type="hidden" id="bpo_uic" name="bpo_uic" value="{{ old('bpo_uic') }}">

          @php
            $bpos = [
              'FINANCE','HUMAN CAPITAL MANAGEMENT','SERVICE DELIVERY','NETWORK OPERATION','CORPORATE SECRETARY',
              'ASSET, PROCUREMENT & LOGISTICS','AUDIT & RISK MANAGEMENT','TRANSFORMATION','BILLING & COLLECTION',
              'PRODUCT DEVELOPMENT & PROJECT MANAGEMENT','STRATEGIC BUSINESS DEVELOPMENT','SYSTEM PLANNING & MANAGEMENT',
              'COMMERCE SEGMENT 1','COMMERCE SEGMENT 2','COMMERCE SEGMENT 3','SATELIT OPERATION',
              'DATACOM, ADJACENT SERVICE & IT','DIREKTORAT OPERASI','INTERNAL AUDIT & RISK MANAGEMENT',
              'GOVERNMENT & REGIONAL SERVICE','MINING, MARITIME & AVIATION SERVICE','OTHER LISENCE OPERATOR SERVICE',
              'DIREKTORAT KOMERSIAL','ENTERPRISE SERVICE','REGIONAL & CONSUMER SERVICE','GOVERNMENT SERVICE',
            ];
            $oldBpo = collect(explode(',', old('bpo_uic')))->map(fn($s)=>trim($s))->filter()->all();
          @endphp

          <select id="bpo_uic_select" class="form-control" multiple size="7">
            @foreach($bpos as $opt)
              <option value="{{ $opt }}" {{ in_array($opt, $oldBpo) ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
          </select>
          <div class="form-text">Tahan Ctrl (Windows) / Cmd (Mac) untuk memilih lebih dari satu.</div>
        </div>

        <div class="mb-3">
          <label for="target" class="form-label">Target</label>
          <input type="date" name="target" id="target" class="form-control" value="{{ old('target') }}">
        </div>

        <div class="mb-3">
          <label for="no_fr" class="form-label">No FR</label>
          <input type="text" class="form-control" id="no_fr" name="no_fr" value="{{ old('no_fr') }}">
        </div>

        <div class="mb-3">
          <label for="keterangan" class="form-label">Keterangan</label>
          <textarea class="form-control" id="keterangan" name="keterangan" rows="2">{{ old('keterangan') }}</textarea>
        </div>

        <div class="mb-3">
          <label for="lampiran" class="form-label">Lampiran (PDF wajib)</label>
          <input type="file" class="form-control" id="lampiran" name="lampiran" accept="application/pdf" required>
          <div class="form-text">Maks 20 MB. Hanya PDF.</div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ route('ai.index') }}" class="btn btn-secondary">Back</a>
          <button type="submit" class="btn btn-success">Simpan</button>
        </div>
      </form>

      {{-- sync pilihan multiple ke hidden bpo_uic --}}
      <script>
        (function () {
          const sel = document.getElementById('bpo_uic_select');
          const hidden = document.getElementById('bpo_uic');
          function sync() {
            const vals = Array.from(sel.selectedOptions).map(o => o.value.trim());
            hidden.value = vals.join(', ');
          }
          sel.addEventListener('change', sync);
          // inisialisasi saat load
          sync();
        })();
      </script>

    </div>
  </div>
</div>
@endsection
