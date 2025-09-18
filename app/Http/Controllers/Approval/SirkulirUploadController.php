<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\FormReview;     // ganti jika modelmu beda
use App\Models\SirkulirFile;   // model baru di bawah

class SirkulirUploadController extends Controller
{
    public function upload(Request $request, $id)
    {
        $review = FormReview::findOrFail($id);
        $role   = $request->route('role'); // 'officer' | 'manager' | 'avp'

        // upload hanya saat status sirkulir
        if (strtolower($review->status ?? '') !== 'sirkulir') {
            return back()->with('warning', 'Upload sirkulir hanya bisa saat status = Sirkulir.');
        }

        $request->validate([
            'file1' => ['nullable','file','max:20480','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg'],
            'file2' => ['nullable','file','max:20480','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg'],
        ]);

        $dir = "sirkulir/{$review->id}";
        $files = [];

        foreach (['file1','file2'] as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $name = now()->format('Ymd_His').'_'.Str::random(6).'_'.$file->getClientOriginalName();
                $path = $file->storeAs($dir, $name, 'public');

                $files[] = SirkulirFile::create([
                    'form_review_id' => $review->id,
                    'uploaded_by'    => auth()->id(),
                    'uploaded_role'  => $role,
                    'path'           => $path,
                    'original_name'  => $file->getClientOriginalName(),
                    'mime'           => $file->getClientMimeType(),
                    'size'           => $file->getSize(),
                ]);
            }
        }

        if (empty($files)) {
            return back()->with('info', 'Tidak ada file yang dipilih.');
        }

        return back()->with('success', 'File sirkulir berhasil diunggah.');
    }
}
