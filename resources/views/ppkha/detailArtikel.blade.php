@extends('layouts.app')

@section('content')
@include('components.navbar')
<div class="detail-content">
    <div>
        <h1 class="roboto-light mb-0" style="font-style: italic; color: #0F1035; font-weight: 500; font-size: 45px;">
            {{ $artikel->judul_artikel }}
        </h1>
        <hr>
        <p style="font-family: 'Roboto Mono', serif; font-size: 18px; font-weight: 400; color: white" class="mb-1">
            {{ date('d M Y H:i:s', strtotime($artikel->updated_at)) }} WIB
        </p>
    </div>
    
    {{-- Carousel untuk Gambar --}}
    <div class="w-100 d-flex justify-content-center m-2">
        <div id="artikelCarousel" style="width: 80%" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            @foreach ($gambar as $index => $item)
              <button type="button" data-bs-target="#artikelCarousel" data-bs-slide-to="{{ $index }}"
                class="{{ $index == 0 ? 'active' : '' }}" aria-label="Slide {{ $index + 1 }}"></button>
            @endforeach
          </div>
          <div class="carousel-inner">
            @forelse ($gambar as $index => $item)
              <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                <img style="width: 100%" src="{{ $item['url'] }}" class="d-block w-100" alt="Gambar Artikel">
              </div>
            @empty
              <div class="carousel-item active">
                <img style="width: 100%" src="{{ asset('assets/images/image.png') }}" class="d-block w-100" alt="Default Gambar">
              </div>
            @endforelse
          </div>
    
          <button class="carousel-control-prev" type="button" data-bs-target="#artikelCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#artikelCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

    <div class="p-4">
        <p style="font-family: 'Roboto Mono', serif; font-weight: 500; color: white;">
            {{ $artikel->deskripsi_artikel }}
            <br> <br>
          Sumber : <br>
          <a class="text-white" href="{{ $artikel->sumber_artikel }}"> {{ $artikel->sumber_artikel }}</a>
        </p>
    </div>
    
    <div class="pengumuman-section">
        
        <h2 class="section-title">Rekomendasi Artikel</h2>
        <div class="pengumuman-grid">
            @foreach ($artikelRekomendasi as $item)
            <div class="card" style="width: 18rem;">
                @php
                    $gambarArray = json_decode($item['gambar'], true) ?? [];
                @endphp

                @if (!empty($gambarArray) && isset($gambarArray[0]))
                    <img class="card-img-top w-100" src="{{ env('BACKEND_FILE_URL') . '/' . ltrim($gambarArray[0], '/') }}" alt="Gambar Artikel">
                @else
                    <img class="card-img-top w-100" src="{{ asset('assets/images/image.png') }}" alt="Default Gambar">
                @endif

                <div class="card-body ps-0 pe-0">
                    <h5 class="card-title">
                        {{ $item['judul_artikel'] }}
                    </h5>
                    <a class="text-white" style="text-align: right;"  href="{{ route('ppkha.detailArtikel', ['id' => $item['id']]) }}">Selengkapnya..</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@include('components.footer')
@endsection
