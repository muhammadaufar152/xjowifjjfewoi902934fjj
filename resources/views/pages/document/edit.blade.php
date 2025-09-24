@extends('layouts.app-document')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-10 py-4 px-5 bg-light">
      <h3 class="fw-bold mb-4">Edit Dokumen</h3>

      <form action="{{ route('document.update', $document->id) }}" id="form-edit" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="is_edit" value="{{ $is_edit }}">
        <div class="mb-3">
          <label>Nama Document</label>
          <input type="text" class="form-control" name="nama_document" value="{{ $document->nama_document }}" 
          @if ($is_edit != 1)
              disabled
          @endif>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Nomor Document</label>
            <input type="text" class="form-control" name="nomor_document" value="{{ $document->nomor_document }}"
            @if ($is_edit != 1)
              disabled
            @endif>
          </div>
          <div class="col-md-6">
            <label>Tanggal Terbit</label>
            <input type="date" class="form-control" id="tanggal_terbit" name="tanggal_terbit" value="{{ $document->tanggal_terbit }}"
            @if ($document->parent_id != null || $is_edit == 1)
                disabled
            @endif>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Siklus Bisnis</label>
            <select name="siklus_bisnis" class="form-control"  required 
            @if ($is_edit != 1)
              disabled
            @endif>
              @foreach ($business_cycles as $cycle)
                <option value="{{ $cycle->id }}" 
                  {{ $document->business_cycle_id == $cycle->id ? 'selected' : '' }}>
                  {{ $cycle->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label>Proses Bisnis</label>
            <select name="proses_bisnis" class="form-control" required 
            @if ($is_edit != 1)
              disabled
            @endif>>
              <option value="" disabled>Pilih Proses Bisnis</option>
              @foreach ($business_processes as $process)
                <option value="{{ $process->id }}" 
                  {{ $document->business_process_id == $process->id ? 'selected' : '' }}>
                  {{ $process->name }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
      <div class="col-md-6">
        <label for="bpo">Business Process Owner</label>
        <select id="bpo_field" class="form-control" disabled>
          @foreach ($bpos as $bpo)
            <option value="{{ $bpo->id }}" 
              {{ $document->business_process_owner_id == $bpo->id ? 'selected' : '' }}>
              {{ $bpo->nama }}
            </option>
          @endforeach
        </select>

            {{-- Hidden input to actually send data --}}
            <input type="hidden" name="business_process_owner" id="bpo_hidden">
          </div>

          <div class="col-md-6">
            <label for="jenis">Jenis Document</label>
            <select name="jenis_document" id="jenis_document" class="form-control" required 
            @if ($is_edit != 1)
              disabled
            @endif>>>
              @foreach ($document_types as $type)
                <option value="{{ $type->id }}" 
                  {{ $document->document_type_id == $type->id ? 'selected' : '' }}>
                  {{ $type->name }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Version</label>
            <input type="text" class="form-control" id="version" name="version" value="{{ $document->version }}" 
            @if ($is_edit == 1)
              disabled
            @endif>
          </div>
        </div>

        <div class="mb-3">
          <label>File Baru (Optional)</label>
          <input type="file" class="form-control" name="additional_file"
          @if ($is_edit != 1)
            disabled
          @endif>
          @if ($document->additional_file)
            <small class="text-muted">File saat ini: {{ $document->additional_file }}</small>
          @endif
          <small class="text-muted d-block mt-1">
            Maksimal ukuran file <strong>50 MB</strong>. 
            Gunakan format <code>.pdf</code>, <code>.doc</code>, atau <code>.docx</code> 
            {{-- , <code>.zip</code>, atau <code>.rar</code>. --}}
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
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(function () {
    if ({{ $is_edit }} == 1) {
      const $jenis = $('#jenis_document');
      const $bpoSelect = $('#bpo_field');
      const $bpoHidden = $('#bpo_hidden');
  
      // Fungsi untuk sinkronisasi input tersembunyi
      function syncBPOHidden() {
        $bpoHidden.val($bpoSelect.val());
      }
  
      // Fungsi untuk mengatur kondisi disabled & hidden value
      function toggleBPO() {
        const selectedJenis = $jenis.find('option:selected').val();;
  
        if (selectedJenis == 2) { // prosedur
          $bpoSelect.prop('disabled', true).val('TIDAK ADA');
          $bpoHidden.val('TIDAK ADA');
        } else {
          $bpoSelect.prop('disabled', false);
          // Pastikan nilai awal di set dari hidden input
          const initialValue = '{{ $document->business_process_owner_id }}';
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
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
      if ({{ $is_edit }} == 1) {
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
      }
      if ({{ $is_edit }} == 0) {
        // 1️⃣ Set min date berdasarkan value atau hari ini
        const tanggalInput = document.getElementById('tanggal_terbit');
        if (tanggalInput) {
            const currentValue = tanggalInput.value;
            const minDate = currentValue ? currentValue : new Date().toISOString().split('T')[0];
            tanggalInput.setAttribute('min', minDate);
        }
  
        // 2️⃣ Ambil last version dari server (string, contoh: "001")
        const lastVersion = "{{ $document->version }}";
  
        // 3️⃣ Form submit dengan SweetAlert + validasi version
        const form = document.getElementById('form-edit');
        form.addEventListener('submit', function (e) {  
            e.preventDefault();
  
            const tanggal = document.getElementById('tanggal_terbit').value;
            let version = document.getElementById('version').value;
  
            // --- Normalisasi version (jadi angka & format lagi)
            let versionNum = parseInt(version, 10);
            let lastVersionNum = parseInt(lastVersion, 10);
  
            // Jika user ketik kosong / NaN -> error
            if (isNaN(versionNum)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Versi Kosong',
                    text: 'Isi versi terlebih dahulu.'
                });
                return;
            }
  
            // Format ulang version supaya selalu 3 digit
            version = String(versionNum).padStart(3, '0');
  
            // Validasi tidak boleh < 001
            if (versionNum < 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Versi Tidak Valid',
                    text: 'Versi tidak boleh kurang dari 001.'
                });
                return;
            }
  
            // Validasi tidak boleh loncat (harus last + 1)
            if (versionNum !== lastVersionNum + 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Versi Tidak Sesuai',
                    text: `Versi harus ${String(lastVersionNum + 1).padStart(3, '0')}, bukan ${version}.`
                });
                return;
            }
  
            // --- Konfirmasi submit ---
            Swal.fire({
                icon: 'warning',
                title: 'Konfirmasi Submit',
                html: `Tanggal terbit: <strong>${tanggal}</strong><br>Version: <strong>${version}</strong><br>Yakin ingin submit?`,
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Update field version dengan format 3 digit sebelum submit
                    document.getElementById('version').value = version;
                    form.submit();
                }
            });
        });
      }
  });
</script>

@endsection