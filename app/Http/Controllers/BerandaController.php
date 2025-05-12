<?php

namespace App\Http\Controllers;

use App\Models\Beranda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;


class BerandaController extends Controller
{
    public function index()
    {

        $mainApi = config('services.main_api.url');

        // Ambil 3 pengumuman terbaru
        $pengumuman = collect();
        $pengumumanResponse = Http::get("$mainApi/api/pengumuman");
        if ($pengumumanResponse->successful()) {
            $pengumuman = collect($pengumumanResponse->json()['data'])
                ->map(fn($item) => (object) $item)
                ->sortByDesc('created_at')
                ->take(3);
        }

        // Ambil 3 artikel terbaru
        $artikel = collect();
        $artikelResponse = Http::get("$mainApi/api/artikel");
        if ($artikelResponse->successful()) {
            $artikel = collect($artikelResponse->json()['data'])
                ->map(fn($item) => (object) $item)
                ->sortByDesc('created_at')
                ->take(3);
        }

        // Ambil 3 berita terbaru
        $berita = collect();
        $beritaResponse = Http::get("$mainApi/api/berita");
        if ($beritaResponse->successful()) {
            $berita = collect($beritaResponse->json()['data'])
                ->map(fn($item) => (object) $item)
                ->sortByDesc('created_at')
                ->take(3);
        }

        $beranda = null;
        $berandaResponse = Http::get("$mainApi/api/beranda");
        if ($berandaResponse->successful() && $berandaResponse->json('success')) {
            $beranda = (object) $berandaResponse->json('data');
        }

        // Kirim data ke view
        return view('ppkha.beranda', compact('pengumuman', 'artikel', 'berita', 'beranda'));
    }

    public function showBerandaAdmin()
    {
        $mainApi = config('services.main_api.url');
        $beranda = null;
        $berandaResponse = Http::get("$mainApi/api/beranda");
        if ($berandaResponse->successful() && $berandaResponse->json('success')) {
            $beranda = (object) $berandaResponse->json('data');
        }
        return view('admin.berandaEdit', compact('beranda'));
    }

    public function update(Request $request)
    {
        $token = Session::get('api_token');
        if (! $token) {
            return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
        }
        $mainApi = config('services.main_api.url');
        $request->validate([
            'deskripsi_beranda' => 'required|string',
        ]);
        $http = Http::withToken($token);

        // Kirim permintaan update ke API
        $response = Http::withToken($token)->put("$mainApi/api/beranda", [
            'deskripsi_beranda' => $request->deskripsi_beranda,
        ]);

        if (!$response->successful()) {
            Log::error('Update Beranda API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return redirect()->back()->with('error', 'Gagal memperbarui deskripsi beranda. (' . $response->status() . ')');
        }


        if ($response->successful() && $response->json('success')) {
            return redirect()->back()->with('success', 'Deskripsi beranda berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal memperbarui deskripsi beranda.');
    }
}
