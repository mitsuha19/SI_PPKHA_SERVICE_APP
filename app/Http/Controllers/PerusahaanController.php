<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;



class PerusahaanController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => config('services.main_api.url')]);
    }
    // Menampilkan semua perusahaan (Admin) - Konsumsi API
    public function index2(Request $request)
    {
        $search = $request->input('search');
        $token = Session::get('api_token');

        if (!$token) {
            return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
        }

        try {
            $response = $this->client->get('/api/perusahaan', [
                'query' => ['search' => $search],
            ]);

            $data = collect(json_decode($response->getBody()->getContents(), true)['data'])
                ->map(function ($item) {
                    return (object) $item;
                });

            Log::info('Perusahaan Data', $data->toArray());

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

            return view('admin.daftarPerusahaan.daftarPerusahaan', compact('perusahaan', 'search', 'token'));
        } catch (\Exception $e) {
            Log::error('Index2 Error: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil data perusahaan: ' . $e->getMessage());
        }
    }

    public function editView($id)
    {
        $token = Session::get('api_token');

        if (!$token) {
            return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
        }

        try {
            $response = $this->client->get("/api/perusahaan/{$id}", [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Raw API Response for Perusahaan: ', $responseData);

            if (!$responseData['success'] || !$responseData['data']) {
                return back()->withErrors('Data perusahaan tidak ditemukan');
            }

            $perusahaan = (object) $responseData['data'];

            Log::info('Processed Perusahaan Data: ', (array) $perusahaan);

            return view('admin.daftarPerusahaan.daftarPerusahaanEdit', compact('perusahaan'));
        } catch (\Exception $e) {
            Log::error('Edit Error: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil data perusahaan: ' . $e->getMessage());
        }
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

    public function update(Request $request, $id)
    {
        try {
            $token = Session::get('api_token');
            if (!$token) {
                return redirect()->route('login')->withErrors('Gagal memperbarui perusahaan: Token autentikasi tidak ditemukan.');
            }

            $validatedData = $request->validate([
                'namaPerusahaan' => 'nullable|string|max:255',
                'lokasiPerusahaan' => 'nullable|string|max:255',
                'websitePerusahaan' => 'nullable|url',
                'industriPerusahaan' => 'nullable|string|max:255',
                'deskripsiPerusahaan' => 'nullable|string',
                'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            $data = array_filter([
                'namaPerusahaan' => $validatedData['namaPerusahaan'] ?? null,
                'lokasiPerusahaan' => $validatedData['lokasiPerusahaan'] ?? null,
                'websitePerusahaan' => $validatedData['websitePerusahaan'] ?? null,
                'industriPerusahaan' => $validatedData['industriPerusahaan'] ?? null,
                'deskripsiPerusahaan' => $validatedData['deskripsiPerusahaan'] ?? null,
            ], fn($value) => !is_null($value) && $value !== '');

            $options = [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ],
                'multipart' => [
                    [
                        'name' => '_method',
                        'contents' => 'PUT',
                    ],
                ],
            ];

            foreach ($data as $key => $value) {
                $options['multipart'][] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $options['multipart'][] = [
                    'name' => 'logo',
                    'contents' => fopen($logo->getRealPath(), 'r'),
                    'filename' => $logo->getClientOriginalName(),
                    'headers' => ['Content-Type' => $logo->getMimeType()],
                ];
            }

            $response = $this->client->post("/api/perusahaan/{$id}", $options);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['success']) && $responseData['success']) {
                return redirect()->route('admin.daftarPerusahaan.daftarPerusahaan')
                    ->with('success', 'Perusahaan berhasil diperbarui.');
            }

            $errorMessage = $responseData['message'] ?? 'Kesalahan tidak diketahui.';
            if (isset($responseData['errors'])) {
                $errorMessage = implode(', ', array_map('current', $responseData['errors']));
            }
            return redirect()->back()->withErrors('Gagal memperbarui perusahaan: ' . $errorMessage);
        } catch (RequestException $e) {
            $errorMessage = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)['message'] ?? $e->getMessage()
                : $e->getMessage();
            Log::error('Update Perusahaan Request Error: ' . $errorMessage);
            return redirect()->back()->withErrors('Gagal memperbarui perusahaan: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('Update Perusahaan Error: ' . $e->getMessage());
            return redirect()->back()->withErrors('Gagal memperbarui perusahaan: ' . $e->getMessage());
        }
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

        $lowongan = collect($perusahaan->lowongan ?? [])->map(function ($item) {
            return (object) $item;
        });

        return view('ppkha.detailperusahaan', compact('perusahaan', 'lowongan'));
    }

    // Menampilkan detail perusahaan (Admin) - Konsumsi API
    public function show1($id)
    {
        $token = Session::get('api_token');

        if (!$token) {
            return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
        }

        try {
            $response = $this->client->get("/api/perusahaan/{$id}", [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $data = isset($responseData['data']) ? $responseData['data'] : null;

            if (!$data) {
                return back()->withErrors('Data perusahaan tidak ditemukan');
            }

            $perusahaan = (object) $data;
            $lowongan = isset($data['lowongan']) ? collect($data['lowongan'])->map(fn($item) => (object) $item) : collect();

            Log::info('Perusahaan Detail Data with Lowongan', [
                'perusahaan' => (array) $perusahaan,
                'lowongan' => $lowongan->toArray(),
            ]);

            return view('admin.daftarPerusahaan.daftarPerusahaanDetail', compact('perusahaan', 'lowongan'));
        } catch (\Exception $e) {
            Log::error('Show1 Error: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil detail perusahaan: ' . $e->getMessage());
        }
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

    public function destroy($id)
    {
        $token = Session::get('api_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token autentikasi tidak ditemukan.'], 401);
        }

        try {
            $response = $this->client->delete("/api/perusahaan/{$id}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['success']) && $responseData['success']) {
                return response()->json(['success' => true, 'message' => 'Perusahaan berhasil dihapus.']);
            } else {
                return response()->json(['success' => false, 'message' => $responseData['message'] ?? 'Gagal menghapus perusahaan.'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Delete Perusahaan Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus perusahaan.'], 500);
        }
    }
}
