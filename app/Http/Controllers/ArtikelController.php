<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
// use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class ArtikelController extends Controller
{
    public function showArtikelAdmin(Request $request){

        $search = $request->input('search');

        $artikel = Artikel::when($search, function ($query) use ($search) {
            return $query->where('judul_artikel', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(2);

        
        return view('admin.artikel.artikel', compact('artikel', 'search'));
    }

    public function showArtikelUser(Request $request){
        $search = $request->input('search');

        // Panggil API
        $response = Http::get('http://127.0.0.1:8001/api/artikel', [
            'search' => $search,
        ]);

        if ($response->successful()) {
            $data = collect($response->json()['data'])->map(fn($item) => (object) $item);
    
            // Konversi ke Collection
            $collection = collect($data);
    
            // Buat pagination manual (karena API tidak menyediakan paginate bawaan)
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $artikel = new LengthAwarePaginator(
                $pagedData,
                $collection->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('ppkha.artikel', compact('artikel', 'search'));
        }

        // Jika gagal ambil data
        return back()->withErrors(['error' => 'Gagal mengambil data artikel dari API.']);
    }

    public function showArtikelDetailUser($id) {
        $response = Http::get("http://127.0.0.1:8001/api/artikel/{$id}");

        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Gagal mengambil data dari API.']);
        }
        $artikelData = $response->json()['data'] ?? null;

        if (!$artikelData) {
            return back()->withErrors(['error' => 'Data artikel tidak ditemukan.']);
        }// Konversi gambar ke array of [path, url]
        $gambar = collect(json_decode($artikelData['gambar'] ?? '[]', true))
        ->map(function ($filePath) {
            return [
                'path' => $filePath,
                'url'  => env('BACKEND_FILE_URL') . '/' . ltrim($filePath, '/'),
            ];
        })->toArray();

        $artikel = (object) $artikelData;

        // return view('ppkha.detailArtikel', compact('berita', 'gambar'));

        // // Ambil artikel yang sedang ditampilkan
        // $artikel = Artikel::findOrFail($id);
        
        // Ambil semua artikel sebagai rekomendasi
        $artikelRekomendasi = Artikel::orderBy('created_at', 'desc')->get();
        
        return view('ppkha.detailArtikel', compact('artikel', 'artikelRekomendasi', 'gambar'));
    }

    public function createArtikel(){
        return view('admin.artikel.artikelAdd');
    }

    public function store(Request $request) {
        $validatedData = $request->validate([
            'judul_artikel' => 'required|string',
            'deskripsi_artikel' => 'required|string',
            'sumber_artikel' => 'nullable|url',
            'gambar.*' => 'nullable|file|mimes:jpg,jpeg,png',
        ]);

        $gambarPaths = [];
        if ($request->hasFile('gambar')) {
            foreach ($request->file('gambar') as $file) {
                $gambarPaths[] = $file->store('gambar', 'public');
            }
        }

        Artikel::create([
            'judul_artikel' => $validatedData['judul_artikel'],
            'deskripsi_artikel' => $validatedData['deskripsi_artikel'],
            'sumber_artikel' => $validatedData['sumber_artikel'],
            'gambar' => $gambarPaths,
        ]);

        return redirect()->route('admin.artikel.artikel')->with('success', 'Artikel berhasil ditambahkan!');
    }

    public function showArtikelEditAdmin($id){
        $artikel = Artikel::findOrFail($id);// Ambil data artikel berdasarkan ID
        return view('admin.artikel.artikelEdit', compact('artikel'));
    }

    public function update(Request $request, $id)
{
    $artikel = Artikel::findOrFail($id);

    $request->validate([
        'judul_artikel' => 'required|string|max:255',
        'deskripsi_artikel' => 'required|string',
        'sumber_artikel' => 'nullable|url',
        'gambar.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $artikel->judul_artikel = $request->judul_artikel;
    $artikel->deskripsi_artikel = $request->deskripsi_artikel;
    $artikel->sumber_artikel = $request->sumber_artikel;

    // Laravel sudah cast ke array, jadi tinggal pakai langsung
    $gambarSekarang = $artikel->gambar ?? [];

    // Hapus gambar jika diminta
    if ($request->has('hapus_gambar')) {
        foreach ($request->hapus_gambar as $file) {
            if (in_array($file, $gambarSekarang)) {
                Storage::disk('public')->delete($file);
                $gambarSekarang = array_filter($gambarSekarang, fn($item) => $item !== $file);
            }
        }
    }

    // Tambahkan gambar baru jika ada
    if ($request->hasFile('gambar')) {
        foreach ($request->file('gambar') as $file) {
            $path = $file->store('gambar_pengumuman', 'public');
            $gambarSekarang[] = $path;
        }
    }

    // Simpan array gambar langsung
    $artikel->gambar = array_values($gambarSekarang);
    $artikel->save();

    return redirect()->route('admin.artikel.artikel')->with('success', 'Artikel berhasil diupdate!');
}

    
    public function destroy($id)
  {
      try {
          $artikel = Artikel::findOrFail($id);
          $artikel->delete();
  
          return response()->json(['success' => true]);
      } catch (\Exception $e) {
          return response()->json(['success' => false, 'message' => 'Gagal menghapus berita.'], 500);
      }
  }

  public function show($id){
    $artikel = Artikel::findOrFail($id);
    return view('admin.artikel.artikelDetail', compact('artikel'));
  }
}
