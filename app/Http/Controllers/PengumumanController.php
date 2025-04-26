<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class PengumumanController extends Controller
{
  public function index(Request $request)
  {
    $search = $request->input('search');

    // Membuat HTTP request untuk mengambil data pengumuman dari API
    $response = Http::get(config('services.main_api.url') . '/api/pengumuman', [
      'search' => $search,
    ]);

    // Jika response tidak berhasil
    if (!$response->successful()) {
      return back()->withErrors('Gagal mengambil data pengumuman');
    }

    // Ambil data dari response API dan pastikan data dalam bentuk koleksi
    $data = collect($response->json()['data']);  // Mengubah array menjadi koleksi

    // Pagination manual
    $perPage = 2;  // Jumlah per halaman
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $pengumuman = new LengthAwarePaginator(
      $pagedData,
      $data->count(),
      $perPage,
      $currentPage,
      ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('admin.pengumuman.pengumuman', compact('pengumuman', 'search'));
  }

  public function showPengumumanUser(Request $request)
  {
    $search = $request->input('search');

    // Panggil API
    $response = Http::get('http://127.0.0.1:8001/api/pengumuman', [
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
      $pengumuman = new LengthAwarePaginator(
        $pagedData,
        $collection->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
      );

      return view('ppkha.pengumuman', compact('pengumuman', 'search'));
    }

    // Jika gagal ambil data
    return back()->withErrors(['error' => 'Gagal mengambil data pengumuman dari API.']);
  }

  public function showPengumumanDetailUser($id)
  {
    $response = Http::get("http://127.0.0.1:8001/api/pengumuman/{$id}");

    if (!$response->successful()) {
      return back()->withErrors(['error' => 'Gagal mengambil data dari API.']);
    }

    $pengumumanData = $response->json()['data'] ?? null;

    if (!$pengumumanData) {
      return back()->withErrors(['error' => 'Data pengumuman tidak ditemukan.']);
    }

    // Konversi lampiran ke array of [path, url]
    $lampiran = collect(json_decode($pengumumanData['lampiran'] ?? '[]', true))
      ->map(function ($filePath) {
        return [
          'path' => $filePath,
          'url'  => env('BACKEND_FILE_URL') . '/' . ltrim($filePath, '/'),
        ];
      })
      ->toArray();


    $pengumuman = (object) $pengumumanData;

    return view('ppkha.detailPengumuman', compact('pengumuman', 'lampiran'));
  }



  public function create()
  {
    return view('admin.pengumuman.pengumumanAdd');
  }

  public function show($id)
  {
    $pengumuman = Pengumuman::findOrFail($id);
    return view('admin.pengumuman.pengumumanDetail', compact('pengumuman'));
  }


  public function store(Request $request)
  {
    $token = Session::get('api_token');
    if (! $token) {
      return redirect()->route('login')
        ->withErrors('Sesi habis, silakan login ulang');
    }
    $request->validate([
      'judul_pengumuman' => 'required|string',
      'deskripsi_pengumuman' => 'required|string',
      'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx' // Validasi file
    ]);

    $http = Http::withToken($token);

    // 4. Lampiran sebagai multipart/form-data
    if ($request->hasFile('lampiran')) {
      foreach ($request->file('lampiran') as $file) {
        $http = $http->attach(
          'lampiran[]',
          fopen($file->getPathname(), 'r'),
          $file->getClientOriginalName()
        );
      }
    }

    // 5. Kirim request ke Service Main
    $response = $http->post(
      config('services.main_api.url') . '/api/pengumuman',
      [
        'judul_pengumuman'     => $request->judul_pengumuman,
        'deskripsi_pengumuman' => $request->deskripsi_pengumuman,
      ]
    );

    if (! $response->successful()) {
      $err = $response->json('message')
        ?? $response->json('error')
        ?? 'Gagal membuat pengumuman';
      return back()->withErrors((array) $err);
    }
    return redirect()->route('admin.pengumuman.index')->with('success', 'Pengumuman berhasil ditambahkan!');
  }

  public function edit($id)
  {
    $pengumuman = Pengumuman::findOrFail($id);
    return view('admin.pengumuman.pengumumanEdit', compact('pengumuman'));
  }

  public function destroy($id)
  {
    try {
      $pengumuman = Pengumuman::findOrFail($id);
      $lampiranPaths = json_decode($pengumuman->lampiran ?? '[]', true);

      foreach ($lampiranPaths as $file) {
        // Tangani path string dan object dari API
        $filePath = null;
        if (is_array($file)) {
          // Jika dari API, ambil 'nama_file' dan pastikan string
          $filePath = isset($file['nama_file']) && is_string($file['nama_file']) ? $file['nama_file'] : null;
        } else {
          // Jika dari web, gunakan langsung jika string
          $filePath = is_string($file) ? $file : null;
        }

        // Lewati jika $filePath bukan string atau null
        if (!$filePath) {
          continue;
        }

        // Hapus file jika ada
        if (Storage::disk('public')->exists($filePath)) {
          Storage::disk('public')->delete($filePath);
        }
      }

      $pengumuman->delete();
      return response()->json(['success' => true]);
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => 'Gagal menghapus pengumuman: ' . $e->getMessage()], 500);
    }
  }
}
