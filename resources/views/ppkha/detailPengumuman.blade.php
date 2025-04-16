@extends('layouts.app')

@section('content')
    @include('components.navbar')
    <div class="p-3 detail-content">
        <div>
            <h1 class="roboto-light mb-0" style="font-style: italic; color: #0F1035; font-weight: 500; font-size: 45px;">
                {{ $pengumuman->judul_pengumuman }}</h1>
            <hr>
            <p style = "font-family: 'Roboto Mono', serif ; font-size : 18px; font-weight: 400; color: white" class="mb-1">
                {{ date('d M Y H:i:s', strtotime($pengumuman->updated_at)) }} WIB
            </p>
        </div>

        <div class="p-4">
            <p style="font-family: 'Roboto Mono', serif; font-weight: 500; color: white;">
                {!! nl2br(e($pengumuman->deskripsi_pengumuman)) !!}
            </p>
        </div>

        <h5 style="font-size: 1.2rem; font-weight: bold; margin-bottom: 20px; color: white;">Nama File:</h5>
        <hr>

        @if (!empty($lampiran))
            <ul style="font-size: 1rem; color: white; list-style: none; padding: 0;">
                @foreach ($lampiran as $file)
                    <li style="margin-bottom: 10px;">
                        <a href="{{ $file['url'] }}" target="_blank" style="color: white;">
                            {{ basename($file['path']) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p style="font-size: 1rem; color: #777;">Tidak ada lampiran tersedia.</p>
        @endif




    </div>
    @include('components.footer')
@endsection
