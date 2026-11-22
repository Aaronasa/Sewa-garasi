@extends('layouts.app')

@section('judul', 'Laporan Sewa')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Laporan Sewa Garasi</h1>
            <p>Sewa dikelompokkan berdasarkan tanggal mulai sewa. Pendapatan adalah total biaya sewa (termasuk perpanjangan dan isi listrik).</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.laporan.index') }}" class="form form-baris">
        <div class="isian">
            <label for="jenis">Jenis Laporan</label>
            <select id="jenis" name="jenis">
                @foreach ($daftarJenis as $kode => $judul)
                    <option value="{{ $kode }}" @selected($jenis === $kode)>{{ $judul }}</option>
                @endforeach
            </select>
            @error('jenis')
                <small class="error">{{ $message }}</small>
            @enderror
        </div>

        <div class="isian">
            <label for="tahun">Tahun</label>
            <input type="number" id="tahun" name="tahun" value="{{ $tahun }}" min="2000" max="2100" required>
            @error('tahun')
                <small class="error">{{ $message }}</small>
            @enderror
        </div>

        <div class="isian">
            <label for="bulan">Bulan (khusus laporan per hari)</label>
            <select id="bulan" name="bulan">
                @foreach ($daftarBulan as $nomor => $nama)
                    <option value="{{ $nomor }}" @selected($bulan === $nomor)>{{ $nama }}</option>
                @endforeach
            </select>
            @error('bulan')
                <small class="error">{{ $message }}</small>
            @enderror
        </div>

        <button type="submit">Tampilkan</button>
    </form>

    <h2>{{ $daftarJenis[$jenis] }} - Tahun {{ $tahun }}</h2>

    @if (empty($baris))
        <p class="kosong">Belum ada data sewa.</p>
    @else
        <div class="tabel-wadah">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>{{ $judulKolom }}</th>
                        <th class="angka">Jumlah Sewa</th>
                        <th class="angka">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td class="angka">{{ $item['jumlahSewa'] }}</td>
                            <td class="angka">Rp{{ number_format($item['pendapatan'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="angka">{{ $totalSewa }}</th>
                        <th class="angka">Rp{{ number_format($totalPendapatan, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
@endsection
