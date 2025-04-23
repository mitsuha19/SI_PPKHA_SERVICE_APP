<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AuthService
{
  protected $baseUrl;

  public function __construct()
  {
    $this->baseUrl = config('services.auth_api.url');
  }

  public function register(array $data)
  {
    $response = Http::post("{$this->baseUrl}/auth/register", $data);
    return $response->json();
  }

  public function login(array $credentials)
  {
    $response = Http::post("{$this->baseUrl}/auth/login", $credentials);
    return $response->json();
  }
}
