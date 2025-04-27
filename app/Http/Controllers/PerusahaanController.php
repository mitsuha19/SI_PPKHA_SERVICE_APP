<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class PerusahaanController extends Controller
{
    // Menampilkan semua perusahaan (Admin) - Konsumsi API
    public function index2(Request $request)
    {
        $search = $request->input('search');

        $response = Http::get(config('services.main_api.url') . '/api/perusahaan', [
            'search' => $search,
        ]);

        if (!$response->successful()) {
            return back()->withErrors('Gagal mengambil data perusahaan');
        }

        $data = collect($response->json()['data']);

        $perPage = 2;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $perusahaan = new LengthAwarePaginator(
            $pagedData,
            $data->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.daftarPerusahaan.daftarPerusahaan', compact('perusahaan', 'search'));
    }

    // Menampilkan semua perusahaan (User) - Konsumsi API
    public function showPerusahaanUser(Request $request)
    {
        $search = $request->input('search');

        $response = Http::get(config('services.main_api.url') . '/api/perusahaan', [
            'search' => $search,
        ]);

        if (!$response->successful()) {
            return back()->withErrors('Gagal mengambil data perusahaan');
        }

        $data = collect($response->json()['data'])->map(fn($item) => (object) $item);

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $perusahaan = new LengthAwarePaginator(
            $pagedData,
            $data->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('ppkha.daftar_perusahaan', compact('perusahaan', 'search'));
    }

    // Menampilkan detail perusahaan untuk User - Konsumsi API
    public function showPerusahaanDetailUser($id)
    {
        $response = Http::get(config('services.main_api.url') . "/api/perusahaan/{$id}");

        if (!$response->successful()) {
            return back()->withErrors('Gagal mengambil data perusahaan');
        }

        $perusahaanData = $response->json()['data'] ?? null;

        if (!$perusahaanData) {
            return back()->withErrors('Perusahaan tidak ditemukan');
        }

        $perusahaan = (object) $perusahaanData;

        // Asumsikan lowongan dari API juga? Kalau belum ada, bisa kosong
        $lowongan = collect($perusahaan->lowongan ?? []);

        return view('ppkha.detailperusahaan', compact('perusahaan', 'lowongan'));
    }

    // Menampilkan detail perusahaan (Admin) - Konsumsi API
    public function show1($id)
    {
        $response = Http::get(config('services.main_api.url') . "/api/perusahaan/{$id}");

        if (!$response->successful()) {
            return back()->withErrors('Gagal mengambil data perusahaan');
        }

        $perusahaanData = $response->json()['data'] ?? null;

        if (!$perusahaanData) {
            return back()->withErrors('Perusahaan tidak ditemukan');
        }

        $perusahaan = (object) $perusahaanData;

        $lowongan = collect($perusahaan->lowongan ?? []);

        return view('admin.daftarPerusahaan.daftarPerusahaanDetail', compact('perusahaan', 'lowongan'));
    }

    // Menampilkan detail perusahaan dalam format JSON (khusus API konsumsi langsung)
    public function show($id)
    {
        $response = Http::get(config('services.main_api.url') . "/api/perusahaan/{$id}");

        if (!$response->successful()) {
            return response()->json(['message' => 'Perusahaan tidak ditemukan'], 404);
        }

        return response()->json($response->json()['data']);
    }
}
