<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
// use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class ArtikelController extends Controller
{
    public function showArtikelAdmin(Request $request){

        $search = $request->input('search');

        // Membuat HTTP request untuk mengambil data artikel dari API
        $response = Http::get(config('services.main_api.url') . '/api/artikel', [
          'search' => $search,
        ]);

        // Jika response tidak berhasil
        if (!$response->successful()) {
          return back()->withErrors('Gagal mengambil data artikel');
        }

        // Ambil data dari response API dan pastikan data dalam bentuk koleksi
        $data = collect($response->json()['data']);  // Mengubah array menjadi koleksi

        // Pagination manual
        $perPage = 2;  // Jumlah per halaman
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $artikel = new LengthAwarePaginator(
          $pagedData,
          $data->count(),
          $perPage,
          $currentPage,
          ['path' => request()->url(), 'query' => request()->query()]
        );
        
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

        // return view('ppkha.detailArtikel', compact('artikel', 'gambar'));

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
      $token = Session::get('api_token');
      if (! $token) {
        return redirect()->route('login')
          ->withErrors('Sesi habis, silakan login ulang');
      }
        $request->validate([
            'judul_artikel' => 'required|string',
            'deskripsi_artikel' => 'required|string',
            'sumber_artikel' => 'nullable|url',
            'gambar.*' => 'nullable|file|mimes:jpg,jpeg,png',
        ]);

        $http = Http::withToken($token);

        if ($request->hasFile('gambar')) {
          foreach ($request->file('gambar') as $file) {
            $http = $http->attach(
              'gambar[]',
              fopen($file->getPathname(), 'r'),
              $file->getClientOriginalName()
            );
          }
        }

        $response = $http->post(
          config('services.main_api.url') . '/api/artikel',
          [
            'judul_artikel'     => $request->judul_artikel,
            'deskripsi_artikel' => $request->deskripsi_artikel,
            'sumber_artikel'     => $request->sumber_artikel,
          ]
        );

        if (! $response->successful()) {
          $err = $response->json('message')
            ?? $response->json('error')
            ?? 'Gagal membuat Artikel';
          return back()->withErrors((array) $err);
        }

        return redirect()->route('admin.artikel.artikel')->with('success', 'Artikel berhasil ditambahkan!');
    }

    // public function showArtikelEditAdmin($id){
    //     $artikel = Artikel::findOrFail($id);// Ambil data artikel berdasarkan ID
    //     return view('admin.artikel.artikelEdit', compact('artikel'));
    // }

    public function showArtikelEditAdmin($id)
  {
    $response = Http::get("http://127.0.0.1:8001/api/artikel/{$id}");

    if (!$response->successful()) {
      return back()->withErrors('Gagal mengambil data Artikel dari API');
    }

    $artikelData = $response->json()['data'] ?? null;

    if (!$artikelData) {
      return back()->withErrors('Artikel tidak ditemukan');
    }

    $artikel = (object) $artikelData;
    return view('admin.artikel.artikelEdit', compact('artikel'));
  }

    public function update(Request $request, $id)
{
    $token = Session::get('api_token');
      if (! $token) {
        return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
      }

    $request->validate([
        'judul_artikel' => 'required|string|max:255',
        'deskripsi_artikel' => 'required|string',
        'sumber_artikel' => 'nullable|url',
        'gambar.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $http = Http::withToken($token);

    // Hapus gambar jika diminta
    if ($request->hasFile('gambar')) {
      foreach ($request->file('gambar') as $file) {
        $http = $http->attach(
          'gambar[]',
          fopen($file->getPathname(), 'r'),
          $file->getClientOriginalName()
        );
      }
    }

    $response = $http->post(
      config('services.main_api.url') . "/api/artikel/{$id}",
      [
          '_method'          => 'PUT', // ini penting
          'judul_artikel'     => $request->judul_artikel,
          'deskripsi_artikel' => $request->deskripsi_artikel,
          'sumber_artikel' => $request->sumber_artikel,
      ]
    );

    if (! $response->successful()) {
      $err = $response->json('message')
        ?? $response->json('error')
        ?? 'Gagal memperbarui Artikel';
      return back()->withErrors((array) $err);
    }

    return redirect()->route('admin.artikel.artikel')->with('success', 'Artikel berhasil diupdate!');
}

    
    public function destroy($id)
  {
      try {
          $artikel = Artikel::findOrFail($id);
          $artikel->delete();
  
          return response()->json(['success' => true]);
      } catch (\Exception $e) {
          return response()->json(['success' => false, 'message' => 'Gagal menghapus Artikel.'], 500);
      }
  }

  public function show($id){
    $response = Http::get("http://127.0.0.1:8001/api/artikel/{$id}");

    if (!$response->successful()) {
      return back()->withErrors(['error' => 'Gagal mengambil data dari API.']);
    }

    $artikelData = $response->json()['data'] ?? null;

    if (!$artikelData) {
      return back()->withErrors(['error' => 'Data artikel tidak ditemukan.']);
    } // Konversi gambar ke array of [path, url]
    $gambar = collect(json_decode($artikelData['gambar'] ?? '[]', true))
      ->map(function ($filePath) {
        return [
          'path' => $filePath,
          'url'  => env('BACKEND_FILE_URL') . '/' . ltrim($filePath, '/'),
        ];
      })
      ->toArray();


    $artikel = (object) $artikelData;

    return view('admin.artikel.artikelDetail', compact('artikel', 'gambar'));
  }
}
