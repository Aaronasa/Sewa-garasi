@extends('layouts.app')

@section('judul', 'Perpanjangan Sewa')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Garasi yang Sedang Disewa Hari Ini</h1>
            <p>Pilih garasi yang akan diperpanjang sewanya.</p>
        </div>
    </div>

    @if ($daftarSewa->isEmpty())
        <p class="kosong">Tidak ada garasi yang sedang disewa hari ini.</p>
    @else
        <div class="tabel-wadah">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Garasi</th>
                        <th>Penyewa</th>
                        <th>Kendaraan</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="angka">Total Biaya</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarSewa as $sewa)
                        <tr>
                            <td class="kode">{{ $sewa->garasi->kode }}</td>
                            <td>{{ $sewa->kendaraan->pemilik->nama }}</td>
                            <td>
                                {{ $sewa->kendaraan->merek_tipe }}
                                <span class="redup">{{ $sewa->kendaraan->plat_nomor }}</span>
                            </td>
                            <td>{{ $sewa->tanggal_mulai->format('d-m-Y') }}</td>
                            <td>{{ $sewa->tanggal_selesai->format('d-m-Y') }}</td>
                            <td class="angka">Rp{{ number_format($sewa->total_biaya, 0, ',', '.') }}</td>
                            <td><a href="{{ route('admin.perpanjangan.edit', $sewa) }}" class="tombol tombol-kecil">Perpanjang</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
