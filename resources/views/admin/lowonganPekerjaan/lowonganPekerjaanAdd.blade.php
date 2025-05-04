@extends('layouts.appAdmin')

@section('content')
    @include('components.navbarAdmin')
    <div class="main-content" style="max-height: 80vh; overflow-y: auto; padding: 15px;">
        <h1 class="poppins-bold text-black mt-3" style="font-size: 22px">Tambah Lowongan Pekerjaan</h1>
        <div class="box-form">
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

            <form action="{{ route('admin.lowonganPekerjaan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label for="judulLowongan" class="poppins-bold text-black mb-2">Judul Lowongan:</label>
                <input type="text" class="form-control mb-3 @error('judulLowongan') is-invalid @enderror"
                    id="judulLowongan" name="judulLowongan" placeholder="Masukkan Judul Lowongan"
                    value="{{ old('judulLowongan') }}" required>
                @error('judulLowongan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="namaPerusahaan" class="poppins-bold text-black mb-2">Nama Perusahaan:</label>
                <select id="namaPerusahaan" name="namaPerusahaan"
                    class="form-control mb-3 @error('namaPerusahaan') is-invalid @enderror" onchange="toggleOtherInput()"
                    required>
                    <option value="">Pilih Nama Perusahaan</option>
                    <option value="Other">Other</option>
                </select>
                @error('namaPerusahaan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <!-- Input tambahan jika memilih "Other" -->
                <div id="otherInputContainer" style="display: none;">
                    <label for="namaPerusahaanBaru" class="poppins-bold text-black mb-2">Nama Perusahaan Baru:</label>
                    <input type="text" class="form-control mb-3 @error('namaPerusahaanBaru') is-invalid @enderror"
                        id="namaPerusahaanBaru" name="namaPerusahaanBaru" placeholder="Masukkan Nama Perusahaan Baru"
                        value="{{ old('namaPerusahaanBaru') }}">
                    @error('namaPerusahaanBaru')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label for="lokasiPerusahaan" class="poppins-bold text-black mb-2">Lokasi Perusahaan:</label>
                    <input type="text" class="form-control mb-3 @error('lokasiPerusahaan') is-invalid @enderror"
                        id="lokasiPerusahaan" name="lokasiPerusahaan" placeholder="Masukkan Lokasi Perusahaan"
                        value="{{ old('lokasiPerusahaan') }}">
                    @error('lokasiPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label for="websitePerusahaan" class="poppins-bold text-black mb-2">Website Perusahaan:</label>
                    <input type="url" class="form-control mb-3 @error('websitePerusahaan') is-invalid @enderror"
                        id="websitePerusahaan" name="websitePerusahaan" placeholder="Masukkan URL Website Perusahaan"
                        value="{{ old('websitePerusahaan') }}">
                    @error('websitePerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label for="industriPerusahaan" class="poppins-bold text-black mb-2">Industri:</label>
                    <input type="text" class="form-control mb-3 @error('industriPerusahaan') is-invalid @enderror"
                        id="industriPerusahaan" name="industriPerusahaan" placeholder="Masukkan Industri Perusahaan"
                        value="{{ old('industriPerusahaan') }}">
                    @error('industriPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label for="deskripsiPerusahaan" class="form-label poppins-bold text-black mb-2">Deskripsi
                        Perusahaan:</label>
                    <textarea class="form-control mb-3 @error('deskripsiPerusahaan') is-invalid @enderror" id="deskripsiPerusahaan"
                        name="deskripsiPerusahaan" placeholder="Masukkan Deskripsi Perusahaan">{{ old('deskripsiPerusahaan') }}</textarea>
                    @error('deskripsiPerusahaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <label for="logo" class="form-label poppins-bold text-black mt-2">Tambahkan Gambar/Logo:</label>
                    <div class="button-wrap">
                        <label class="buttonUploadFile" for="logo">
                            <i class='bx bx-upload me-1'></i> Choose a File
                        </label>
                        <input type="file" class="form-control d-none @error('logo') is-invalid @enderror" id="logo"
                            name="logo" accept="image/jpeg,image/png,image/jpg,image/svg+xml" onchange="previewFiles()">
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="file-preview" class="mt-2"></div>
                </div>

                <label for="jenisLowongan" class="poppins-bold text-black mb-2">Departemen:</label>
                <input type="text" class="form-control mb-3 @error('jenisLowongan') is-invalid @enderror"
                    id="jenisLowongan" name="jenisLowongan" placeholder="Contoh: FullStack, Administrasi"
                    value="{{ old('jenisLowongan') }}" required>
                @error('jenisLowongan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="tipeLowongan" class="poppins-bold text-black mb-2">Tipe Lowongan:</label>
                <select name="tipeLowongan" class="form-select mb-3 @error('tipeLowongan') is-invalid @enderror" required>
                    <option value="">Pilih Tipe Lowongan</option>
                    <option value="Full-time" {{ old('tipeLowongan') == 'Full-time' ? 'selected' : '' }}>Full-time
                    </option>
                    <option value="Part-time" {{ old('tipeLowongan') == 'Part-time' ? 'selected' : '' }}>Part-time
                    </option>
                    <option value="Magang" {{ old('tipeLowongan') == 'Magang' ? 'selected' : '' }}>Magang</option>
                    <option value="Kontrak" {{ old('tipeLowongan') == 'Kontrak' ? 'selected' : '' }}>Kontrak</option>
                </select>
                @error('tipeLowongan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="deskripsiLowongan" class="form-label poppins-bold text-black mb-2">Deskripsi Lowongan:</label>
                <textarea class="form-control mb-3 @error('deskripsiLowongan') is-invalid @enderror" id="deskripsiLowongan"
                    name="deskripsiLowongan" placeholder="Masukkan Deskripsi Lowongan" required>{{ old('deskripsiLowongan') }}</textarea>
                @error('deskripsiLowongan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="kualifikasi" class="form-label poppins-bold text-black mb-2">Kualifikasi Lowongan:</label>
                <textarea class="form-control mb-3 @error('kualifikasi') is-invalid @enderror" id="kualifikasi" name="kualifikasi"
                    placeholder="Masukkan Kualifikasi Lowongan" required>{{ old('kualifikasi') }}</textarea>
                @error('kualifikasi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="benefit" class="form-label poppins-bold text-black mb-2">Benefit Lowongan:</label>
                <textarea class="form-control mb-3 @error('benefit') is-invalid @enderror" id="benefit" name="benefit"
                    placeholder="Masukkan Benefit Lowongan" required>{{ old('benefit') }}</textarea>
                @error('benefit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="keahlian" class="poppins-bold text-black mb-2">Tambahkan Keahlian Lowongan:</label>
                <div id="keahlianContainer">
                    <button type="button" class="btn mt-1 mb-3" style="width: fit-content; height: fit-content;"
                        onclick="addKeahlian()">
                        <i class='bx bx-plus'></i> Tambah Keahlian
                    </button>
                    @if (old('keahlian'))
                        @foreach (old('keahlian') as $skill)
                            <div class="keahlian-item d-flex mb-2">
                                <div class="d-flex flex-row align-items-center w-100">
                                    <input type="text" class="form-control me-2" name="keahlian[]"
                                        placeholder="Masukkan Keahlian" value="{{ $skill }}" required>
                                    <button type="button" class="btn btn-danger btn-sm mt-0"
                                        onclick="removeKeahlian(this)">❌</button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                @error('keahlian')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                <label for="batasMulai" class="poppins-bold text-black mb-2">Batas Mulai Lamaran:</label>
                <input type="date" class="form-control mb-3 @error('batasMulai') is-invalid @enderror"
                    id="batasMulai" name="batasMulai" value="{{ old('batasMulai') }}" required>
                @error('batasMulai')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <label for="batasAkhir" class="poppins-bold text-black mb-2">Batas Akhir Lamaran:</label>
                <input type="date" class="form-control mb-3 @error('batasAkhir') is-invalid @enderror"
                    id="batasAkhir" name="batasAkhir" value="{{ old('batasAkhir') }}" required>
                @error('batasAkhir')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="d-flex justify-content-end align-items-end gap-2">
                    <a href="{{ route('admin.lowonganPekerjaan.lowonganPekerjaan') }}" class="btn btn-batal">Batal</a>
                    <button type="submit" class="btn">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        function toggleOtherInput() {
            const selectBox = document.getElementById('namaPerusahaan');
            const otherInputContainer = document.getElementById('otherInputContainer');
            otherInputContainer.style.display = selectBox.value === 'Other' ? 'block' : 'none';
        }

        let perusahaanLoaded = false;

        async function loadPerusahaan() {
            if (perusahaanLoaded) return; // Only fetch once
            perusahaanLoaded = true;

            try {
                const response = await fetch('{{ config('services.main_api.url') }}/api/perusahaan');
                const data = await response.json();
                console.log('Perusahaan fetched:', data);

                const select = document.getElementById('namaPerusahaan');

                // Remove existing options except the first (Pilih) and last (Other)
                let options = Array.from(select.options).filter(opt => opt.value !== '' && opt.value !== 'Other');
                options.forEach(opt => opt.remove());

                // Insert new perusahaan
                data.data.forEach(perusahaan => {
                    let option = document.createElement('option');
                    option.value = perusahaan.namaPerusahaan; // Use namaPerusahaan instead of id
                    option.text = perusahaan.namaPerusahaan;
                    select.insertBefore(option, select.options[select.options.length - 1]); // Before "Other"
                });

                // Restore old value if present
                @if (old('namaPerusahaan'))
                    select.value = '{{ old('namaPerusahaan') }}';
                    toggleOtherInput();
                @endif
            } catch (error) {
                console.error('Gagal mengambil data perusahaan:', error);
            }
        }

        function addKeahlian() {
            const container = document.getElementById('keahlianContainer');
            const newInput = document.createElement('div');
            newInput.classList.add('keahlian-item', 'd-flex', 'mb-2');
            newInput.innerHTML = `
                <div class="d-flex flex-row align-items-center w-100">
                    <input type="text" class="form-control me-2" name="keahlian[]" placeholder="Masukkan Keahlian" required>
                    <button type="button" class="btn btn-danger btn-sm mt-0" onclick="removeKeahlian(this)">❌</button>
                </div>
            `;
            container.insertBefore(newInput, container.firstChild.nextSibling); // Insert after "Tambah Keahlian" button
        }

        function removeKeahlian(button) {
            button.parentElement.parentElement.remove();
        }

        function previewFiles() {
            const input = document.getElementById('logo');
            const preview = document.getElementById('file-preview');
            preview.innerHTML = '';

            if (input.files.length > 0) {
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const fileReader = new FileReader();

                    fileReader.onload = function(e) {
                        const fileType = file.type.split('/')[0];
                        const fileDisplay = document.createElement('div');
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

        document.addEventListener('DOMContentLoaded', function() {
            loadPerusahaan();
            toggleOtherInput(); // Ensure correct initial state
        });
    </script>
@endsection
