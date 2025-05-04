@extends('layouts.appAdmin')
@section('content')
    @include('components.navbarAdmin')
    <div class="main-content">
        <h1 class="poppins-bold text-black mt-3" style="font-size: 22px">Edit Daftar Perusahaan</h1>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="box-form">
            <!-- Using POST with @method('PUT') to spoof PUT request, as HTML forms only support GET/POST -->
            <form action="{{ route('admin.perusahaan.update', $perusahaan->id) }}" method="POST" enctype="multipart/form-data"
                onsubmit="return validateForm()">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="namaPerusahaan" class="poppins-bold text-black mb-2">Nama Perusahaan:</label>
                    <input type="text" class="form-control @error('namaPerusahaan') is-invalid @enderror"
                        id="namaPerusahaan" name="namaPerusahaan"
                        value="{{ old('namaPerusahaan', $perusahaan->namaPerusahaan ?? '') }}">
                    @error('namaPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="lokasiPerusahaan" class="poppins-bold text-black mb-2">Lokasi Perusahaan:</label>
                    <input type="text" class="form-control @error('lokasiPerusahaan') is-invalid @enderror"
                        id="lokasiPerusahaan" name="lokasiPerusahaan"
                        value="{{ old('lokasiPerusahaan', $perusahaan->lokasiPerusahaan ?? '') }}">
                    @error('lokasiPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="websitePerusahaan" class="poppins-bold text-black mb-2">Website Perusahaan:</label>
                    <input type="url" class="form-control @error('websitePerusahaan') is-invalid @enderror"
                        id="websitePerusahaan" name="websitePerusahaan"
                        value="{{ old('websitePerusahaan', $perusahaan->websitePerusahaan ?? '') }}">
                    @error('websitePerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="industriPerusahaan" class="poppins-bold text-black mb-2">Industri Perusahaan:</label>
                    <input type="text" class="form-control @error('industriPerusahaan') is-invalid @enderror"
                        id="industriPerusahaan" name="industriPerusahaan"
                        value="{{ old('industriPerusahaan', $perusahaan->industriPerusahaan ?? '') }}">
                    @error('industriPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="deskripsiPerusahaan" class="form-label poppins-bold text-black mb-2">Deskripsi
                        Perusahaan:</label>
                    <textarea class="form-control @error('deskripsiPerusahaan') is-invalid @enderror" id="deskripsiPerusahaan"
                        name="deskripsiPerusahaan">{{ old('deskripsiPerusahaan', $perusahaan->deskripsiPerusahaan ?? '') }}</textarea>
                    @error('deskripsiPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="logo" class="form-label poppins-bold text-black mb-2">Tambahkan Gambar/Logo:</label>
                    <div class="mb-2">
                        <img src="{{ $perusahaan->logo ? config('services.main_api.url') . '/api/perusahaan/' . $perusahaan->id . '/logo' : asset('assets/images/default-logo.png') }}"
                            alt="Logo Perusahaan" style="height: 100px;"
                            onerror="this.onerror=null; this.src='{{ asset('assets/images/default-logo.png') }}'"
                            onload="console.log('Logo loaded for ID {{ $perusahaan->id }}: ', this.src)">
                    </div>
                    <div class="button-wrap">
                        <label class="buttonUploadFile" for="logo">
                            <i class='bx bx-upload me-1'></i>
                            Pilih File
                        </label>
                        <input id="logo" type="file" name="logo" accept="image/*" onchange="previewLogo()">
                        <div id="file-preview" class="mt-2"></div>
                        @error('logo')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('admin.daftarPerusahaan.daftarPerusahaan') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Edit</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function validateForm() {
            const websiteInput = document.getElementById('websitePerusahaan');
            if (websiteInput.value.trim()) {
                const urlPattern = /^(https?:\/\/)?([\w-]+\.)+[\w-]+(\/[\w-]*)*$/;
                if (!urlPattern.test(websiteInput.value)) {
                    alert('Website Perusahaan must be a valid URL (e.g., https://example.com).');
                    websiteInput.focus();
                    return false;
                }
            }
            return true;
        }

        function previewLogo() {
            const input = document.getElementById('logo');
            const preview = document.getElementById('file-preview');
            preview.innerHTML = '';
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Logo Preview';
                        img.className = 'img-thumbnail';
                        img.style.width = '100px';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '<p>File bukan gambar.</p>';
                }
            }
        }
    </script>
@endsection
