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
      <input type="text" class="form-control" id="nomor" name="nomor_document" disabled required>
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
        <select name="siklus_bisnis" id="siklus_bisnis" class="form-control" required>
          <option value="" disabled selected>Pilih Siklus Bisnis</option>
          @foreach ($business_cycles as $cycle)
            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-6">
        <label for="proses">Proses Bisnis</label>
        <select name="proses_bisnis" id="proses_bisnis" class="form-control" required>
          <option disabled selected>Pilih Proses Bisnis</option>
          @foreach ($business_processes as $process)
            <option value="{{ $process->id }}">{{ $process->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label for="bpo">Business Process Owner</label>
        <select id="bpo_field" class="form-control" disabled>
          <option value="" disabled selected>Pilih Business Process Owner</option>
          @foreach ($bpos as $bpo)
            <option value="{{ $bpo->id }}">{{ $bpo->nama }}</option>
          @endforeach
        </select>

        {{-- Hidden input to actually send data --}}
        <input type="hidden" name="business_process_owner" id="bpo_hidden">
      </div>

      <div class="col-md-6">
        <label for="jenis">Jenis Document</label>
        <select name="jenis_document" id="jenis_document" class="form-control" required>
          <option value="" disabled selected>Pilih Jenis Dokumen</option>
          @foreach ($document_types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label for="version">Version</label>
      <input type="text" name="version" id="version" class="form-control" placeholder="Contoh: 001" value="001">
    </div>

    <div class="mb-3">
      <label for="file">Additional File</label>
      <input type="file" class="form-control" id="file" name="additional_file" accept=".pdf,.doc,.docx,.zip,.rar">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  let versionTimeout;

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

  document.getElementById('version').addEventListener('input', function () {
      clearTimeout(versionTimeout);

      let version = this.value;

      versionTimeout = setTimeout(() => {
        if (version.value !== '001') {
          Swal.fire({
              icon: 'warning',
              title: 'Versi Tidak Sesuai Urutan',
              text: 'Yakin menginputkan versi ' + version + '?',
              showCancelButton: true,
              confirmButtonText: 'Ya, lanjutkan',
              cancelButtonText: 'Batal',
              reverseButtons: true,
          }).then((result) => {
              if (result.dismiss === Swal.DismissReason.cancel) {
                  this.value = '001';
              }
          });
        }
      }, 1000);
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

  document.getElementById('jenis_document').addEventListener('change', function () {
      // ✅ Kalau sudah terisi semua, isi nomor sesuai jenis
      let selectedOption = this.options[this.selectedIndex].text;
      let nomorInput = document.getElementById('nomor');
      nomorInput.disabled = true;

      let kode = random3Char();

      if (selectedOption === 'Prosedur') {
          nomorInput.value = 'PRO/' + kode + '/001';
      } 
      if (selectedOption === 'Instruksi Kerja') {
          nomorInput.value = 'IKA/' + kode + '/001';
      } 
      if (selectedOption === 'Form') {
          nomorInput.value = 'FRM/' + kode + '/001';
      } 
      if (selectedOption === 'Bispro') {
        let siklus  = document.getElementById('siklus_bisnis').value;
        let proses  = document.getElementById('proses_bisnis').value;
        let tanggal = document.getElementById('tanggal').value;
        let versi   = document.getElementById('version').value;
        let nama    = document.getElementById('nama').value.trim();
        
        let tanggalObj = new Date(tanggal);

        let day = String(tanggalObj.getDate()).padStart(2, '0');
        let monthName = tanggalObj.toLocaleString('id-ID', { month: 'long' }); // "September"
        let year = tanggalObj.getFullYear();

        let formattedTanggal = `${day}${monthName}${year}`;
        
        // ✅ Cek apakah semua field wajib sudah diisi
        if (!siklus || !proses || !nama || !versi || !tanggal ) {
            Swal.fire({
                icon: 'warning',
                title: 'Lengkapi Data',
                text: 'Harap isi Siklus Bisnis, Proses Bisnis, Nomor Versi, Tanggal Terbit, dan Nama Dokumen terlebih dahulu!',
                confirmButtonText: 'OK'
            });

            this.value = '';

            return;
        }
        
        nomorInput.value = idToLetter(siklus) + '.' + String(proses).padStart(2, '0') + ' - ' + nama + '_R' + year + '_V' + versi + '_' + formattedTanggal;
      }

      nomorInput.disabled = false;
  });

  function random3Char() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 3; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }

  function idToLetter(id) {
    let letter = '';
    while (id > 0) {
      id--; // shift ke 0-based
      letter = String.fromCharCode(65 + (id % 26)) + letter;
      id = Math.floor(id / 26); // PEMBAGIAN, bukan Math.floor(id, 26)
    }
    return letter;
  }
</script>


@endsection
