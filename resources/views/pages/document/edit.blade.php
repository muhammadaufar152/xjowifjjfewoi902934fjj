@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-10 py-4 px-5 bg-light">
      <h3 class="fw-bold mb-4">Edit Dokumen</h3>

      <form action="{{ route('document.update', $document->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label>Nama Document</label>
          <input type="text" class="form-control" name="nama_document" value="{{ $document->nama_document }}">
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Nomor Document</label>
            <input type="text" class="form-control" name="nomor_document" value="{{ $document->nomor_document }}">
          </div>
          <div class="col-md-6">
            <label>Tanggal Terbit</label>
            <input type="date" class="form-control" name="tanggal_terbit" value="{{ $document->tanggal_terbit }}">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Siklus Bisnis</label>
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
            <label>Proses Bisnis</label>
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
          <option value="Bispro">Bispro</option>
          <option value="Prosedur">Prosedur</option>
          <option value="Instruksi Kerja">Instruksi Kerja</option>
          <option value="Form">Form</option>
        </select>
      </div>
    </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Version</label>
            <input type="text" class="form-control" name="version" value="{{ $document->version }}">
          </div>
        </div>

        <div class="mb-3">
          <label>File Baru (Optional)</label>
          <input type="file" class="form-control" name="additional_file">
          @if ($document->additional_file)
            <small class="text-muted">File saat ini: {{ $document->additional_file }}</small>
          @endif
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ route('document') }}" class="btn btn-secondary">Back</a>
          <button type="submit" class="btn btn-success">Save Change</button>
        </div>
      </form>
    </div>
  </div>
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

      if (selectedJenis === 'Prosedur') {
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
</script>

@endsection
