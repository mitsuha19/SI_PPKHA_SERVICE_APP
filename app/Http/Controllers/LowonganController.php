<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Lowongan;
use App\Models\Perusahaan;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class LowonganController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => config('services.main_api.url')]);
    }

    public function index2(Request $request)
    {
        $search = $request->input('search');

        try {
            $response = $this->client->get('/api/lowongan', [
                'query' => ['search' => $search],
            ]);

            $data = collect(json_decode($response->getBody()->getContents(), true)['data'])
                ->map(function ($item) {
                    $item = (object) $item;
                    if (isset($item->perusahaan)) {
                        $item->perusahaan = (object) $item->perusahaan;
                    }
                    return $item;
                });

            \Log::info('Lowongan Data with Perusahaan', $data->toArray());

            $perPage = 2;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pagedData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $lowongan = new LengthAwarePaginator(
                $pagedData,
                $data->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('admin.lowonganPekerjaan.lowonganPekerjaan', compact('lowongan', 'search'));
        } catch (\Exception $e) {
            \Log::error('Index2 Error: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil data lowongan: ' . $e->getMessage());
        }
    }

    public function showLowonganUser(Request $request)
    {
        $search = $request->input('search');

        // Panggil API untuk Lowongan Pekerjaan
        $response = Http::get('http://127.0.0.1:8001/api/lowongan', [
            'search' => $search,
        ]);

        if ($response->successful()) {
            // Mengambil data dari API dan mengonversinya ke Collection
            $data = collect($response->json()['data'])->map(fn($item) => (object) $item);

            // Membuat Collection dari data
            $collection = collect($data);

            // Buat pagination manual (karena API tidak menyediakan paginate bawaan)
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $lowongan = new LengthAwarePaginator(
                $pagedData,
                $collection->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Return view dengan data lowongan dan search term
            return view('ppkha.lowongan_pekerjaan', compact('lowongan', 'search'));
        }

        // Jika gagal ambil data
        return back()->withErrors(['error' => 'Gagal mengambil data lowongan pekerjaan dari API.']);
    }


    public function showLowonganDetailUser($id)
    {
        try {
            $response = $this->client->get("/api/lowongan/{$id}");
            $lowonganData = json_decode($response->getBody()->getContents(), true)['data'] ?? null;

            if (!$lowonganData) {
                return back()->withErrors('Data lowongan tidak ditemukan');
            }

            $lowongan = (object) $lowonganData;
            $perusahaan = Perusahaan::where('id', $lowongan->perusahaan_id ?? null)->get();

            return view('ppkha.detaillowongan', compact('lowongan', 'perusahaan'));
        } catch (\Exception $e) {
            return back()->withErrors('Gagal mengambil detail lowongan');
        }
    }

    public function index3()
    {
        $perusahaan = Perusahaan::all();
        return view('admin.lowonganPekerjaan.lowonganPekerjaanAdd', compact('perusahaan'));
    }

    public function index4($id)
    {
        try {
            $lowonganResponse = $this->client->get("/api/lowongan/{$id}");
            $lowonganData = json_decode($lowonganResponse->getBody()->getContents(), true);

            // Log the raw API response
            \Log::info('Raw API Response for Lowongan: ', $lowonganData);

            if (!$lowonganData['success'] || !$lowonganData['data']) {
                return back()->withErrors('Data lowongan tidak ditemukan');
            }

            $lowongan = (object) $lowonganData['data'];
            $lowongan->perusahaan = (object) $lowongan->perusahaan;

            // Handle keahlian field: decode JSON or split comma-separated string
            if (is_string($lowongan->keahlian)) {
                // Try to decode as JSON first
                $decoded = json_decode($lowongan->keahlian, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $lowongan->keahlian = $decoded;
                } else {
                    // If not JSON, treat as comma-separated string
                    $lowongan->keahlian = array_filter(array_map('trim', explode(',', $lowongan->keahlian)));
                }
            } elseif (!is_array($lowongan->keahlian)) {
                $lowongan->keahlian = [];
            }

            // Log the processed keahlian data
            \Log::info('Processed Keahlian Data: ', $lowongan->keahlian);

            $perusahaanResponse = $this->client->get('/api/perusahaan');
            $perusahaanData = json_decode($perusahaanResponse->getBody()->getContents(), true);

            if (!$perusahaanData['success'] || !$perusahaanData['data']) {
                return back()->withErrors('Gagal mengambil data perusahaan');
            }

            $perusahaan = collect($perusahaanData['data'])->map(fn($item) => (object) $item);

            return view('admin.lowonganPekerjaan.lowonganPekerjaanEdit', compact('lowongan', 'perusahaan'));
        } catch (\Exception $e) {
            \Log::error('Index4 Error: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil data: ' . $e->getMessage());
        }
    }

    public function show1($id)
    {
        try {
            $response = $this->client->get("/api/lowongan/{$id}");
            $lowonganData = json_decode($response->getBody()->getContents(), true);
            \Log::info('Raw API Response for Lowongan in show1: ', $lowonganData);

            if (!$lowonganData['success'] || !isset($lowonganData['data'])) {
                return back()->withErrors('Data lowongan tidak ditemukan');
            }

            $lowongan = (object) $lowonganData['data'];
            $lowongan->perusahaan = isset($lowonganData['data']['perusahaan']) ? (object) $lowonganData['data']['perusahaan'] : (object) [];
            \Log::info('Logo Path in show1: ', ['logo' => $lowongan->perusahaan->logo ?? 'Not Set']);

            return view('admin.lowonganPekerjaan.lowonganPekerjaanDetail', compact('lowongan'));
        } catch (\Exception $e) {
            \Log::error('Error in show1: ' . $e->getMessage());
            return back()->withErrors('Gagal mengambil detail lowongan');
        }
    }

    public function store(Request $request)
    {
        $token = Session::get('api_token');
        if (!$token) {
            return redirect()->route('login')->withErrors('Sesi habis, silakan login ulang');
        }

        // Validate the form data
        $validatedData = $request->validate([
            'judulLowongan' => 'required|string',
            'jenisLowongan' => 'required|string',
            'tipeLowongan' => 'required|string',
            'deskripsiLowongan' => 'required|string',
            'kualifikasi' => 'required|string',
            'benefit' => 'required|string',
            'keahlian' => 'required|array',
            'batasMulai' => 'required|date',
            'batasAkhir' => 'required|date',
            'namaPerusahaan' => 'required|string',
        ]);

        try {
            // Prepare multipart data for the API request
            $multipart = [
                ['name' => 'judulLowongan', 'contents' => $validatedData['judulLowongan']],
                ['name' => 'jenisLowongan', 'contents' => $validatedData['jenisLowongan']],
                ['name' => 'tipeLowongan', 'contents' => $validatedData['tipeLowongan']],
                ['name' => 'deskripsiLowongan', 'contents' => $validatedData['deskripsiLowongan']],
                ['name' => 'kualifikasi', 'contents' => $validatedData['kualifikasi']],
                ['name' => 'benefit', 'contents' => $validatedData['benefit']],
                ['name' => 'batasMulai', 'contents' => $validatedData['batasMulai']],
                ['name' => 'batasAkhir', 'contents' => $validatedData['batasAkhir']],
                ['name' => 'namaPerusahaan', 'contents' => $request->namaPerusahaan],
            ];

            // Add keahlian as an array
            foreach ($validatedData['keahlian'] as $index => $skill) {
                $multipart[] = ['name' => "keahlian[$index]", 'contents' => $skill];
            }

            // Handle new company fields if namaPerusahaan is "Other"
            if ($request->namaPerusahaan === 'Other') {
                $request->validate([
                    'namaPerusahaanBaru' => 'required|string',
                    'lokasiPerusahaan' => 'required|string',
                    'websitePerusahaan' => 'nullable|url',
                    'industriPerusahaan' => 'required|string',
                    'deskripsiPerusahaan' => 'required|string',
                    'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                ]);

                $multipart[] = ['name' => 'namaPerusahaanBaru', 'contents' => $request->namaPerusahaanBaru];
                $multipart[] = ['name' => 'lokasiPerusahaan', 'contents' => $request->lokasiPerusahaan];
                $multipart[] = ['name' => 'websitePerusahaan', 'contents' => $request->websitePerusahaan ?? ''];
                $multipart[] = ['name' => 'industriPerusahaan', 'contents' => $request->industriPerusahaan];
                $multipart[] = ['name' => 'deskripsiPerusahaan', 'contents' => $request->deskripsiPerusahaan];

                if ($request->hasFile('logo')) {
                    $multipart[] = [
                        'name' => 'logo',
                        'contents' => fopen($request->file('logo')->path(), 'r'),
                        'filename' => $request->file('logo')->getClientOriginalName(),
                    ];
                }
            }

            // Send POST request to the API
            $response = $this->client->post('/api/lowongan', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'multipart' => $multipart,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            if ($responseData['success']) {
                return redirect()->route('admin.lowonganPekerjaan.lowonganPekerjaan')
                    ->with('success', 'Lowongan berhasil ditambahkan');
            }

            return back()->withErrors('Gagal menambahkan lowongan: ' . ($responseData['message'] ?? 'Unknown error'))->withInput();
        } catch (\Exception $e) {
            \Log::error('Store Error: ' . $e->getMessage());
            return back()->withErrors('Gagal menambahkan lowongan: ' . $e->getMessage())->withInput();
        }
    }


    public function update(Request $request, $id)
    {
        try {
            // Validate the request data (this matches the form field names)
            $validatedData = $request->validate([
                'judulLowongan' => 'required|string|max:255',
                'perusahaan_id' => 'required|integer',
                'jenisLowongan' => 'required|string|max:255',
                'tipeLowongan' => 'required|in:Full-time,Part-time,Magang,Kontrak',
                'deskripsiLowongan' => 'required|string',
                'kualifikasi' => 'required|string',
                'benefit' => 'required|string',
                'keahlian' => 'required|array',
                'keahlian.*' => 'string|max:255',
                'batasMulai' => 'required|date',
                'batasAkhir' => 'required|date|after_or_equal:batasMulai',
            ]);

            // Retrieve the JWT token
            $token = Session::get('api_token');

            // Log session data for debugging
            \Log::info('Session Data in Update: ', session()->all());
            \Log::info('JWT Token in Update: ' . ($token ?? 'Not Found'));

            if (!$token) {
                return redirect()->route('login')->withErrors('Gagal memperbarui lowongan: Token autentikasi tidak ditemukan. Silakan login kembali.');
            }

            // Prepare data for the API using camelCase to match API expectations
            $data = [
                'judulLowongan' => $validatedData['judulLowongan'],
                'perusahaan_id' => $validatedData['perusahaan_id'],
                'jenisLowongan' => $validatedData['jenisLowongan'],
                'tipeLowongan' => $validatedData['tipeLowongan'],
                'deskripsiLowongan' => $validatedData['deskripsiLowongan'],
                'kualifikasi' => $validatedData['kualifikasi'],
                'benefit' => $validatedData['benefit'],
                'keahlian' => $validatedData['keahlian'],
                'batasMulai' => $validatedData['batasMulai'],
                'batasAkhir' => $validatedData['batasAkhir'],
            ];

            // Send PUT request to API
            $response = $this->client->put("/api/lowongan/{$id}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ],
                'json' => $data,
            ]);

            // Parse the API response
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Log the API response for debugging
            \Log::info('Update Lowongan API Response: ', $responseData);

            // Check if API request was successful
            if (isset($responseData['success']) && $responseData['success']) {
                return redirect()->route('admin.lowonganPekerjaan.lowonganPekerjaan')
                    ->with('success', 'Lowongan berhasil diperbarui.');
            }

            return redirect()->back()->withErrors('Gagal memperbarui lowongan: ' . ($responseData['message'] ?? 'Kesalahan tidak diketahui.'));

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Log::error('Update Lowongan Error: ' . $e->getMessage());
            $errorMessage = 'Gagal memperbarui lowongan: ';

            if ($e->hasResponse()) {
                $response = json_decode($e->getResponse()->getBody()->getContents(), true);
                $errorMessage .= $response['message'] ?? 'Kesalahan API.';
            } else {
                $errorMessage .= 'Tidak dapat terhubung ke API.';
            }

            return redirect()->back()->withErrors($errorMessage);
        } catch (\Exception $e) {
            \Log::error('Update Lowongan Error: ' . $e->getMessage());
            return redirect()->back()->withErrors('Gagal memperbarui lowongan: ' . $e->getMessage());
        }
    }

    // private function getPerusahaanId($namaPerusahaan)
    // {
    //     try {
    //         // Get JWT token (adjust based on your setup)
    //         $token = session('jwt_token');

    //         if (!$token) {
    //             throw new \Exception('Token autentikasi tidak ditemukan.');
    //         }

    //         // Fetch perusahaan data from API
    //         $response = $this->client->get('/api/perusahaan', [
    //             'headers' => [
    //                 'Authorization' => "Bearer {$token}",
    //                 'Accept' => 'application/json',
    //             ],
    //         ]);

    //         $perusahaanData = json_decode($response->getBody()->getContents(), true);

    //         // Log the API response for debugging
    //         \Log::info('Perusahaan API Response: ', $perusahaanData);

    //         if (isset($perusahaanData['data'])) {
    //             foreach ($perusahaanData['data'] as $perusahaan) {
    //                 // Use namaPerusahaan instead of nama_perusahaan
    //                 if (isset($perusahaan['namaPerusahaan']) && $perusahaan['namaPerusahaan'] === $namaPerusahaan) {
    //                     return $perusahaan['id'];
    //                 }
    //             }
    //         }

    //         // Log if no matching perusahaan is found
    //         \Log::warning("Perusahaan with namaPerusahaan '{$namaPerusahaan}' not found.");
    //         return null;

    //     } catch (\Exception $e) {
    //         \Log::error('GetPerusahaanId Error: ' . $e->getMessage());
    //         throw new \Exception('Gagal mengambil ID perusahaan: ' . $e->getMessage());
    //     }
    // }

    public function destroy($id)
    {
        try {
            // Retrieve the JWT token from the session
            $token = Session::get('api_token');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token autentikasi tidak ditemukan. Silakan login kembali.'], 401);
            }

            // Send DELETE request to the API
            $response = $this->client->delete("/api/lowongan/{$id}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Check if the API request was successful
            if (isset($responseData['success']) && $responseData['success']) {
                return response()->json(['success' => true, 'message' => 'Lowongan berhasil dihapus.']);
            }

            return response()->json(['success' => false, 'message' => 'Gagal menghapus lowongan: ' . ($responseData['message'] ?? 'Kesalahan tidak diketahui.')], 400);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Log::error('Destroy Lowongan Error: ' . $e->getMessage());
            $errorMessage = 'Gagal menghapus lowongan: ';
            if ($e->hasResponse()) {
                $response = json_decode($e->getResponse()->getBody()->getContents(), true);
                $errorMessage .= $response['message'] ?? 'Kesalahan API.';
            } else {
                $errorMessage .= 'Tidak dapat terhubung ke API.';
            }
            return response()->json(['success' => false, 'message' => $errorMessage], 500);
        } catch (\Exception $e) {
            \Log::error('Destroy Lowongan Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus lowongan: ' . $e->getMessage()], 500);
        }
    }
}
