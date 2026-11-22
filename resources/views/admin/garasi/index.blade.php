@extends('layouts.app')

@section('judul', 'Daftar Garasi')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Daftar Garasi</h1>
            <p>{{ $daftarGarasi->count() }} garasi terdaftar, lengkap dengan harga dan status sewa hari ini.</p>
        </div>
        <a href="{{ route('admin.garasi.create') }}" class="tombol">+ Buat Garasi Baru</a>
    </div>

    @if ($daftarGarasi->isEmpty())
        <p class="kosong">Belum ada garasi. Klik <strong>Buat Garasi Baru</strong> untuk menambah garasi.</p>
    @else
        <div class="tabel-wadah">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th class="angka">Harga Harian</th>
                        <th class="angka">Harga Bulanan</th>
                        <th class="angka">Harga Listrik / kWh</th>
                        <th>Status Hari Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarGarasi as $garasi)
                        <tr>
                            <td class="kode">{{ $garasi->kode }}</td>
                            <td>{{ $garasi->tipe->label() }}</td>
                            <td class="angka">Rp{{ number_format($garasi->harga_harian, 0, ',', '.') }}</td>
                            <td class="angka">Rp{{ number_format($garasi->hargaBulanan(), 0, ',', '.') }}</td>
                            <td class="angka">
                                @if ($garasi->harga_listrik_per_kwh)
                                    Rp{{ number_format($garasi->harga_listrik_per_kwh, 0, ',', '.') }}
                                @else
                                    <span class="redup">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($garasi->statusHariIni() === $garasi::STATUS_TERSEDIA)
                                    <span class="badge badge-hijau">Tersedia</span>
                                @else
                                    <span class="badge badge-oranye">Sedang Disewa</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
