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
        <select name="siklus_bisnis" class="form-control" required>
          <option value="" disabled selected>Pilih Siklus Bisnis</option>
          <option value="Revenue">Revenue</option>
          <option value="Cost">Cost</option>
          <option value="Tax">Tax</option>
          <option value="Procurement & Asset Management">Procurement & Asset Management</option>
          <option value="Financial Reporting">Financial Reporting</option>
          <option value="Treasury">Treasury</option>
          <option value="Planning & System Management">Planning & System Management</option>
          <option value="General Affair">General Affair</option>
          <option value="IT Management">IT Management</option>
        </select>
      </div>

      <div class="col-md-6">
        <label for="proses">Proses Bisnis</label>
        <select name="proses_bisnis" class="form-control" required>
          <option value="" disabled selected>Pilih Proses Bisnis</option>
          <option value="Fufillment">Fufillment</option>
          <option value="Assurance">Assurance</option>
          <option value="Billing">Billing</option>
          <option value="Financial Management">Financial Management</option>
          <option value="Procurement">Procurement</option>
          <option value="Asset Management">Asset Management</option>
          <option value="HCM">HCM</option>
          <option value="Audit & Risk Management">Audit & Risk Management</option>
          <option value="Strategic & Enterprise Management">Strategic & Enterprise Management</option>
          <option value="IT Management">IT Management</option>
          <option value="General Affair">General Affair</option>
          <option value="Enterprise Governance">Enterprise Governance</option>
          <option value="Performance Report">Performance Report</option>
        </select>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label for="bpo">Business Process Owner</label>
        <select id="bpo_field" class="form-control" disabled>
          <option value="" disabled selected>Pilih Business Process Owner</option>
          <option value="FINANCE">FINANCE</option>
          <option value="HUMAN CAPITAL MANAGEMENT">HUMAN CAPITAL MANAGEMENT</option>
          <option value="SERVICE DELIVERY">SERVICE DELIVERY</option>
          <option value="NETWORK OPERATION">NETWORK OPERATION</option>
          <option value="CORPORATE SECRETARY">CORPORATE SECRETARY</option>
          <option value="ASSET, PROCUREMENT & LOGISTICS">ASSET, PROCUREMENT & LOGISTICS</option>
          <option value="AUDIT & RISK MANAGEMENT">AUDIT & RISK MANAGEMENT</option>
          <option value="TRANSFORMATION">TRANSFORMATION</option>
          <option value="BILLING & COLLECTION">BILLING & COLLECTION</option>
          <option value="PRODUCT DEVELOPMENT & PROJECT MANAGEMENT">PRODUCT DEVELOPMENT & PROJECT MANAGEMENT</option>
          <option value="STRATEGIC BUSINESS DEVELOPMENT">STRATEGIC BUSINESS DEVELOPMENT</option>
          <option value="SYSTEM PLANNING & MANAGEMENT">SYSTEM PLANNING & MANAGEMENT</option>
          <option value="COMMERCE SEGMENT 1">COMMERCE SEGMENT 1</option>
          <option value="COMMERCE SEGMENT 2">COMMERCE SEGMENT 2</option>
          <option value="COMMERCE SEGMENT 3">COMMERCE SEGMENT 3</option>
          <option value="SATELIT OPERATION">SATELIT OPERATION</option>
          <option value="DATACOM, ADJACENT SERVICE & IT">DATACOM, ADJACENT SERVICE & IT</option>
          <option value="DIREKTORAT OPERASI">DIREKTORAT OPERASI</option>
          <option value="INTERNAL AUDIT & RISK MANAGEMENT">INTERNAL AUDIT & RISK MANAGEMENT</option>
          <option value="GOVERNMENT & REGIONAL SERVICE">GOVERNMENT & REGIONAL SERVICE</option>
          <option value="MINING, MARITIME & AVIATION SERVICE">MINING, MARITIME & AVIATION SERVICE</option>
          <option value="OTHER LISENCE OPERATOR SERVICE">OTHER LISENCE OPERATOR SERVICE</option>
          <option value="DIREKTORAT KOMERSIAL">DIREKTORAT KOMERSIAL</option>
          <option value="ENTERPRISE SERVICE">ENTERPRISE SERVICE</option>
          <option value="REGIONAL & CONSUMER SERVICE">REGIONAL & CONSUMER SERVICE</option>
          <option value="GOVERNMENT SERVICE">GOVERNMENT SERVICE</option>
        </select>

        {{-- Hidden input to actually send data --}}
        <input type="hidden" name="business_process_owner" id="bpo_hidden">
      </div>

      <div class="col-md-6">
        <label for="jenis">Jenis Document</label>
        <select name="jenis_document" id="jenis_document" class="form-control" required>
          <option value="" disabled selected>Pilih Jenis Dokumen</option>
          <option value="Bispro Level 2">Bispro Level 2</option>
          <option value="Prosedur">Prosedur</option>
          <option value="Instruksi Kerja">Instruksi Kerja</option>
          <option value="Form">Form</option>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label for="version">Version</label>
      <input type="text" name="version" class="form-control" placeholder="Contoh: 1.0">
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
    const $bpoHidden = $('#bpo_hidden');

    // Fungsi untuk sinkronisasi input tersembunyi
    function syncBPOHidden() {
      $bpoHidden.val($bpoSelect.val());
    }

    // Fungsi untuk mengatur kondisi disabled & hidden value
    function toggleBPO() {
      const selectedJenis = $jenis.val();

      if (selectedJenis === 'Bispro Level 2') {
        $bpoSelect.prop('disabled', true).val('');
        $bpoHidden.val('TIDAK ADA'); // Default jika Prosedur
      } else {
        $bpoSelect.prop('disabled', false);
        syncBPOHidden(); // Salin nilai ke hidden input
      }
    }

    // Event listener saat select BPO berubah
    $bpoSelect.on('change', syncBPOHidden);

    // Event listener saat jenis dokumen berubah
    $jenis.on('change', toggleBPO);

    // Jalankan saat halaman pertama kali dimuat
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
