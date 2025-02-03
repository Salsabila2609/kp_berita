<?php
// app/Http/Controllers/NewsController.php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use HTMLPurifier;
use HTMLPurifier_Config;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::all()->map(function ($item) {
            if (!is_array($item->kategori)) {
                $decodedKategori = json_decode($item->kategori, true);
                if (is_string($decodedKategori)) {
                    $decodedKategori = json_decode($decodedKategori, true);
                }
                $item->kategori = $decodedKategori;
            }
            $item->tanggal_terbit = $item->created_at->format('d M Y'); // Format tanggal
            $item->gambar_lampiran = is_string($item->gambar_lampiran)
                ? json_decode($item->gambar_lampiran, true)
                : $item->gambar_lampiran;
    
            return $item;
        });
    
        // Hitung jumlah berita per kategori
        $categoriesWithCount = News::all()
            ->flatMap(function ($news) {
                $kategori = json_decode($news->kategori, true);
                return is_array($kategori) ? $kategori : [];
            })
            ->countBy()
            ->map(function ($count, $category) {
                return [
                    'category' => $category,
                    'count' => $count,
                ];
            })
            ->values();
    
        // Tambahkan jumlah total berita
        $totalNewsCount = News::count();
    
        return inertia('News/Index', [
            'newsItems' => $news,
            'categoriesWithCount' => $categoriesWithCount,
            'totalNewsCount' => $totalNewsCount,
        ]);
    }    

    public function showByCategory($kategori)
    {
        // Cek apakah pengguna sudah login
        if (auth()->check()) {
            // Decode kategori jika diperlukan
            $kategori = urldecode($kategori); // Decode URL-encoded string
    
            // Debug: Log kategori yang diterima
            \Log::info('Kategori yang diterima:', ['kategori' => $kategori]);
    
            // Ambil berita berdasarkan kategori
            $news = News::whereJsonContains('kategori', $kategori)->get()->map(function ($item) {
                // Proses kategori jika diperlukan (misalnya kategori disimpan dalam format JSON)
                if (!is_array($item->kategori)) {
                    $decodedKategori = json_decode($item->kategori, true);
                    if (is_string($decodedKategori)) {
                        $decodedKategori = json_decode($decodedKategori, true);
                    }
                    $item->kategori = $decodedKategori;
                }
    
                // Format tanggal
                $item->tanggal_terbit = $item->created_at->format('d M Y');
                // Periksa dan decode gambar_lampiran jika ada
                $item->gambar_lampiran = is_string($item->gambar_lampiran)
                    ? json_decode($item->gambar_lampiran, true)
                    : $item->gambar_lampiran;
    
                return $item;
            });
    
            // Debug: Log berita yang ditemukan
            \Log::info('Berita yang ditemukan:', ['news' => $news]);
    
            // Hitung jumlah berita per kategori
            $categoriesWithCount = News::all()
                ->flatMap(function ($news) {
                    $kategori = json_decode($news->kategori, true);
                    return is_array($kategori) ? $kategori : [];
                })
                ->countBy()
                ->map(function ($count, $category) {
                    return [
                        'category' => $category,
                        'count' => $count,
                    ];
                })
                ->values();
    
            // Tambahkan jumlah total berita
            $totalNewsCount = News::count();
    
            return inertia('News/Index', [
                'newsItems' => $news,
                'categoriesWithCount' => $categoriesWithCount,
                'totalNewsCount' => $totalNewsCount,
                'activeCategory' => $kategori, // Tambahkan kategori aktif
            ]);
        } else {
            // Jika pengguna tidak login, redirect ke halaman login
            return redirect()->route('login');
        }
    }    

    public function create()
    {
        return Inertia::render('News/Create');
    }

    public function store(Request $request)
    {

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        
        // Mengambil dan menyaring konten berita
        $cleanContent = $purifier->purify($request->input('isi_berita'));

        // Validasi input form
        $request->validate([
            'penulis' => 'required|string',
            'judul' => 'required|string',
            'isi_berita' => 'required',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gambar_lampiran.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'gambar_utama_keterangan' => 'nullable|string|max:255',  // Validasi untuk keterangan gambar utama
            'gambar_lampiran_keterangan.*' => 'nullable|string|max:255',  // Validasi untuk keterangan gambar lampiran
        ]);
    
        // Validasi jumlah gambar yang di-upload
        if (count($request->file('gambar_lampiran')) > 5) { 
            return back()->withErrors(['gambar_lampiran' => 'Anda hanya dapat meng-upload maksimal 5 gambar']);
        }
    
        // Mengubah kategori menjadi array jika belum
        $kategori = is_array($request->kategori) ? $request->kategori : json_decode($request->kategori, true);
    
        // Membuat instance berita baru
        $news = new News();
        $news->penulis = $request->penulis;
        $news->judul = $request->judul;
        $news->isi_berita = $request->isi_berita;
        $news->kategori = json_encode($kategori);
    
        // Proses upload gambar utama
        if ($request->hasFile('gambar_utama') && $request->file('gambar_utama')->isValid()) {
            $news->gambar_utama = $request->file('gambar_utama')->store('images');
        }
    
        // Menyimpan keterangan gambar utama
        $news->gambar_utama_keterangan = $request->gambar_utama_keterangan;
    
        // Proses upload multiple gambar lampiran
        $gambarLampiranPaths = [];
        $gambarLampiranKeterangan = [];  // Tambahkan ini untuk keterangan gambar lampiran
        if ($request->hasFile('gambar_lampiran')) {
            foreach ($request->file('gambar_lampiran') as $index => $lampiran) {
                if ($lampiran->isValid()) {
                    $path = $lampiran->store('images');
                    $gambarLampiranPaths[] = $path;
    
                    // Menyimpan keterangan untuk setiap gambar lampiran (jika ada)
                    $gambarLampiranKeterangan[] = $request->input('gambar_lampiran_keterangan.' . $index);
                }
            }
        }
    
        // Menyimpan gambar lampiran dan keterangan ke dalam database
        $news->gambar_lampiran = json_encode($gambarLampiranPaths);
        $news->gambar_lampiran_keterangan = json_encode($gambarLampiranKeterangan);
    
        // Simpan berita ke database
        $news->save();
    
        return redirect()->route('news.index')->with('success', 'News created successfully!');
    }
    

    public function show($id)
    {
        $news = News::findOrFail($id);
    
        // Decode kategori berita utama
        $decodedKategori = json_decode($news->kategori, true);
        if (is_string($decodedKategori)) {
            $decodedKategori = json_decode($decodedKategori, true);
        }
        $news->kategori = $decodedKategori;
        $news->tanggal_terbit = $news->created_at->format('d M Y');
    
        // Decode gambar lampiran dan keterangan gambar lampiran menjadi array
        if (is_string($news->gambar_lampiran)) {
            $news->gambar_lampiran = json_decode($news->gambar_lampiran, true);
        }
        if (is_string($news->gambar_lampiran_keterangan)) {
            $news->gambar_lampiran_keterangan = json_decode($news->gambar_lampiran_keterangan, true);
        }
    
        // Ambil berita terkait berdasarkan kategori
        $relatedNews = News::where(function ($query) use ($decodedKategori) {
                if (!empty($decodedKategori)) {
                    $query->where(function ($q) use ($decodedKategori) {
                        foreach ($decodedKategori as $kategori) {
                            $q->orWhere('kategori', 'like', '%' . $kategori . '%');
                        }
                    });
                }
            })
            ->where('id', '!=', $id) // Jangan tampilkan berita yang sedang dibuka
            ->limit(5) // Batasi jumlah berita terkait
            ->get()
            ->map(function ($related) {
                $related->kategori = json_decode($related->kategori, true);
                $related->tanggal_terbit = $related->created_at->format('d M Y');
                $related->gambar_lampiran = is_string($related->gambar_lampiran) 
                    ? json_decode($related->gambar_lampiran, true) 
                    : $related->gambar_lampiran;
                return $related;
            });
    
        // Hitung jumlah berita per kategori
        $uniqueCategoriesWithCount = News::all()
            ->flatMap(function ($news) {
                $kategori = json_decode($news->kategori, true);
                return is_array($kategori) ? $kategori : [];
            })
            ->countBy() // Hitung jumlah berita per kategori
            ->map(function ($count, $category) {
                return [
                    'category' => $category,
                    'count' => $count,
                ];
            })
            ->values();
    
        // Kirim data ke frontend
        return Inertia::render('News/Show', [
            'news' => $news,
            'related' => $relatedNews,
            'categoriesWithCount' => $uniqueCategoriesWithCount,
        ]);
    }
    
    public function edit($id)
    {
        $news = News::findOrFail($id);
        
        // Decode kategori if needed
        if (!is_array($news->kategori)) {
            $decodedKategori = json_decode($news->kategori, true);
            if (is_string($decodedKategori)) {
                $decodedKategori = json_decode($decodedKategori, true);
            }
            $news->kategori = $decodedKategori;
        }
        
        // Decode gambar lampiran and keterangan if they're stored as JSON strings
        if (is_string($news->gambar_lampiran)) {
            $news->gambar_lampiran = json_decode($news->gambar_lampiran, true) ?? [];
        }
        if (is_string($news->gambar_lampiran_keterangan)) {
            $news->gambar_lampiran_keterangan = json_decode($news->gambar_lampiran_keterangan, true) ?? [];
        }
    
        return Inertia::render('News/Edit', [
            'news' => $news
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        
        // Purify the news content
        $cleanContent = $purifier->purify($request->input('isi_berita'));
    
        // Validate input
        $request->validate([
            'penulis' => 'required|string',
            'judul' => 'required|string',
            'isi_berita' => 'required',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'gambar_lampiran.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'gambar_utama_keterangan' => 'nullable|string|max:255',
            'gambar_lampiran_keterangan.*' => 'nullable|string|max:255',
        ]);
    
        $news = News::findOrFail($id);
    
        // Update basic fields
        $news->penulis = $request->penulis;
        $news->judul = $request->judul;
        $news->isi_berita = $request->isi_berita;
        
        // Handle kategori
        $kategori = is_array($request->kategori) ? $request->kategori : json_decode($request->kategori, true);
        $news->kategori = json_encode($kategori);
    
        // Handle main image update if new one is uploaded
        if ($request->hasFile('gambar_utama') && $request->file('gambar_utama')->isValid()) {
            // Delete old image if exists
            if ($news->gambar_utama) {
                Storage::delete($news->gambar_utama);
            }
            $news->gambar_utama = $request->file('gambar_utama')->store('images');
        }
        
        // Update main image caption
        $news->gambar_utama_keterangan = $request->gambar_utama_keterangan;
    
        // Handle attachment images
        $existingAttachments = is_string($news->gambar_lampiran) 
            ? json_decode($news->gambar_lampiran, true) ?? []
            : $news->gambar_lampiran ?? [];
        
        $existingCaptions = is_string($news->gambar_lampiran_keterangan)
            ? json_decode($news->gambar_lampiran_keterangan, true) ?? []
            : $news->gambar_lampiran_keterangan ?? [];
    
        // Handle deleted attachments
        $keptAttachments = $request->input('kept_attachments', []);
        foreach ($existingAttachments as $index => $path) {
            if (!in_array($path, $keptAttachments)) {
                Storage::delete($path);
                unset($existingAttachments[$index]);
                unset($existingCaptions[$index]);
            }
        }
    
        // Add new attachments
        if ($request->hasFile('gambar_lampiran')) {
            foreach ($request->file('gambar_lampiran') as $index => $lampiran) {
                if ($lampiran->isValid()) {
                    $path = $lampiran->store('images');
                    $existingAttachments[] = $path;
                    $existingCaptions[] = $request->input('gambar_lampiran_keterangan.' . $index);
                }
            }
        }
    
        // Update the arrays in the database
        $news->gambar_lampiran = json_encode(array_values($existingAttachments));
        $news->gambar_lampiran_keterangan = json_encode(array_values($existingCaptions));
    
        $news->save();
    
        return redirect()->route('news.show', $news->id)->with('success', 'News updated successfully!');
    }

    public function destroy($id)
    {
        try {
            // Find the news
            $news = News::findOrFail($id);

            // Delete the main image if exists
            if ($news->gambar_utama) {
                Storage::delete($news->gambar_utama);
            }

            // Delete attachment images if exist
            if ($news->gambar_lampiran) {
                $attachments = json_decode($news->gambar_lampiran, true);
                foreach ($attachments as $attachment) {
                    Storage::delete($attachment);
                }
            }

            // Delete the news record
            $news->delete();

            return redirect()->route('news.index')->with('success', 'News deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting news: ' . $e->getMessage());
        }
    }
}
