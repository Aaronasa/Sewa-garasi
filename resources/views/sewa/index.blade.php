@extends('layouts.app')

@section('judul', 'Sewa Saya')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Sewa Saya</h1>
            <p>Riwayat sewa garasi Anda.</p>
        </div>
        <a href="{{ route('sewa.create') }}" class="tombol">+ Sewa Garasi Baru</a>
    </div>

    @if ($daftarSewa->isEmpty())
        <p class="kosong">Anda belum punya sewa garasi.</p>
    @else
        <div class="tabel-wadah">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Garasi</th>
                        <th>Kendaraan</th>
                        <th>Tipe Sewa</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="angka">Total Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarSewa as $sewa)
                        <tr>
                            <td class="kode">{{ $sewa->garasi->kode }}</td>
                            <td>
                                {{ $sewa->kendaraan->merek_tipe }}
                                <span class="redup">{{ $sewa->kendaraan->plat_nomor }}</span>
                            </td>
                            <td>{{ $sewa->tipe_sewa->label() }}</td>
                            <td>{{ $sewa->tanggal_mulai->format('d-m-Y') }}</td>
                            <td>{{ $sewa->tanggal_selesai->format('d-m-Y') }}</td>
                            <td class="angka">Rp{{ number_format($sewa->total_biaya, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
