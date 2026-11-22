@extends('layouts.app')

@section('judul', 'Verifikasi Pendaftar')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Verifikasi Pendaftar Baru</h1>
            <p>Cek kesesuaian NIK dengan KTP penyewa, lalu terima atau tolak pendaftarannya.</p>
        </div>
    </div>

    @if ($pendaftar->isEmpty())
        <p class="kosong">Tidak ada pendaftar yang menunggu verifikasi.</p>
    @else
        <div class="tabel-wadah">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama Lengkap</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendaftar as $user)
                        <tr>
                            <td class="kode">{{ $user->nik }}</td>
                            <td>{{ $user->nama }}</td>
                            <td>{{ $user->telepon }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->created_at->format('d-m-Y H:i') }}</td>
                            <td class="aksi">
                                <form method="POST" action="{{ route('admin.verifikasi.terima', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="tombol-kecil">Terima</button>
                                </form>
                                <form method="POST" action="{{ route('admin.verifikasi.tolak', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="tombol-kecil tombol-bahaya">Tolak</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
