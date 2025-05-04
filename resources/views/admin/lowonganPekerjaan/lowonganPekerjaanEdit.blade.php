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
            <form action="{{ route('admin.perusahaan.update', $perusahaan->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <label for="namaPerusahaan" class="poppins-bold text-black mb-2">Nama Perusahaan:</label>
                <input type="text" class="form-control mb-3 @error('namaPerusahaan') is-invalid @enderror"
                    id="namaPerusahaan" name="namaPerusahaan"
                    value="{{ old('namaPerusahaan', $perusahaan->namaPerusahaan) }}">
                @error('namaPerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="lokasiPerusahaan" class="poppins-bold text-black mb-2">Alamat Perusahaan:</label>
                <input type="text" class="form-control mb-3 @error('lokasiPerusahaan') is-invalid @enderror"
                    id="alamatPerusahaan" name="lokasiPerusahaan"
                    value="{{ old('lokasiPerusahaan', $perusahaan->lokasiPerusahaan) }}">
                @error('lokasiPerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="websitePerusahaan" class="poppins-bold text-black mb-2">Website Perusahaan:</label>
                <input type="text" class="form-control mb-3 @error('websitePerusahaan') is-invalid @enderror"
                    id="websitePerusahaan" name="websitePerusahaan"
                    value="{{ old('websitePerusahaan', $perusahaan->websitePerusahaan) }}">
                @error('websitePerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="industriPerusahaan" class="poppins-bold text-black mb-2">Industri Perusahaan:</label>
                <input type="text" class="form-control mb-3 @error('industriPerusahaan') is-invalid @enderror"
                    id="industriPerusahaan" name="industriPerusahaan"
                    value="{{ old('industriPerusahaan', $perusahaan->industriPerusahaan) }}">
                @error('industriPerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="deskripsiPerusahaan" class="form-label poppins-bold text-black mb-2">Deskripsi
                    Perusahaan:</label>
                <textarea class="form-control mb-3 @error('deskripsiPerusahaan') is-invalid @enderror" id="deskripsiPerusahaan"
                    name="deskripsiPerusahaan">{{ old('deskripsiPerusahaan', $perusahaan->deskripsiPerusahaan) }}</textarea>
                @error('deskripsiPerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="upload" class="form-label poppins-bold text-black mt-2">Tambahkan Gambar/Logo:</label>
                <div class="mb-3">
                    <img src="{{ $perusahaan->logo ? config('services.main_api.url') . '/api/perusahaan/' . $perusahaan->id . '/logo' : asset('assets/images/default-logo.png') }}"
                        alt="Logo Perusahaan" style="height: 100px;"
                        onerror="this.onerror=null; this.src='{{ asset('assets/images/default-logo.png') }}'"
                        onload="console.log('Logo loaded for ID {{ $perusahaan->id }}: ', this.src)">
                </div>
                <div class="button-wrap">
                    <label class="buttonUploadFile" for="upload">
                        <i class='bx bx-upload me-1'></i>
                        Pilih File
                    </label>
                    <input id="upload" type="file" name="logo" onchange="previewFiles()" accept="image/*">
                    <div id="file-preview" class="mt-2"></div>
                    @error('logo')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end align-items-end gap-2 mt-3">
                    <a href="{{ route('admin.daftarPerusahaan.daftarPerusahaan') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Edit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewFiles() {
            let input = document.getElementById('upload');
            let preview = document.getElementById('file-preview');
            preview.innerHTML = '';

            if (input.files.length > 0) {
                for (let i = 0; i < input.files.length; i++) {
                    let file = input.files[i];
                    let fileReader = new FileReader();

                    fileReader.onload = function(e) {
                        let fileType = file.type.split('/')[0];
                        let fileDisplay = document.createElement('div');
                        fileDisplay.classList.add('mb-2');

                        if (fileType === 'image') {
                            fileDisplay.innerHTML =
                                `<img src="${e.target.result}" alt="Preview" class="img-thumbnail" width="100">`;
                        } else {
                            fileDisplay.innerHTML = `<p>${file.name}</p>`;
                        }

                        preview.appendChild(fileDisplay);
                    };

                    fileReader.readAsDataURL(file);
                }
            }
        }
    </script>
@endsection
