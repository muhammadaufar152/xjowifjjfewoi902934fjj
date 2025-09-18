<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SirkulirFile;


class FormReview extends Model
{
    use HasFactory;

    protected $table = 'form_reviews';

    protected $fillable = [
        'tanggal_masuk',
        'tanggal_approval',
        'jenis_permohonan',
        'latar_belakang',
        'usulan_revisi',
        'nama_dokumen',
        'no_dokumen',
        'level_dokumen',
        'klasifikasi_siklus',
        'jenis_dokumen',
        'status',
        'perihal',
        'lampiran',
        'bpo_id',
    ];

    /**
     * Relasi ke user BPO (yang input form review)
     */
    public function bpoUser()
    {
        return $this->belongsTo(User::class, 'bpo_id');
    }

    /**
     * Relasi ke semua steps (approval history)
     */
    public function steps()
    {
        return $this->hasMany(ReviewStep::class, 'form_review_id');
    }

    /**
     * Relasi ke step terakhir berdasarkan tanggal terbaru
     */
    public function lastStep()
    {
        return $this->hasOne(ReviewStep::class, 'form_review_id')->latest('tanggal');
    }

    // app/Models/FormReview.php

    public function bpoUploads()
    {
        return $this->hasMany(\App\Models\BpoUploadedFile::class, 'form_review_id');
    }


    public function sirkulirFiles()
    {
        return $this->hasMany(SirkulirFile::class, 'form_review_id')->latest();
    }
        /* ======================== BPO UPLOAD (FINAL) ======================== */
        public function uploadByBpo(Request $request, $id)
        {
            $review = FormReview::findOrFail($id);
    
            // izinkan saat status sudah Sirkulir atau Selesai
            $status = strtolower(trim($review->status ?? ''));
            if (!in_array($status, ['sirkulir', 'selesai'], true)) {
                return back()->with('warning', 'Upload BPO hanya tersedia saat status Sirkulir/Selesai.');
            }
    
            $request->validate([
                'file' => ['required','file','max:20480',
                          'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg']
            ],[
                'file.required' => 'Pilih file untuk diunggah.',
                'file.max'      => 'Maksimum ukuran file 20MB.',
                'file.mimes'    => 'Format file tidak didukung.',
            ]);
    
            $stored = $request->file('file')->store("sirkulir/{$review->id}/bpo", 'public');
    
            SirkulirFile::create([
                'form_review_id' => $review->id,
                'path'           => $stored,
                'original_name'  => $request->file('file')->getClientOriginalName(),
                'uploaded_by'    => auth()->id(),
                'uploaded_role'  => 'bpo',
            ]);
    
            return back()->with('success', 'File BPO berhasil diunggah.');
        }
    

}
