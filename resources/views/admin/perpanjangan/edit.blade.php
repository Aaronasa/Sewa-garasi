@extends('layouts.app')

@section('judul', 'Perpanjang Sewa')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Perpanjang Sewa Garasi <span class="kode">{{ $sewa->garasi->kode }}</span></h1>
            <p>Pilih tanggal selesai baru, sistem akan menghitung sisa biaya yang harus dibayar.</p>
        </div>
        <a href="{{ route('admin.perpanjangan.index') }}" class="tombol tombol-garis">&larr; Kembali ke daftar</a>
    </div>

    <div class="dua-kolom">
        <section class="kartu">
            <h2>Data Sewa Saat Ini</h2>
            <table class="ringkasan">
                <tr>
                    <th>Penyewa</th>
                    <td>{{ $sewa->kendaraan->pemilik->nama }}</td>
                </tr>
                <tr>
                    <th>Kendaraan</th>
                    <td>{{ $sewa->kendaraan->merek_tipe }} {{ $sewa->kendaraan->plat_nomor }}</td>
                </tr>
                <tr>
                    <th>Tanggal Sewa</th>
                    <td>{{ $sewa->tanggal_mulai->format('d-m-Y') }} sampai {{ $sewa->tanggal_selesai->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <th>Total Biaya Saat Ini</th>
                    <td>Rp{{ number_format($sewa->total_biaya, 0, ',', '.') }}</td>
                </tr>
            </table>
        </section>

        <form method="GET" action="{{ route('admin.perpanjangan.edit', $sewa) }}" class="form">
            <h2>Hitung Perpanjangan</h2>

            <div class="isian">
                <label for="tanggal_selesai_baru">Tanggal Selesai Baru</label>
                <input type="date" id="tanggal_selesai_baru" name="tanggal_selesai_baru"
                       value="{{ request('tanggal_selesai_baru') }}"
                       min="{{ $sewa->tanggal_selesai->copy()->addDay()->toDateString() }}" required>
                <small>Paling cepat sehari setelah tanggal selesai sekarang.</small>
                @error('tanggal_selesai_baru')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="tombol-baris">
                <button type="submit">Hitung Sisa Biaya</button>
            </div>
        </form>
    </div>

    @if ($hasil)
        <div class="dua-kolom">
            <section class="kartu">
                <h2>Rincian Perpanjangan</h2>
                <table class="ringkasan">
                    <tr>
                        <th>Tanggal Selesai Baru</th>
                        <td>
                            {{ $hasil['tanggalSelesaiBaru']->format('d-m-Y') }}
                            ({{ $hasil['jumlahHari'] }} hari, {{ $hasil['tipeSewaBaru']->label() }})
                        </td>
                    </tr>
                    <tr>
                        <th>Total Biaya Baru</th>
                        <td>Rp{{ number_format($hasil['totalBaru'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Sudah Dibayar</th>
                        <td>Rp{{ number_format($hasil['sudahDibayar'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total">
                        <th>Sisa yang Harus Dibayar</th>
                        <td>Rp{{ number_format($hasil['sisaBiaya'], 0, ',', '.') }}</td>
                    </tr>
                </table>
            </section>

            <form method="POST" action="{{ route('admin.perpanjangan.update', $sewa) }}" enctype="multipart/form-data" class="form">
                @csrf
                @method('PATCH')

                <h2>Simpan Perpanjangan</h2>

                <input type="hidden" name="tanggal_selesai_baru" value="{{ $hasil['tanggalSelesaiBaru']->toDateString() }}">

                @if ($hasil['sisaBiaya'] > 0)
                    <div class="isian">
                        <label for="bukti_pembayaran">Bukti Pembayaran Sisa Biaya</label>
                        <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/*" required>
                        <small>File gambar, maksimal 2 MB.</small>
                        @error('bukti_pembayaran')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>
                @else
                    <p class="info">Tidak ada sisa biaya, jadi tidak perlu upload bukti pembayaran.</p>
                @endif

                <div class="tombol-baris">
                    <button type="submit">Simpan Perpanjangan</button>
                </div>
            </form>
        </div>
    @endif

    <h2>Riwayat Pembayaran</h2>
    <div class="tabel-wadah">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th class="angka">Jumlah</th>
                    <th>Bukti</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sewa->pembayaran as $pembayaran)
                    <tr>
                        <td>{{ $pembayaran->created_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $pembayaran->jenis->label() }}</td>
                        <td class="angka">Rp{{ number_format($pembayaran->jumlah, 0, ',', '.') }}</td>
                        <td><a href="{{ asset('storage/'.$pembayaran->bukti_pembayaran) }}" target="_blank">Lihat bukti</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
