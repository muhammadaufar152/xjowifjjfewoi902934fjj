@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center mt-4">
    <div class="col-10 bg-white rounded p-4">
      <h4 class="fw-bold text-center mb-4">EDIT FORM REVIEW</h4>

      @if ($errors->any())
        <div class="alert alert-danger">
          <strong>Terjadi kesalahan!</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('form_review.update', $review->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="hidden" name="bpo_id" value="{{ $review->bpo_id }}">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Nama Pemohon / BPO</label>
            <input type="text" class="form-control" value="{{ $review->bpoUser->name ?? '-' }}" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Masuk</label>
            <input type="date" class="form-control" name="tanggal_masuk" value="{{ $review->tanggal_masuk }}">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Jenis Permohonan</label>
          <select class="form-select" name="jenis_permohonan" id="jenis_permohonan">
            <option value="Baru"   {{ $review->jenis_permohonan == 'Baru' ? 'selected' : '' }}>Review Dokumen Baru</option>
            <option value="Revisi" {{ $review->jenis_permohonan == 'Revisi' ? 'selected' : '' }}>Review Dokumen Eksisting</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Latar Belakang</label>
          <textarea class="form-control" name="latar_belakang" rows="3">{{ $review->latar_belakang }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Usulan Revisi</label>
          <textarea class="form-control" name="usulan_revisi" rows="3">{{ $review->usulan_revisi }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Jenis Dokumen</label>
          <select class="form-select" name="jenis_dokumen">
            <option value="">Pilih Jenis</option>
            @foreach(['Bispro', 'Prosedur', 'Form', 'IK'] as $jenis)
              <option value="{{ $jenis }}" {{ $review->jenis_dokumen == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Klasifikasi Siklus</label>
          <select class="form-select" name="klasifikasi_siklus">
            <option value="">Pilih Siklus Bisnis</option>
            @foreach([
              'Revenue', 'Cost', 'Tax', 'Procurement & Asset Management',
              'Financial Reporting', 'Treasury', 'Planning & System Management',
              'General Affair', 'IT Management'
            ] as $siklus)
              <option value="{{ $siklus }}" {{ $review->klasifikasi_siklus == $siklus ? 'selected' : '' }}>{{ $siklus }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Nama Dokumen</label>
          <input type="text" class="form-control" name="nama_dokumen" id="nama_dokumen" value="{{ $review->nama_dokumen }}">
        </div>

        <div class="mb-3">
          <label class="form-label">No Dokumen</label>
          <input type="text" class="form-control" name="no_dokumen" id="no_dokumen" value="{{ $review->no_dokumen }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Level Dokumen</label>
          <input type="text" class="form-control" name="level_dokumen" id="level_dokumen" value="{{ $review->level_dokumen }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Perihal</label>
          <input type="text" class="form-control" name="perihal" value="{{ $review->perihal }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Upload Lampiran Baru (Opsional)</label>
          <input type="file" class="form-control" name="lampiran" accept=".pdf,.doc,.docx,.xlsx,.jpg,.jpeg,.png">
          @if ($review->lampiran)
            <small class="text-muted d-block mt-1">
              File saat ini: <a href="{{ route('form_review.download', $review->id) }}" target="_blank">Lihat file</a>
            </small>
          @endif
        </div>

        @php
          // ===== LOGIKA TOMBOL RESUBMIT (lihat keputusan NON-pending terakhir) =====
          $steps = $review->steps ?? collect();
          $sorted = $steps->sortByDesc('tanggal')->sortByDesc('id');
          $lastDecision = $sorted->first(function($s){
              return strtolower($s->status ?? '') !== 'pending';
          });
          $canResubmit = $lastDecision
              && strtolower($lastDecision->tahapan ?? '') === 'officer'
              && in_array(strtolower($lastDecision->status ?? ''), ['tidak setuju','return','ditolak']);
        @endphp

        {{-- Kolom keterangan resubmit: hanya muncul jika bisa resubmit --}}
        @if ($canResubmit)
          <div class="mb-3">
            <label class="form-label">Keterangan Resubmit (akan tampil di Riwayat)</label>
            <textarea name="keterangan_resubmit" class="form-control" rows="3" maxlength="2000" placeholder="Contoh: Perubahan sudah dilakukan sesuai catatan Officer.">{{ old('keterangan_resubmit','') }}</textarea>
            <small class="text-muted">Opsional, maks. 2000 karakter.</small>
          </div>
        @endif

        <div class="d-flex justify-content-between align-items-center">
          <a href="{{ route('form_review.index') }}" class="btn btn-secondary">Back</a>

          @if ($canResubmit)
            <button type="submit" name="resubmit" value="1" class="btn btn-warning">
              Re-submit
            </button>
          @else
            <span></span>
          @endif

          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Freeze fields ketika pilih "Baru" --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const jenis = document.getElementById('jenis_permohonan'); // "Baru" / "Revisi"
  const nama  = document.getElementById('nama_dokumen');
  const nomor = document.getElementById('no_dokumen');
  const level = document.getElementById('level_dokumen');

  if (!jenis || !nama || !nomor || !level) return;

  const mustFreeze = () => {
    const v = (jenis.value || '').toLowerCase().trim();
    const t = (jenis.options[jenis.selectedIndex]?.text || '').toLowerCase();
    return v === 'baru' || t.includes('baru');
  };

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
  apply(); // kondisi awal
});
</script>
@endsection
