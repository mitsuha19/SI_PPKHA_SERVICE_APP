<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class PengumumanController extends Controller
{
  public function index(Request $request)
  {
    $search = $request->input('search');

    $pengumuman = Pengumuman::when($search, function ($query) use ($search) {
      return $query->where('judul_pengumuman', 'like', "%{$search}%");
    })->orderBy('created_at', 'desc')->paginate(2);

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
    $request->validate([
      'judul_pengumuman' => 'required|string',
      'deskripsi_pengumuman' => 'required|string',
      'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx' // Validasi file
    ]);

    // Simpan lampiran
    $lampiranPaths = [];
    if ($request->hasFile('lampiran')) {
      foreach ($request->file('lampiran') as $file) {
        $path = $file->store('lampiran_pengumuman', 'public'); // Simpan ke storage/app/public/lampiran_pengumuman
        $lampiranPaths[] = $path;
      }
    }

    // Simpan data pengumuman ke database
    $pengumuman = Pengumuman::create([
      'judul_pengumuman' => $request->judul_pengumuman,
      'deskripsi_pengumuman' => $request->deskripsi_pengumuman,
      'lampiran' => count($lampiranPaths) > 0 ? json_encode($lampiranPaths) : null, // Simpan dalam format JSON
    ]);

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
