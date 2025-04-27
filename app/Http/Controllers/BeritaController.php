<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class BeritaController extends Controller
{
  public function index2(Request $request)
  {
    $search = $request->input('search');

    // Membuat HTTP request untuk mengambil data berita dari API
    $response = Http::get(config('services.main_api.url') . '/api/berita', [
      'search' => $search,
    ]);

    // Jika response tidak berhasil
    if (!$response->successful()) {
      return back()->withErrors('Gagal mengambil data berita');
    }

    // Ambil data dari response API dan pastikan data dalam bentuk koleksi
    $data = collect($response->json()['data']);  // Mengubah array menjadi koleksi

    // Pagination manual
    $perPage = 2;  // Jumlah per halaman
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $berita = new LengthAwarePaginator(
      $pagedData,
      $data->count(),
      $perPage,
      $currentPage,
      ['path' => request()->url(), 'query' => request()->query()]
    );
    return view('admin.berita.berita', compact('berita', 'search'));
  }

  public function showBeritaUser(Request $request)
  {
    $search = $request->input('search');

    // Panggil API
    $response = Http::get('http://127.0.0.1:8001/api/berita', [
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
      $berita = new LengthAwarePaginator(
        $pagedData,
        $collection->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
      );
      dd(session()->all());
      return view('ppkha.berita', compact('berita', 'search'));
    }

    // Jika gagal ambil data
    return back()->withErrors(['error' => 'Gagal mengambil data berita dari API.']);
  }

  public function showBeritaDetailUser($id)
  {
    $response = Http::get("http://127.0.0.1:8001/api/berita/{$id}");

    if (!$response->successful()) {
      return back()->withErrors(['error' => 'Gagal mengambil data dari API.']);
    }

    $beritaData = $response->json()['data'] ?? null;

    if (!$beritaData) {
      return back()->withErrors(['error' => 'Data berita tidak ditemukan.']);
    } // Konversi gambar ke array of [path, url]
    $gambar = collect(json_decode($beritaData['gambar'] ?? '[]', true))
      ->map(function ($filePath) {
        return [
          'path' => $filePath,
          'url'  => env('BACKEND_FILE_URL') . '/' . ltrim($filePath, '/'),
        ];
      })
      ->toArray();


    $berita = (object) $beritaData;

    return view('ppkha.detailBerita', compact('berita', 'gambar'));
  }

  public function index3()
  {
    return view('admin.berita.beritaAdd');
  }

  public function index4($id)
  {
    $berita = Berita::findOrFail($id); // Ambil data berita berdasarkan ID
    return view('admin.berita.beritaEdit', compact('berita'));
  }

  public function show1($id)
  {
    $response = Http::get("http://127.0.0.1:8001/api/berita/{$id}");

    if (!$response->successful()) {
      return back()->withErrors(['error' => 'Gagal mengambil data dari API.']);
    }

    $beritaData = $response->json()['data'] ?? null;

    if (!$beritaData) {
      return back()->withErrors(['error' => 'Data berita tidak ditemukan.']);
    } // Konversi gambar ke array of [path, url]
    $gambar = collect(json_decode($beritaData['gambar'] ?? '[]', true))
      ->map(function ($filePath) {
        return [
          'path' => $filePath,
          'url'  => env('BACKEND_FILE_URL') . '/' . ltrim($filePath, '/'),
        ];
      })
      ->toArray();


    $berita = (object) $beritaData;

    return view('ppkha.detailBerita', compact('berita', 'gambar'));
  }

  public function store(Request $request)
  {
    $token = Session::get('api_token');
    if (! $token) {
      return redirect()->route('login')
        ->withErrors('Sesi habis, silakan login ulang');
    }

    $request->validate([
      'judul_berita' => 'required|string',
      'deskripsi_berita' => 'required|string',
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
      config('services.main_api.url') . '/api/berita',
      [
        'judul_berita'     => $request->judul_berita,
        'deskripsi_berita' => $request->deskripsi_berita,
      ]
    );

    if (! $response->successful()) {
      $err = $response->json('message')
        ?? $response->json('error')
        ?? 'Gagal membuat Berita';
      return back()->withErrors((array) $err);
    }

    return redirect()->route('admin.berita.berita')->with('success', 'Berita berhasil ditambahkan!');
  }

  public function update(Request $request, $id)
  {
    $validatedData = $request->validate([
      'judul_berita' => 'required|string',
      'deskripsi_berita' => 'required|string',
      'gambar.*' => 'nullable|file|mimes:jpg,jpeg,png,gif',
    ]);

    $berita = Berita::findOrFail($id);

    // Hapus gambar lama jika dicentang
    if ($request->has('hapus_gambar')) {
      foreach ($request->hapus_gambar as $file) {
        if (Storage::disk('public')->exists($file)) {
          Storage::disk('public')->delete($file);
        }
      }

      // Filter gambar yang tidak dihapus
      $berita->gambar = array_diff($berita->gambar ?? [], $request->hapus_gambar);
    }

    // Tambah gambar baru
    $gambarPaths = $berita->gambar ?? [];
    if ($request->hasFile('gambar')) {
      foreach ($request->file('gambar') as $file) {
        $gambarPaths[] = $file->store('gambar', 'public');
      }
    }

    // Update berita
    $berita->update([
      'judul_berita' => $validatedData['judul_berita'],
      'deskripsi_berita' => $validatedData['deskripsi_berita'],
      'gambar' => $gambarPaths,
    ]);

    return redirect()->route('admin.berita.berita')->with('success', 'Berita berhasil diupdate!');
  }

  public function destroy($id)
  {
    try {
      $berita = Berita::findOrFail($id);
      $berita->delete();

      return response()->json(['success' => true]);
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => 'Gagal menghapus berita.'], 500);
    }
  }
}
