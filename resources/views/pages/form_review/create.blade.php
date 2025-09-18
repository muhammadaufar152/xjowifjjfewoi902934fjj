@extends('layouts.app-document')

@section('content')
<div class="container mt-4">
  <h3 class="fw-bold mb-4">Tambah Dokumen</h3>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif


  <form action="{{ route('document.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
      <label for="nomor">Nomor Document</label>
      <input type="text" class="form-control" id="nomor" name="nomor_document" required>
      <small id="nomorFeedback" class="text-danger d-none"></small>

      @error('nomor_document')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label for="nama">Nama Document</label>
        <input type="text" class="form-control" id="nama" name="nama_document" required>
      </div>
      <div class="col-md-6">
        <label for="tanggal">Tanggal Terbit</label>
        <input type="date" class="form-control" id="tanggal" name="tanggal_terbit" required>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label for="siklus">Siklus Bisnis</label>
        <select name="business_cycle_id" class="form-control" required>
          <option value="" disabled selected>Pilih Siklus Bisnis</option>
          @foreach($businessCycles as $cycle)
            <option value="{{ $cycle->id }}">{{ $cycle->nama }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-6">
        <label for="proses">Proses Bisnis</label>
        <select name="business_process_id" class="form-control" required>
          <option value="" disabled selected>Pilih Proses Bisnis</option>
          @foreach($businessProcesses as $process)
            <option value="{{ $process->id }}">{{ $process->nama }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label for="bpo">Business Process Owner</label>
        <select name="business_process_owner_id" id="bpo_field" class="form-control" required>
          <option value="" disabled selected>Pilih Business Process Owner</option>
          @foreach($businessProcessOwners as $owner)
            <option value="{{ $owner->id }}">{{ $owner->nama }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-6">
        <label for="jenis">Jenis Document</label>
        <select name="document_type_id" id="jenis_document" class="form-control" required>
          <option value="" disabled selected>Pilih Jenis Dokumen</option>
          <option value="1">Bispro Level 2</option>
          <option value="2">Prosedur</option>
          <option value="3">Instruksi Kerja</option>
          <option value="4">Form</option>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label for="version">Version</label>
      <input type="text" name="version" class="form-control" placeholder="Contoh: 1.0" required>
    </div>

    <div class="mb-3">
      <label for="file">Additional File</label>
      <input type="file" class="form-control" id="file" name="additional_file" accept=".pdf,.doc,.docx,.zip,.rar">
      <small class="text-muted d-block mt-1">
        Maksimal ukuran file <strong>50 MB</strong>. 
        Gunakan format <code>.pdf</code>, <code>.doc</code>, <code>.docx</code>, <code>.zip</code>, atau <code>.rar</code>.
      </small>
      <small>
        <div id="file-error" class="text-danger mt-1" style="display:none;"></div>
      </small>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('document') }}" class="btn btn-secondary">Back</a>
      <button type="submit" class="btn btn-success">Save Change</button>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(function () {
    const $jenis = $('#jenis_document');
    const $bpoSelect = $('#bpo_field');

    function toggleBPO() {
      const selectedJenis = $jenis.val();

      if (selectedJenis === '1') { // Asumsi ID untuk "Bispro Level 2" adalah 1
        $bpoSelect.prop('disabled', true).val('');
      } else {
        $bpoSelect.prop('disabled', false);
      }
    }

    $jenis.on('change', toggleBPO);
    toggleBPO();
  });

  document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("file");
    const errorDiv = document.getElementById("file-error");

    if (fileInput) {
      fileInput.addEventListener("change", function () {
        const file = fileInput.files[0];
        errorDiv.style.display = "none";
        errorDiv.textContent = "";
        if (!file) return;

        const maxSize = 50  * 1024 * 1024; // 50 MB dalam byte
        console.log('size:' + file.size);
        
        if (file.size > maxSize) {
          errorDiv.textContent = `❌ File terlalu besar (${(file.size / 1024 / 1024).toFixed(2)} MB). 
          Silakan kompres terlebih dahulu sebelum mengunggah.`;
          errorDiv.style.display = "block";
          fileInput.value = ""; // reset input supaya user bisa pilih ulang
        }
      });
    }
  });

  document.getElementById('nomor').addEventListener('input', function () {
      let nomor = this.value;
      let feedback = document.getElementById('nomorFeedback');

      if (nomor.length === 0) {
          feedback.classList.add('d-none');
          feedback.textContent = "";
          return;
      }

      fetch("{{ route('document.checkNomor') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
          },
          body: JSON.stringify({ nomor_document: nomor }),
      })
      .then(response => response.json())
      .then(data => {
          if (data.exists) {
              feedback.textContent = data.message;
              feedback.classList.remove('d-none');
          } else {
              feedback.classList.add('d-none');
              feedback.textContent = "";
          }
      })
      .catch(error => console.error('Error:', error));
  });
</script>

@endsection