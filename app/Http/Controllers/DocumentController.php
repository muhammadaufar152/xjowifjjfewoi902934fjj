<?php

namespace App\Http\Controllers;

use App\Models\BusinessCycle;
use App\Models\BusinessProcess;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;


class DocumentController extends Controller
{
    /**
     * Tampilkan halaman Document.
     * Mendukung quick-filter via query string:
     * - ?status=update|obsolete|pending|return|verifikasi 1|verifikasi 2|selesai
     * - ?siklus=Revenue (nilai harus sama dengan yang tersimpan di kolom siklus_bisnis)
     * - ?jenis=Bispro (nilai harus sama dengan yang tersimpan di kolom jenis_document)
     * - ?q=teks (pencarian nama/no dokumen)
     */
    public function index(Request $request)
    {
        // Ambil filter dari query string
        $filters = [
            'status' => $request->query('status'),
            'siklus' => $request->query('siklus'),
            'jenis'  => $request->query('jenis'),
            'q'      => $request->query('q'),
        ];

        $norm = fn($v) => trim(mb_strtolower((string)$v));

        // Normalisasi variasi penulisan status
        $statusMap = [
            'update'        => ['update', 'updated'],
            'updated'       => ['update', 'updated'],
            'obsolete'      => ['obsolete', 'obselete'],
            'obselete'      => ['obsolete', 'obselete'],
            'pending'       => ['pending', 'menunggu'],
            'return'        => ['return', 'tidak setuju', 'ditolak'],
            'verifikasi 1'  => ['verifikasi 1', 'verifikasi1'],
            'verifikasi 2'  => ['verifikasi 2', 'verifikasi2'],
            'selesai'       => ['selesai'],
        ];

        // Ekspresi SQL untuk menormalkan kolom status (hapus spasi/tab/CRLF dan lowercase)
        $statusExpr = "LOWER(TRIM(REPLACE(REPLACE(REPLACE(IFNULL(status,''), CHAR(13), ''), CHAR(10), ''), CHAR(9), '')))";

        // Mengambil ID dari dokumen yang paling terbaru untuk setiap nomor_document
        $latestIds = Document::select('nomor_document', DB::raw('MAX(id) as id'))
                            ->groupBy('nomor_document');

        $q = Document::with(['downloads.user', 'relatedVersions', 'parent.relatedVersions'])
                     ->joinSub($latestIds, 'latest_docs', function ($join) {
                         $join->on('documents.id', '=', 'latest_docs.id');
                     });

        // Filter STATUS
        if ($filters['status']) {
            $want = $norm($filters['status']);
            $alts = $statusMap[$want] ?? [$filters['status']];

            $q->where(function ($qq) use ($alts, $norm, $statusExpr) {
                foreach ($alts as $s) {
                    $qq->orWhereRaw("documents.$statusExpr = ?", [$norm($s)]);
                }
            });
        }

        // Perbaikan: Mencari ID berdasarkan nama yang dinormalisasi
        if ($filters['siklus']) {
            $cycleId = BusinessCycle::whereRaw("LOWER(TRIM(name)) = ?", [$norm($filters['siklus'])])->first()?->id;
            if ($cycleId) {
                $q->where('documents.business_cycle_id', $cycleId);
            }
        }

        if ($filters['jenis']) {
            $typeId = DocumentType::whereRaw("LOWER(TRIM(name)) = ?", [$norm($filters['jenis'])])->first()?->id;
            if ($typeId) {
                $q->where('documents.document_type_id', $typeId);
            }
        }

        // Pencarian global (nama/no dokumen)
        if ($filters['q']) {
            $q->where(function ($qq) use ($filters) {
                $qq->where('documents.nama_document', 'like', '%'.$filters['q'].'%')
                   ->orWhere('documents.nomor_document', 'like', '%'.$filters['q'].'%');
            });
        }

        $documents = $q->orderByDesc('documents.created_at')->get();

        return view('pages.document.index', [
            'documents' => $documents,
            'filters'   => $filters, // kirim supaya view bisa tahu filter aktif (berguna untuk DataTables auto-search)
        ]);
    }

    public function create()
    {
        $bpos = DB::connection('mysql_sipatra')
            ->table('r_subdit_legal')
            ->join('m_organisasi', 'r_subdit_legal.m_organisasi_id', '=', 'm_organisasi.id')
            ->select('m_organisasi.id', 'm_organisasi.nama')
            ->get();
        $business_cycles = BusinessCycle::get();
        $business_processes = BusinessProcess::get();
        $document_types = DocumentType::get();

        return view('pages.document.create', compact('bpos','business_cycles', 'business_processes', 'document_types'));
    }

    public function store(Request $request)
    {
        $businessProcessOwner = $request->input('business_process_owner') ?? 'TIDAK ADA';

        $request->validate([
            'nama_document'          => 'required',
            'nomor_document'         => 'required',
            'tanggal_terbit'         => 'required|date',
            'siklus_bisnis'          => 'required',
            'proses_bisnis'          => 'required',
            'business_process_owner' => $request->jenis_document !== 2 ? 'required' : 'nullable', // != Prosedur
            'jenis_document'         => 'required',
            'version'                => 'required',
            'additional_file'        => 'nullable|file|mimes:pdf,doc,docx,xlsx,ppt,pptx|max:51200',
        ]);

        $exists = Document::where('nomor_document', $request->nomor_document)->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['nomor_document' => 'Nomor dokumen sudah digunakan, silakan pakai nomor lain.']);
        }

        $filePath = null;
        if ($request->hasFile('additional_file')) {
            $originalName = $request->file('additional_file')->getClientOriginalName();
            $filename = time() . '_' . $originalName;

            $filePath = $request->file('additional_file')->storeAs('additional_files', $filename, 'public');
        }

        Document::create([
            'nomor_document'         => $request->nomor_document,
            'nama_document'          => $request->nama_document,
            'tanggal_terbit'         => $request->tanggal_terbit,
            'business_cycle_id'      => $request->siklus_bisnis, 
            'business_process_id'    => $request->proses_bisnis, 
            'business_process_owner_id' => $request->business_process_owner,
            'document_type_id'         => (int) $request->jenis_document,
            'version'                => $request->version,
            'additional_file'        => $filePath,
            'created_by'             => auth()->id(),
        ]);


        return redirect()->route('document')->with('success', 'Dokumen berhasil ditambahkan');
    }

    public function edit($id, $is_edit = 1)
    {
        $document = Document::findOrFail($id);

        $bpos = DB::connection('mysql_sipatra')
            ->table('r_subdit_legal')
            ->join('m_organisasi', 'r_subdit_legal.m_organisasi_id', '=', 'm_organisasi.id')
            ->select('m_organisasi.id', 'm_organisasi.nama')
            ->get();
        $business_cycles = BusinessCycle::get();
        $business_processes = BusinessProcess::get();
        $document_types = DocumentType::get();

        return view('pages.document.edit', compact('document','bpos', 'business_cycles', 'business_processes', 'document_types', 'is_edit'));
    }

    public function update(Request $request, $id)
    {
        $old = Document::findOrFail($id);

        // Validasi input
        $request->validate([
            'nama_document'          => 'required|string',
            'nomor_document'         => 'required|string',
            'tanggal_terbit'         => 'required|date',
            'siklus_bisnis'          => 'required|integer',
            'proses_bisnis'          => 'required|integer',
            'business_process_owner_id' => 'required',
            'jenis_document'         => 'required|integer',
            'version'                => 'required|string',
            'additional_file'        => 'nullable|file|mimes:pdf,doc,docx,xlsx,ppt,pptx|max:51200',
        ]);

        // Mapping request -> kolom database
        $mappedData = [
            'nomor_document'         => $request->nomor_document,
            'nama_document'          => $request->nama_document,
            'tanggal_terbit'         => $request->tanggal_terbit,
            'business_cycle_id'      => $request->siklus_bisnis, 
            'business_process_id'    => $request->proses_bisnis, 
            'business_process_owner_id' => $request->business_process_owner,
            'document_type_id'         => (int) $request->jenis_document,
            'version'                => $request->version,
        ];

        $versionChanged = $old->version !== $request->version;
        $fileChanged    = $request->hasFile('additional_file');

        // Jika hanya update (tidak buat versi baru)
        if ($request->is_edit == 1) {
            // Jika versi & file tidak berubah, update langsung record lama
            if (!$versionChanged && !$fileChanged) {
                $old->update($mappedData);
                return redirect()->route('document')->with('success', 'Dokumen berhasil diperbarui.');
            }

            // Proses file
            $filePath = $old->additional_file;

            if ($fileChanged) {
                $file = $request->file('additional_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('additional_files', $filename, 'public');
            } elseif ($old->additional_file && Storage::disk('public')->exists($old->additional_file)) {
                // Salin file lama untuk versi baru
                $newFilename = time() . '_copy_' . basename($old->additional_file);
                $newPath = 'additional_files/' . $newFilename;
                Storage::disk('public')->copy($old->additional_file, $newPath);
                $filePath = $newPath;
            } else {
                \Log::warning("File lama tidak ditemukan saat ingin disalin: " . $old->additional_file);
            }

            $mappedData['additional_file'] = $filePath;
            $old->update($mappedData);

            $msg = "Dokumen berhasil diupdate";
        } else {
            // Buat versi baru
            $newData = array_merge(
                $old->toArray(),
                $mappedData,
                [
                    'parent_id'      => $old->parent_id ?? $old->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );

            unset($newData['id']); // Pastikan tidak pakai ID lama
            unset($newData['updated_by']); // jika ada kolom ini

            Document::create($newData);
            $msg = "Versi baru berhasil ditambahkan";
        }

        return redirect()->route('document')->with('success', $msg);
    }

    
    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();

        return redirect()->route('document')->with('success', 'Dokumen berhasil dihapus.');
    }

    public function show($id)
    {
        $document = Document::with('creator', 'relatedVersions')->findOrFail($id);
        return view('pages.document.show', compact('document'));
    }

    public function downloadWithWatermark($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->additional_file) {
            return back()->withErrors(['File dokumen tidak tersedia.']);
        }

        $relativePath = $document->additional_file;

        if (!Storage::disk('public')->exists($relativePath)) {
            \Log::error("File tidak ditemukan saat download: ".$relativePath);
            return response()->view('errors.custom', [
                'message' => 'File tidak ditemukan di server.',
                'path'    => Storage::disk('public')->path($relativePath)
            ], 404);
        }

        $path = Storage::disk('public')->path($relativePath);

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'pdf') {
            return back()->withErrors(['File bukan PDF. Tidak bisa diberikan watermark.']);
        }

        try {
            $pdf = new \App\Services\FPDFAlpha();
            $pageCount = $pdf->setSourceFile($path);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl  = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                $text = '[TIDAK TERKENDALI]';

                // 1. Hitung diagonal & sudut
                $diagonal = sqrt(pow($size['width'], 2) + pow($size['height'], 2));
                $angle    = rad2deg(atan2($size['height'], $size['width']));

                // dd($diagonal);

                // 2. Skala font berdasarkan diagonal
                $fontSize = $diagonal * 0.2; // 5% dari diagonal halaman
                $pdf->SetFont('Helvetica', 'B', $fontSize);
                $pdf->SetTextColor(207, 207, 207);
                $pdf->SetAlpha(0.67);

                // 3. Hitung posisi tengah
                $textWidth  = $pdf->GetStringWidth($text);
                // $textHeight = $fontSize * 0.3528;
                // $x = -(($size['width']  - $textWidth)  / 2);
                $x = ($size['width'] * 0.1);
                $y = ($size['height'] * 0.8);

                // dd($x);
                // 4. Rotasi & tulis
                $rad = deg2rad($angle);
                $cos = cos($rad);
                $sin = sin($rad);

                $pdf->_out('q');
                $pdf->_out(sprintf('%.5F %.5F %.5F %.5F %.5F %.5F cm',
                    $cos, $sin, -$sin, $cos,
                    $size['width']/2 * (1 - $cos) + $size['height']/2 * $sin,
                    $size['height']/2 * (1 - $cos) - $size['width']/2 * $sin
                ));

                $pdf->SetXY($x, $y);
                $pdf->Cell($textWidth, 10, $text, 0, 0, 'C');

                $pdf->_out('Q');
            }

            if (auth()->check()) {
                $alreadyLogged = DocumentDownload::where('document_id', $document->id)
                    ->where('user_id', auth()->id())
                    ->where('downloaded_at', '>=', now()->subSeconds(5)) // cek 5 detik terakhir
                    ->exists();

                if (!$alreadyLogged) {
                    DocumentDownload::create([
                        'document_id'   => $document->id,
                        'user_id'       => auth()->id(),
                        'downloaded_at' => now(),
                    ]);
                }
            }

            // ===== Generate Nama File =====
            $root = $document->parent ?: $document;
            $baseDate = optional($root->created_at)->format('Ymd');

            $ascChain = collect([$root])
                ->merge(($root->relatedVersions ?? collect())->sortBy('created_at')->values());

            $position = $ascChain->search(fn ($d) => $d->id === $document->id);
            $verNo = str_pad(($position !== false ? $position + 1 : 1), 3, '0', STR_PAD_LEFT);

            $cleanNomor = preg_replace('/[^A-Za-z0-9\-]/', '_', $document->nomor_document);
            $cleanNama  = preg_replace('/[^A-Za-z0-9\-]/', '_', $document->nama_document);

            $filename = "{$cleanNomor}_{$cleanNama}_{$baseDate}-{$verNo}.pdf";

            // =================================

            return response($pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\setasign\Fpdi\PdfReader\PdfReaderException $e) {
            \Log::error("FPDI error: " . $e->getMessage());
            return back()->withErrors(['Gagal membuka file PDF.']);
        } catch (\Exception $e) {
            \Log::error("General error: " . $e->getMessage());
            return back()->withErrors(['Terjadi kesalahan saat memproses file.']);
        }
    }

    public function checkNomor(Request $request)
    {
        $nomor = $request->input('nomor_document');

        // check
        $exists = Document::where('nomor_document', $nomor)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists 
                ? 'Nomor dokumen sudah digunakan, silakan pakai nomor lain.'
                : null
        ]);
    }

    function mmToPx($mm, $dpi = 72) {
        return ($mm / 25.4) * $dpi;
    }
}