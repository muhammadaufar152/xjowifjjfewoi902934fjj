{{-- resources/views/components/status_badge.blade.php --}}
@php
    /**
     * Props:
     * - $status : string status asal (pending / verifikasi 1/2/3 / selesai / open / progress / request close / cancel / closed / return)
     * - $steps  : (opsional) koleksi step; jika ada yang Rejected -> tampil Cancel
     */

    $raw = strtolower(trim($status ?? ''));

    // Jika ada step Rejected, paksa tampil "cancel"
    $hasRejected = isset($steps) && $steps && collect($steps)->contains(function ($s) {
        return strtolower(trim(data_get($s, 'status', ''))) === 'rejected';
    });
    if ($hasRejected) {
        $display = 'cancel';
        $note = null; // tidak perlu teks tambahan
    } else {
        // Normalisasi typo & alias
        $raw = str_replace('verivikasi', 'verifikasi', $raw); // handle typo

        // Mapping yang diminta:
        // pending         -> open
        // verifikasi 1/2/3-> progress  (+ tampil "Verifikasi N" tanpa label)
        // selesai         -> done
        $note = null;

        if ($raw === 'pending') {
            $display = 'open';
        } elseif (in_array($raw, ['verifikasi 1','verifikasi 2','verifikasi 3'], true)) {
            $display = 'progress';
            $note = ucfirst($raw); // "Verifikasi 1/2/3"
        } elseif ($raw === 'selesai') {
            $display = 'done';
        } else {
            // status lain biarkan apa adanya
            $display = $raw;
        }

        // Kompatibilitas umum
        if ($display === 'closed') {
            $display = 'done';
        }
        if ($display === 'canceled' || $display === 'cancelled') {
            $display = 'cancel';
        }
        if ($display === 'req close') {
            $display = 'request close';
        }
    }

    // Peta status tampilan -> (badge class, label)
    $map = [
        'open'           => ['bg-secondary',           'Open'],
        'progress'       => ['bg-warning text-dark',   'Progress'],
        'request close'  => ['bg-info text-dark',      'Request Close'],
        'done'           => ['bg-success',             'Done'],
        'cancel'         => ['bg-danger',              'Cancel'],
        'return'         => ['bg-warning text-dark',   'Return'],
    ];

    [$cls, $label] = $map[$display] ?? ['bg-light text-dark', ucfirst($display ?: 'Pending')];
@endphp

<span class="badge rounded-pill {{ $cls }}">{{ $label }}</span>
@if($note)
  <small class="text-muted ms-2">{{ $note }}</small>
@endif
