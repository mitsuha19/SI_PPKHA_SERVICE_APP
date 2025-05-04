@extends('layouts.app')

@section('content')
    @include('components.navbar')

    <div class="content-with-background">
        @include('components.bg')

        <div class="top-search-bar-container">
            <div class="top-search-bar d-flex align-items-center">
                <form class="d-flex w-100" action="{{ route('ppkha.lowonganPekerjaan') }}" method="GET">
                    <input type="text" id="lowongan" name="search" class="form-control me-2"
                        placeholder="Cari Lowongan Pekerjaan..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-search bx-sm'></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="d-flex flex-column align-items-center gap-4 mt-4">
            @foreach ($lowongan as $l)
                <div class="background-card">
                    <div class="card-information d-flex align-items-center p-3 gap-3">
                        @php
                            $backendUrl = env('BACKEND_FILE_URL', 'http://127.0.0.1:8001');
                            $logoUrl =
                                isset($l->perusahaan['logo']) && $l->perusahaan['logo']
                                    ? $backendUrl . '/storage/' . $l->perusahaan['logo']
                                    : null;
                            // Debug: Log the logo URL
                            \Log::info('Logo URL for lowongan ' . $l->id . ': ' . ($logoUrl ?? 'No logo'));
                        @endphp

                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo Perusahaan"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px;"
                                onerror="this.src='{{ asset('assets/images/image.png') }}'">
                        @else
                            <img src="{{ asset('assets/images/image.png') }}" alt="Default Logo"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px;">
                        @endif

                        <div class="ps-2 w-100">
                            <div class="d-flex justify-content-between align-items-center w-100 mb-1">
                                <h5 class="fw-bold mb-0">{{ $l->judulLowongan }}</h5>
                                <div class="d-flex flex-row align-items-center gap-1 detail">
                                    <a href="{{ route('ppkha.lowonganPekerjaanDetail', ['id' => $l->id]) }}">Detail</a>
                                    <i class='bx bx-sm bx-right-arrow-alt'></i>
                                </div>
                            </div>

                            <hr class="my-1" style="border: 1px solid black; opacity: 1">

                            <p class="mb-1 montserrat-light" style="font-size: 14px;">
                                {{ $l->perusahaan['namaPerusahaan'] ?? 'Perusahaan tidak tersedia' }}
                            </p>

                            <p class="roboto-light text-black mb-2" style="font-size: 13px;">
                                {!! nl2br(e(Str::limit($l->deskripsiLowongan, 100, '...'))) !!}
                            </p>

                            <div class="d-flex flex-wrap gap-2">
                                <div class="pills small-pill">
                                    {{ $l->perusahaan['lokasiPerusahaan'] ?? 'Lokasi tidak ada' }}</div>
                                <div class="pills small-pill">{{ $l->jenisLowongan }}</div>
                                <div class="pills small-pill">{{ $l->tipeLowongan ?? 'Full-Time' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $lowongan->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    @include('components.footer')
@endsection
