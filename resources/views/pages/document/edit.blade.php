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
              <option value="" disabled>Pilih Siklus Bisnis</option>
              <option value="Revenue" {{ $document->siklus_bisnis == 'Revenue' ? 'selected' : '' }}>Revenue</option>
              <option value="Cost" {{ $document->siklus_bisnis == 'Cost' ? 'selected' : '' }}>Cost</option>
              <option value="Tax" {{ $document->siklus_bisnis == 'Tax' ? 'selected' : '' }}>Tax</option>
              <option value="Procurement & Asset Management" {{ $document->siklus_bisnis == 'Procurement & Asset Management' ? 'selected' : '' }}>Procurement & Asset Management</option>
              <option value="Financial Reporting" {{ $document->siklus_bisnis == 'Financial Reporting' ? 'selected' : '' }}>Financial Reporting</option>
              <option value="Treasury" {{ $document->siklus_bisnis == 'Treasury' ? 'selected' : '' }}>Treasury</option>
              <option value="Planning & System Management" {{ $document->siklus_bisnis == 'Planning & System Management' ? 'selected' : '' }}>Planning & System Management</option>
              <option value="General Affair" {{ $document->siklus_bisnis == 'General Affair' ? 'selected' : '' }}>General Affair</option>
              <option value="IT Management" {{ $document->siklus_bisnis == 'IT Management' ? 'selected' : '' }}>IT Management</option>
            </select>
          </div>
          <div class="col-md-6">
            <label>Proses Bisnis</label>
            <select name="proses_bisnis" class="form-control" required>
              <option value="" disabled>Pilih Proses Bisnis</option>
              <option value="Fufillment" {{ $document->proses_bisnis == 'Fufillment' ? 'selected' : '' }}>Fufillment</option>
              <option value="Assurance" {{ $document->proses_bisnis == 'Assurance' ? 'selected' : '' }}>Assurance</option>
              <option value="Billing" {{ $document->proses_bisnis == 'Billing' ? 'selected' : '' }}>Billing</option>
              <option value="Financial Management" {{ $document->proses_bisnis == 'Financial Management' ? 'selected' : '' }}>Financial Management</option>
              <option value="Procurement" {{ $document->proses_bisnis == 'Procurement' ? 'selected' : '' }}>Procurement</option>
              <option value="Asset Management" {{ $document->proses_bisnis == 'Asset Management' ? 'selected' : '' }}>Asset Management</option>
              <option value="HCM" {{ $document->proses_bisnis == 'HCM' ? 'selected' : '' }}>HCM</option>
              <option value="Audit & Risk Management" {{ $document->proses_bisnis == 'Audit & Risk Management' ? 'selected' : '' }}>Audit & Risk Management</option>
              <option value="Strategic & Enterprise Management" {{ $document->proses_bisnis == 'Strategic & Enterprise Management' ? 'selected' : '' }}>Strategic & Enterprise Management</option>
              <option value="IT Management" {{ $document->proses_bisnis == 'IT Management' ? 'selected' : '' }}>IT Management</option>
              <option value="General Affair" {{ $document->proses_bisnis == 'General Affair' ? 'selected' : '' }}>General Affair</option>
              <option value="Enterprise Governance" {{ $document->proses_bisnis == 'Enterprise Governance' ? 'selected' : '' }}>Enterprise Governance</option>
              <option value="Performance Report" {{ $document->proses_bisnis == 'Performance Report' ? 'selected' : '' }}>Performance Report</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="bpo">Business Process Owner</label>
            <select id="bpo_field" class="form-control" disabled>
              <option value="" disabled>Pilih Business Process Owner</option>
              <option value="FINANCE" {{ $document->business_process_owner == 'FINANCE' ? 'selected' : '' }}>FINANCE</option>
              <option value="HUMAN CAPITAL MANAGEMENT" {{ $document->business_process_owner == 'HUMAN CAPITAL MANAGEMENT' ? 'selected' : '' }}>HUMAN CAPITAL MANAGEMENT</option>
              <option value="SERVICE DELIVERY" {{ $document->business_process_owner == 'SERVICE DELIVERY' ? 'selected' : '' }}>SERVICE DELIVERY</option>
              <option value="NETWORK OPERATION" {{ $document->business_process_owner == 'NETWORK OPERATION' ? 'selected' : '' }}>NETWORK OPERATION</option>
              <option value="CORPORATE SECRETARY" {{ $document->business_process_owner == 'CORPORATE SECRETARY' ? 'selected' : '' }}>CORPORATE SECRETARY</option>
              <option value="ASSET, PROCUREMENT & LOGISTICS" {{ $document->business_process_owner == 'ASSET, PROCUREMENT & LOGISTICS' ? 'selected' : '' }}>ASSET, PROCUREMENT & LOGISTICS</option>
              <option value="AUDIT & RISK MANAGEMENT" {{ $document->business_process_owner == 'AUDIT & RISK MANAGEMENT' ? 'selected' : '' }}>AUDIT & RISK MANAGEMENT</option>
              <option value="TRANSFORMATION" {{ $document->business_process_owner == 'TRANSFORMATION' ? 'selected' : '' }}>TRANSFORMATION</option>
              <option value="BILLING & COLLECTION" {{ $document->business_process_owner == 'BILLING & COLLECTION' ? 'selected' : '' }}>BILLING & COLLECTION</option>
              <option value="PRODUCT DEVELOPMENT & PROJECT MANAGEMENT" {{ $document->business_process_owner == 'PRODUCT DEVELOPMENT & PROJECT MANAGEMENT' ? 'selected' : '' }}>PRODUCT DEVELOPMENT & PROJECT MANAGEMENT</option>
              <option value="STRATEGIC BUSINESS DEVELOPMENT" {{ $document->business_process_owner == 'STRATEGIC BUSINESS DEVELOPMENT' ? 'selected' : '' }}>STRATEGIC BUSINESS DEVELOPMENT</option>
              <option value="SYSTEM PLANNING & MANAGEMENT" {{ $document->business_process_owner == 'SYSTEM PLANNING & MANAGEMENT' ? 'selected' : '' }}>SYSTEM PLANNING & MANAGEMENT</option>
              <option value="COMMERCE SEGMENT 1" {{ $document->business_process_owner == 'COMMERCE SEGMENT 1' ? 'selected' : '' }}>COMMERCE SEGMENT 1</option>
              <option value="COMMERCE SEGMENT 2" {{ $document->business_process_owner == 'COMMERCE SEGMENT 2' ? 'selected' : '' }}>COMMERCE SEGMENT 2</option>
              <option value="COMMERCE SEGMENT 3" {{ $document->business_process_owner == 'COMMERCE SEGMENT 3' ? 'selected' : '' }}>COMMERCE SEGMENT 3</option>
              <option value="SATELIT OPERATION" {{ $document->business_process_owner == 'SATELIT OPERATION' ? 'selected' : '' }}>SATELIT OPERATION</option>
              <option value="DATACOM, ADJACENT SERVICE & IT" {{ $document->business_process_owner == 'DATACOM, ADJACENT SERVICE & IT' ? 'selected' : '' }}>DATACOM, ADJACENT SERVICE & IT</option>
              <option value="DIREKTORAT OPERASI" {{ $document->business_process_owner == 'DIREKTORAT OPERASI' ? 'selected' : '' }}>DIREKTORAT OPERASI</option>
              <option value="INTERNAL AUDIT & RISK MANAGEMENT" {{ $document->business_process_owner == 'INTERNAL AUDIT & RISK MANAGEMENT' ? 'selected' : '' }}>INTERNAL AUDIT & RISK MANAGEMENT</option>
              <option value="GOVERNMENT & REGIONAL SERVICE" {{ $document->business_process_owner == 'GOVERNMENT & REGIONAL SERVICE' ? 'selected' : '' }}>GOVERNMENT & REGIONAL SERVICE</option>
              <option value="MINING, MARITIME & AVIATION SERVICE" {{ $document->business_process_owner == 'MINING, MARITIME & AVIATION SERVICE' ? 'selected' : '' }}>MINING, MARITIME & AVIATION SERVICE</option>
              <option value="OTHER LISENCE OPERATOR SERVICE" {{ $document->business_process_owner == 'OTHER LISENCE OPERATOR SERVICE' ? 'selected' : '' }}>OTHER LISENCE OPERATOR SERVICE</option>
              <option value="DIREKTORAT KOMERSIAL" {{ $document->business_process_owner == 'DIREKTORAT KOMERSIAL' ? 'selected' : '' }}>DIREKTORAT KOMERSIAL</option>
              <option value="ENTERPRISE SERVICE" {{ $document->business_process_owner == 'ENTERPRISE SERVICE' ? 'selected' : '' }}>ENTERPRISE SERVICE</option>
              <option value="REGIONAL & CONSUMER SERVICE" {{ $document->business_process_owner == 'REGIONAL & CONSUMER SERVICE' ? 'selected' : '' }}>REGIONAL & CONSUMER SERVICE</option>
              <option value="GOVERNMENT SERVICE" {{ $document->business_process_owner == 'GOVERNMENT SERVICE' ? 'selected' : '' }}>GOVERNMENT SERVICE</option>
            </select>

            {{-- Hidden input to actually send data --}}
            <input type="hidden" name="business_process_owner" id="bpo_hidden">
          </div>

          <div class="col-md-6">
            <label for="jenis">Jenis Document</label>
            <select name="jenis_document" id="jenis_document" class="form-control" required>
              <option value="" disabled>Pilih Jenis Dokumen</option>
              <option value="Bispro" {{ $document->jenis_document == 'Bispro' ? 'selected' : '' }}>Bispro</option>
              <option value="Prosedur" {{ $document->jenis_document == 'Prosedur' ? 'selected' : '' }}>Prosedur</option>
              <option value="Instruksi Kerja" {{ $document->jenis_document == 'Instruksi Kerja' ? 'selected' : '' }}>Instruksi Kerja</option>
              <option value="Form" {{ $document->jenis_document == 'Form' ? 'selected' : '' }}>Form</option>
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
        $bpoSelect.prop('disabled', true).val('TIDAK ADA');
        $bpoHidden.val('TIDAK ADA');
      } else {
        $bpoSelect.prop('disabled', false);
        // Pastikan nilai awal di set dari hidden input
        const initialValue = '{{ $document->business_process_owner }}';
        if (initialValue !== 'TIDAK ADA') {
            $bpoSelect.val(initialValue);
        }
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