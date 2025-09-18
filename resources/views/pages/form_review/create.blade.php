@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-10 bg-white rounded p-4">
            <h4 class="fw-bold text-center mb-4">TAMBAH FORM REVIEW</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Terjadi kesalahan!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('form_review.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Hidden bpo_id -->
                <input type="hidden" name="bpo_id" value="{{ auth()->user()->id }}">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pemohon / BPO</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Masuk</label>
                        <input type="date"
                               class="form-control @error('tanggal_masuk') is-invalid @enderror"
                               name="tanggal_masuk"
                               value="{{ old('tanggal_masuk', date('Y-m-d')) }}">
                        @error('tanggal_masuk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Permohonan</label>
                    <select
                        id="jenis_permohonan"
                        class="form-select @error('jenis_permohonan') is-invalid @enderror"
                        name="jenis_permohonan">
                        <option value="">Pilih Jenis</option>
                        <option value="Baru"   {{ old('jenis_permohonan') == 'Baru'   ? 'selected' : '' }}>Review Dokumen Baru</option>
                        <option value="Revisi" {{ old('jenis_permohonan') == 'Revisi' ? 'selected' : '' }}>Review Dokumen Eksisting</option>
                    </select>
                    @error('jenis_permohonan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Latar Belakang</label>
                    <textarea class="form-control @error('latar_belakang') is-invalid @enderror" name="latar_belakang" rows="3">{{ old('latar_belakang') }}</textarea>
                    @error('latar_belakang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Usulan Revisi</label>
                    <textarea class="form-control @error('usulan_revisi') is-invalid @enderror" name="usulan_revisi" rows="3">{{ old('usulan_revisi') }}</textarea>
                    @error('usulan_revisi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- === POSISI DITUKAR: Klasifikasi Siklus lebih dulu, lalu Jenis Dokumen === --}}
                <div class="mb-3">
                    <label class="form-label">Klasifikasi Siklus</label>
                    <select class="form-select" name="klasifikasi_siklus">
                        <option value="">Pilih Siklus Bisnis</option>
                        @foreach([
                            'Revenue', 'Cost', 'Tax', 'Procurement & Asset Management',
                            'Financial Reporting', 'Treasury', 'Planning & System Management',
                            'General Affair', 'IT Management'
                        ] as $siklus)
                            <option value="{{ $siklus }}" {{ old('klasifikasi_siklus') == $siklus ? 'selected' : '' }}>{{ $siklus }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Dokumen</label>
                    <select class="form-select" name="jenis_dokumen">
                        <option value="">Pilih Jenis</option>
                        @foreach(['Bispro', 'Prosedur', 'Form', 'IK'] as $jenis)
                            <option value="{{ $jenis }}" {{ old('jenis_dokumen') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- === POSISI DITUKAR: No Dokumen lebih dulu daripada Nama Dokumen === --}}
                <div class="mb-3">
                    <label class="form-label">No Dokumen</label>
                    <input type="text" id="no_dokumen" class="form-control" name="no_dokumen" value="{{ old('no_dokumen') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Dokumen</label>
                    <input type="text" id="nama_dokumen" class="form-control" name="nama_dokumen" value="{{ old('nama_dokumen') }}">
                </div>

                {{-- Level Dokumen di-hide (tetap ada & terkirim) --}}
                <div class="mb-3 d-none">
                    <label class="form-label">Level Dokumen</label>
                    <input type="text" id="level_dokumen" class="form-control" name="level_dokumen" value="{{ old('level_dokumen') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Perihal Review</label>
                    <input type="text" class="form-control @error('perihal') is-invalid @enderror" name="perihal" value="{{ old('perihal') }}">
                    @error('perihal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Lampiran (draft/final)</label>
                    <input type="file" class="form-control @error('lampiran') is-invalid @enderror" name="lampiran">
                    @error('lampiran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('form_review.index') }}" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn" style="background-color: #A0522D; color: white;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Freeze fields saat "Review Dokumen Baru" --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const jenis   = document.getElementById('jenis_permohonan'); // "Baru" / "Revisi"
  const nama    = document.getElementById('nama_dokumen');
  const nomor   = document.getElementById('no_dokumen');
  const level   = document.getElementById('level_dokumen');

  if (!jenis || !nama || !nomor || !level) return;

  // Freeze SAAT pilihan "Review Dokumen Baru"
  const mustFreeze = () => {
    const v = (jenis.value || '').toLowerCase().trim();                       // ex: "baru"
    const t = (jenis.options[jenis.selectedIndex]?.text || '').toLowerCase(); // ex: "review dokumen baru"
    return v === 'baru' || t.includes('baru');
  };

  // Pakai readOnly supaya value tetap terkirim ke server
  const setFreeze = (on) => {
    [nama, nomor, level].forEach(el => {
      el.readOnly = on;
      el.classList.toggle('bg-light', on);
      el.classList.toggle('text-muted', on);
      if (on) el.setAttribute('tabindex','-1'); else el.removeAttribute('tabindex');
    });
  };

  const apply = () => setFreeze(mustFreeze());

  jenis.addEventListener('change', apply);
  jenis.addEventListener('input',  apply);
  apply(); // kondisi awal saat halaman dibuka
});
</script>
@endsection
