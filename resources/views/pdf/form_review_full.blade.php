@php
  // ====== Data dasar dari controller ======
  $meta  = $meta ?? [];
  $exec  = $review->exec_summary ?? [];
  $items = $review->review_items ?? [];
  $sign  = $review->signers ?? [];
  $loc   = $review->lokasi_tanggal ?? '';

  // Normalisasi teks hasil review & ruang lingkup
  $hasilReviewText = is_array($items) ? implode("\n", $items) : (string) $items;
  $ruangLingkupArr = $exec['ruang_lingkup'] ?? [];
  $ruangLingkupTxt = is_array($ruangLingkupArr) ? implode("\n", $ruangLingkupArr) : (string) $ruangLingkupArr;

  // ====== Logo -> data URI (lebih aman di DomPDF) ======
  $logoPath     = public_path('images/logo-telkomsat.png');
  $logoDataUri  = null;
  if (is_file($logoPath)) {
      try {
          $mime = 'image/png';
          $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
      } catch (\Throwable $e) {
          $logoDataUri = null;
      }
  }

  // ====== Helper tanggal aman ======
  $rawTgl = $meta['tanggal_permohonan'] ?? ($review->tanggal_masuk ?? null);
  try {
      if ($rawTgl instanceof \Carbon\Carbon) {
          $tglPermohonan = $rawTgl->translatedFormat('d F Y');
      } else {
          $tglPermohonan = $rawTgl ? \Carbon\Carbon::parse($rawTgl)->translatedFormat('d F Y') : '-';
      }
  } catch (\Throwable $e) {
      $tglPermohonan = '-';
  }
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Form Hasil Review Bispro</title>
  <style>
    /* ====== PAGE ====== */
    @page { size: A4; margin: 42mm 22mm 18mm 22mm; } /* top right bottom left */

    /* Gunakan DejaVu Sans (bawaan DomPDF, aman untuk Unicode) */
    body {
      font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
      font-size: 10.6pt; line-height: 1.18; color: #111;
    }

    /* ====== LAYOUT KONTEN ====== */
    .content { width: 165mm; margin: 0 auto; }
    .tight   { line-height: 1.12; }

    /* ====== HEADER ====== */
    .logo  { width: 75mm; }
    .title { text-align:center; font-size: 12.2pt; font-weight:bold; letter-spacing:.2px; margin-top: 6mm; }

    /* ====== TABEL UTAMA ====== */
    table { width:100%; border-collapse:collapse; }
    .tbl-main { border:0.8pt solid #000; }
    .tbl-main td, .tbl-main th {
      border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000;
      border-left:0; border-right:0;
      padding: 2.4mm 3.2mm; vertical-align: top;
    }
    .tbl-main tr:first-child td, .tbl-main tr:first-child th { border-top:0; }
    .tbl-main tr:last-child td { border-bottom:0; }

    .m-label { width: 43%; font-weight:bold; }
    .m-colon { width: 3%;  text-align:center; }
    .m-value { width: 54%; }

    /* ====== SIGNATURE ====== */
    .sign-head th {
      border: 0.8pt solid #000; padding: 2.8mm 3mm; text-align:center; font-weight:bold;
    }
    .sign-pad td { height: 30mm; border:0.8pt solid #000; }
    .sign-info td {
      border:0.8pt solid #000; border-top:0; text-align:center; padding: 2.6mm 2mm;
    }
    .sign-name { font-weight:bold; display:block; }
    .sign-role { font-size: 9.2pt; color:#333; display:block; margin-top:1mm; }

    /* ====== EXECUTIVE SUMMARY ====== */
    .es-logo  { width: 75mm; }
    .es-title { text-align:center; font-style:italic; font-weight:bold; font-size: 12pt; margin-top: 3mm; }

    .tbl-es { border:0.8pt solid #000; }
    .tbl-es td, .tbl-es th {
      border-top:0.8pt solid #000; border-bottom:0.8pt solid #000;
      border-left:0; border-right:0;
      padding: 2.4mm 3.2mm; vertical-align: top;
    }
    .tbl-es tr:first-child td, .tbl-es tr:first-child th { border-top:0; }
    .tbl-es tr:last-child td { border-bottom:0; }

    .es-label { width:43%; font-weight:bold; }
    .es-colon { width:3%;  text-align:center; }
    .es-value { width:54%; }

    .avoid-break { page-break-inside: avoid; }
  </style>
</head>
<body>

  {{-- ====== NOMOR HALAMAN ====== --}}
  <script type="text/php">
  if (isset($pdf)) {
      $text = "Halaman {PAGE_NUM} / {PAGE_COUNT}";
      $size = 9;
      $font = $fontMetrics->get_font("DejaVu Sans", "normal");
      $w = $pdf->get_width(); $h = $pdf->get_height();
      $textWidth = $fontMetrics->get_text_width($text, $font, $size);
      $x = ($w - $textWidth) / 2; $y = $h - 18;
      $pdf->page_text($x, $y, $text, $font, $size, [0,0,0]);
  }
  </script>

  <div class="content">
    {{-- ====== HEADER ====== --}}
    <table style="border:0; margin-bottom: 6mm;">
      <tr>
        <td style="border:0;">
          @if($logoDataUri)
            <img class="logo" src="{{ $logoDataUri }}" alt="Telkomsat">
          @endif
        </td>
      </tr>
      <tr>
        <td style="border:0;">
          <div class="title">FORM HASIL REVIEW BISPRO</div>
        </td>
      </tr>
    </table>

    {{-- ====== FORM REVIEW ====== --}}
    <table class="tbl-main tight avoid-break">
      <tr>
        <td class="m-label">Nama Dokumen</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $meta['nama_dokumen'] ?? $review->nama_dokumen }}</td>
      </tr>
      <tr>
        <td class="m-label">Nomor Dokumen</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $meta['no_dokumen'] ?? $review->no_dokumen }}</td>
      </tr>
      <tr>
        <td class="m-label">Jenis Dokumen</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $meta['jenis_dokumen'] ?? $review->jenis_dokumen }}</td>
      </tr>
      <tr>
        <td class="m-label">Klasifikasi Siklus</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $meta['klasifikasi_siklus'] ?? $review->klasifikasi_siklus }}</td>
      </tr>
      <tr>
        <td class="m-label">Business Process Owner (BPO)</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $meta['bpo'] ?? ($review->bpoUser->name ?? '-') }}</td>
      </tr>
      <tr>
        <td class="m-label">Tanggal Permohonan</td>
        <td class="m-colon">:</td>
        <td class="m-value">{{ $tglPermohonan }}</td>
      </tr>
      <tr>
        <td class="m-label">Hasil Review</td>
        <td class="m-colon">:</td>
        <td class="m-value">{!! nl2br(e($hasilReviewText ?: '-')) !!}</td>
      </tr>
      <tr>
        <td class="m-label">Rekomendasi</td>
        <td class="m-colon">:</td>
        <td class="m-value">{!! nl2br(e($review->rekomendasi ?? '-')) !!}</td>
      </tr>
    </table>

    {{-- ====== TANDA TANGAN ====== --}}
    <div style="margin:6mm 0 3mm 0;"><strong>Lokasi &amp; Tanggal:</strong> {{ $loc ?: '-' }}</div>

    <table class="avoid-break">
      <tr class="sign-head">
        <th style="width:33%;">Dibuat Oleh</th>
        <th style="width:33%;">Ditinjau Oleh</th>
        <th style="width:34%;">Disetujui Oleh</th>
      </tr>
      <tr class="sign-pad">
        <td></td><td></td><td></td>
      </tr>
      <tr class="sign-info">
        <td>
          <span class="sign-name">{{ $sign['dibuat']['nama'] ?? '-' }}</span>
          <span class="sign-role">{{ $sign['dibuat']['jabatan'] ?? '-' }}</span>
        </td>
        <td>
          <span class="sign-name">{{ $sign['ditinjau']['nama'] ?? '-' }}</span>
          <span class="sign-role">{{ $sign['ditinjau']['jabatan'] ?? '-' }}</span>
        </td>
        <td>
          <span class="sign-name">{{ $sign['disetujui']['nama'] ?? '-' }}</span>
          <span class="sign-role">{{ $sign['disetujui']['jabatan'] ?? '-' }}</span>
        </td>
      </tr>
    </table>

    {{-- ====== EXECUTIVE SUMMARY (HALAMAN BARU) ====== --}}
    <div style="page-break-before:always;"></div>

    @if($logoDataUri)
      <img class="es-logo" src="{{ $logoDataUri }}" alt="Telkomsat">
    @endif
    <div class="es-title">Executive Summary</div>

    <table class="tbl-es tight avoid-break" style="margin-top: 5mm;">
      <tr>
        <td class="es-label">Judul</td>
        <td class="es-colon">:</td>
        <td class="es-value">{{ $exec['judul'] ?? '' }}</td>
      </tr>
      <tr>
        <td class="es-label">Latar Belakang</td>
        <td class="es-colon">:</td>
        <td class="es-value">{!! nl2br(e($exec['latar_belakang'] ?? '')) !!}</td>
      </tr>
      <tr>
        <td class="es-label">Maksud &amp; Tujuan</td>
        <td class="es-colon">:</td>
        <td class="es-value">{!! nl2br(e($exec['maksud_tujuan'] ?? '')) !!}</td>
      </tr>
      <tr>
        <td class="es-label">Ruang Lingkup</td>
        <td class="es-colon">:</td>
        <td class="es-value">{!! nl2br(e($ruangLingkupTxt ?: '')) !!}</td>
      </tr>
      <tr>
        <td class="es-label">Ketentuan yang Dicabut</td>
        <td class="es-colon">:</td>
        <td class="es-value">{!! nl2br(e($exec['ketentuan_dicabut'] ?? '')) !!}</td>
      </tr>
      <tr>
        <td class="es-label">Tanggal Berlaku</td>
        <td class="es-colon">:</td>
        <td class="es-value">
          @php $tb = $exec['tanggal_berlaku'] ?? null; @endphp
          {{ $tb ? \Carbon\Carbon::parse($tb)->translatedFormat('d F Y') : '-' }}
        </td>
      </tr>
      <tr>
        <td class="es-label">Lain-lain</td>
        <td class="es-colon">:</td>
        <td class="es-value">{!! nl2br(e($exec['lain_lain'] ?? '')) !!}</td>
      </tr>
      <tr>
        <td colspan="3">
          <em>Disclaimer Clause: Executive Summary</em> ini hanyalah ringkasan dari standarisasi pelanggan regular,
          critical atau VVIP dan bukan suatu rekomendasi dalam pengambilan keputusan.
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
