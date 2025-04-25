<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Prodi;
use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Helpers\CaesarCipher;
use App\Services\AuthService;

class AuthController extends Controller
{
    // 2. Check that 'tahun_lulus' is within the last 2 years.
    // $currentYear = \Carbon\Carbon::now()->year;
    // if ($request->tahun_lulus < ($currentYear - 2) || $request->tahun_lulus > $currentYear) {
    //     return redirect()->back()
    //         ->withErrors(['tahun_lulus' => 'Tahun lulus must be within the last 2 years.'])
    //         ->withInput();
    // }

    protected $authApi;

    public function __construct(AuthService $authService)
    {
        $this->authApi = $authService;
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'nim'          => 'required|string',
            'prodi'        => 'required|string',
            'fakultas'     => 'required|string',
            'tahun_lulus'  => 'required|integer',
            'password'     => 'required|string|confirmed',
        ]);

        $result = $this->authApi->register($request->only([
            'name',
            'nim',
            'prodi',
            'fakultas',
            'tahun_lulus',
            'password'
        ]));

        if (isset($result['token'])) {
            Session::put('api_token', $result['token']);
            return redirect('/')->with('success', 'Registrasi Berhasil');
        }

        return back()->withErrors(['api' => $result['message'] ?? 'Registrasi Gagal']);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nim'      => 'required|string',
            'password' => 'required|string',
        ]);

        if (config('app.login_require_captcha')) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $result = $this->authApi->login($request->only(['nim', 'password']));

        if (isset($result['token'])) {
            Session::put('api_token', $result['token']);
            Session::put('user', $result['user']);

            // Redirect based on role
            $role = $result['user']['role'];
            return match ($role) {
                'admin'  => redirect('/admin')->with('success', 'Welcome, Big Boss!'),
                'alumni' => redirect('/')->with('success', 'Login Sukses'),
            };
        }

        return back()->withErrors(['auth' => $result['message'] ?? 'Login Berhasil']);
    }

    public function logout()
    {
        Session::forget('api_token');
        Session::forget('user');

        return redirect('/')->with('success', 'Logged out successfully');
    }
}
