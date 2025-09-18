@extends('layouts.app-document')

@section('content')
<div class="container py-4">
    <h3 class="fw-bold mb-4">View Document</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Di Upload Oleh:</strong> {{ $document->creator->name ?? '-' }}</p>
            <p><strong>Nama Dokumen:</strong> {{ $document->nama_document }}</p>
            <p><strong>Nomor Dokumen:</strong> {{ $document->nomor_document }}</p>
            <p><strong>Tanggal Terbit:</strong> {{ \Carbon\Carbon::parse($document->tanggal_terbit)->translatedFormat('Y-F-d') }}</p>
            <p><strong>Siklus Bisnis:</strong> {{ $document->siklus_bisnis }}</p>
            <p><strong>Proses Bisnis:</strong> {{ $document->proses_bisnis }}</p>
            <p><strong>Jenis Dokumen:</strong> {{ $document->jenis_document }}</p>
            <p><strong>Business Process Owner:</strong> {{ $document->business_process_owner }}</p>
            <p><strong>Version:</strong> {{ $document->version }}</p>
            @php
                $chain = $document->parent
                    ? collect([$document->parent])->merge($document->parent->relatedVersions)
                    : collect([$document])->merge($document->relatedVersions ?? collect());

                $latest = $chain->sortByDesc('created_at')->first();
                $isLatest = $document->id === $latest->id;
            @endphp

            <p>
            <strong>Status:</strong>
            @if ($isLatest)
                <span class="badge bg-success">Up to date</span>
            @else
                <span class="badge bg-secondary">Obsolete</span>
            @endif
            </p>

            <p><strong>Waktu Upload:</strong> {{ \Carbon\Carbon::parse($document->created_at)->format('d M Y H:i') }}</p>
            @if ($document->additional_file)
                <a href="{{ asset('storage/' . $document->additional_file) }}" target="_blank">Download PDF</a>
            @endif
            
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('document') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
@endsection
