@extends('layouts.appAdmin')

@section('content')
    @include('components.navbarAdmin')
    <div class="main-content d-flex flex-column align-items-center">
        <h1>Daftar Perusahaan</h1>

        {{-- Form Pencarian --}}
        <div class="d-flex flex-row justify-content-center gap-2 w-100 mb-3">
            <form class="w-50 d-flex" action="{{ route('admin.daftarPerusahaan.daftarPerusahaan') }}" method="GET">
                <input type="text" id="perusahaan" name="search" class="form-control" placeholder="Cari Nama Perusahaan..."
                    value="{{ request('search') }}">
                <button type="submit" class="search-logo d-flex justify-content-center align-items-center">
                    <i class='bx bx-search-alt-2'></i>
                </button>
            </form>
        </div>

        @foreach ($perusahaan as $p)
            <div class="background-card">
                <div class="card-information d-flex align-items-center px-3">
                    <img style="width: 100px"
                        src="{{ $p->logo ? config('services.main_api.url') . '/api/perusahaan/' . $p->id . '/logo' : asset('assets/images/default-logo.png') }}"
                        onerror="this.onerror=null; this.src='{{ asset('assets/images/default-logo.png') }}'"
                        onload="console.log('Logo loaded for ID {{ $p->id }}: ', this.src)">
                    <div class="ps-3 w-100">
                        <div class="d-flex flex-row w-auto justify-content-start align-items-end">
                            <h2 class="fst-italic roboto-title mb-0 align-self-center">
                                {{ $p->namaPerusahaan }}
                            </h2>
                            <div class="align-self-start">
                                <div class="ms-auto d-flex gap-2">
                                    <button type="button" id="btn-edit"
                                        class="btn btn-primary px-4 py-2 fw-bold d-flex align-items-center"
                                        onclick="window.location.href='{{ route('admin.perusahaan.edit', $p->id) }}'">
                                        <i class='bx bx-pencil fs-5 me-2'></i> Edit
                                    </button>
                                    <button type="button" id="btn-hapus"
                                        class="btn btn-primary px-4 py-2 fw-bold d-flex align-items-center"
                                        onclick="openDeleteModal({{ $p->id }}, '{{ $p->namaPerusahaan }}')">
                                        <i class='bx bx-trash fs-5 me-2'></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2" style="border: 2px solid black; opacity: 1">
                        <div class="d-flex flex-row align-items-center" style="gap: 5px">
                            <p class="mb-0 montserrat-light">{{ $p->lokasiPerusahaan }}</p>
                            <div class="circle"></div>
                            <p class="mb-0 montserrat-medium">{{ $p->industriPerusahaan }}</p>
                        </div>
                        <p class="roboto mb-1 mt-2" style="font-size: 15px">
                            {!! nl2br(e(Str::limit($p->deskripsiPerusahaan, 50, '...'))) !!}
                        </p>
                        <div class="detail">
                            <a href="{{ route('admin.daftarPerusahaan.daftarPerusahaanDetail', ['id' => $p->id]) }}">
                                Selengkapnya..
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="pagination">
            {{ $perusahaan->appends(request()->query())->links() }}
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: linear-gradient(to bottom, #80C7D9, #446973);">
                <div class="modal-body d-flex flex-column align-items-center justify-content-center gap-4">
                    <h5 class="modal-title" id="deleteModalLabel"></h5>
                    <h2 class="text-center">Apakah anda yakin untuk menghapus <b id="namaPerusahaan"></b>?</h2>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal"
                            style="padding: 15px 40px; font-size: 1.5rem; background: linear-gradient(to bottom, #80C7D9, #446973); border: none;">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-lg text-white" id="confirmDeleteButton"
                            style="padding: 15px 40px; font-size: 1.5rem; background: linear-gradient(to bottom, #80C7D9, #446973); border: none;">
                            Yes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedId = null;

        function openDeleteModal(id, title) {
            selectedId = id;
            fetch(`{{ config('services.main_api.url') }}/api/perusahaan/${id}`, {
                    headers: {
                        'Authorization': 'Bearer {{ $token }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const lowonganCount = data.data.lowongan?.length || 0;
                    document.getElementById('namaPerusahaan').innerText = title;
                    document.getElementById('deleteModalLabel').innerText = lowonganCount > 0 ?
                        `Peringatan: ${lowonganCount} lowongan akan dihapus.` :
                        'Konfirmasi Hapus';
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal mengambil data perusahaan. Silakan coba lagi.');
                });
        }

        document.getElementById('confirmDeleteButton').addEventListener('click', function() {
            // Added for debugging: Log the ID being deleted
            console.log('Attempting to delete ID:', selectedId);
            fetch(`/admin/daftar-perusahaan/${selectedId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => {
                    // Added for debugging: Log the HTTP response status
                    console.log('Response status:', response.status);
                    if (!response.ok) throw new Error('Gagal menghapus data.');
                    return response.json();
                })
                .then(data => {
                    // Added for debugging: Log the response data
                    console.log('Response data:', data);
                    if (data.success) {
                        window.location.href = '{{ route('admin.daftarPerusahaan.daftarPerusahaan') }}';
                    } else {
                        // Modified for user feedback: Changed console.error to alert
                        alert('Gagal menghapus: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    // Modified for user feedback: Changed console.error to alert
                    alert('Terjadi kesalahan: ' + error.message);
                });
        });
    </script>
@endsection
