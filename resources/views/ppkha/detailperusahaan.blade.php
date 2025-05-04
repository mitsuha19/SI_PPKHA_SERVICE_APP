@extends('layouts.app')

@section('content')
    @include('components.navbar')

    <div class="p-3 detail-content">
        <!-- Berita Section -->
        <div class="horizontal-card2 mt-4">
            <div class="card-perusahaan d-flex flex-row align-items-center gap-5">
                <img style="height: 92px; width: auto;"
                    src="{{ $perusahaan->logo ? (str_starts_with($perusahaan->logo, 'http') ? $perusahaan->logo : config('services.main_api.url', 'http://127.0.0.1:8001') . '/storage/' . $perusahaan->logo) : asset('assets/images/default-logo.png') }}"
                    alt="Company Logo">
                <div class="montserrat-medium mb-0">
                    <h2>{{ $perusahaan->namaPerusahaan ?? 'No Name' }}</h2>
                    <p>{{ $perusahaan->lokasiPerusahaan ?? 'No Location' }}</p>
                    <div class="d-flex flex-row" style="gap: 100px;">
                        <p><span
                                style="color: #656565;">Industri</span><br>{{ $perusahaan->industriPerusahaan ?? 'No Industry' }}
                        </p>
                        <p><span style="color: #656565;">Website</span><br><a
                                href="{{ $perusahaan->websitePerusahaan ?? '#' }}"
                                target="_blank">{{ $perusahaan->websitePerusahaan ?? 'No Website' }}</a></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="horizontal-card3 mt-4">
            <div class="horizontal-card-body3">
                <div class="text-container">
                    <div class="horizontal-card-text-section3">
                        <h5 class="horizontal-card-title3">TENTANG KAMI</h5>
                        <p class="horizontal-card-text2">
                            {{ $perusahaan->deskripsiPerusahaan ?? 'No Description' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="horizontal-card3 mt-4">
            <h2 class="title">Lowongan</h2>
            @foreach ($lowongan as $job)
                <div style="border-bottom: 1px solid #000; padding: 10px;">
                    <div style="display: flex; align-items: start; gap: 20px; padding: 10px;">
                        <img style="height: 92px; width: auto;"
                            src="{{ $perusahaan->logo ? (str_starts_with($perusahaan->logo, 'http') ? $perusahaan->logo : config('services.main_api.url', 'http://127.0.0.1:8001') . '/storage/' . $perusahaan->logo) : asset('assets/images/default-logo.png') }}"
                            alt="Company Logo">
                        <div class="job-info" style="display: grid; gap: 10px;">
                            <h3>{{ $job->judulLowongan ?? 'No Title' }}</h3>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>{{ $job->deskripsiLowongan ?? 'No Description' }}</li>
                            </ul>
                        </div>
                        <a href="{{ route('ppkha.lowonganPekerjaanDetail', ['id' => $job->id ?? '#']) }}"
                            class="detail d-flex flex-row gap-1 align-items-center"
                            style="margin-left: auto; text-decoration: none; color: black; font-weight: bold;">
                            Detail
                            <i class='bx bx-right-arrow-alt'></i>
                        </a>
                    </div>
                    <div class="job-tags" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <span
                            style="background: #f3f3f3; padding: 5px 10px; border-radius: 5px;">{{ strtoupper($perusahaan->lokasiPerusahaan ?? 'No Location') }}</span>
                        <span
                            style="background: #f3f3f3; padding: 5px 10px; border-radius: 5px;">{{ $job->jenisLowongan ?? 'No Type' }}</span>
                        <span
                            style="background: #f3f3f3; padding: 5px 10px; border-radius: 5px;">{{ $job->tipeLowongan ?? 'No Category' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('components.footer')
@endsection
