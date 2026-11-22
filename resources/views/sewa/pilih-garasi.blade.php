@extends('layouts.app')

@section('judul', 'Pilih Garasi')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Pilih Garasi</h1>
            <p>Pilih salah satu garasi yang tersedia, lalu upload bukti pelunasan.</p>
        </div>
    </div>

    <ol class="langkah">
        <li>1. Data kendaraan dan sewa</li>
        <li class="aktif">2. Pilih garasi dan upload bukti bayar</li>
    </ol>

    <section class="kartu">
        <h2>Ringkasan Sewa</h2>
        <table class="ringkasan">
            <tr>
                <th>Kendaraan</th>
                <td>
                    {{ $tipeKendaraan->label() }} -
                    {{ $data['merek_tipe'] }} {{ $data['plat_nomor'] }}
                </td>
            </tr>
            <tr>
                <th>Tipe Sewa</th>
                <td>{{ $tipeSewa->label() }}</td>
            </tr>
            <tr>
                <th>Tanggal Sewa</th>
                <td>
                    {{ $mulai->format('d-m-Y') }} sampai {{ $selesai->format('d-m-Y') }}
                    ({{ (int) $mulai->diffInDays($selesai) + 1 }} hari)
                </td>
            </tr>
        </table>
    </section>

    @if ($pilihan->isEmpty())
        <p class="kosong">Tidak ada garasi tipe ini yang tersedia pada tanggal tersebut.</p>
        <a href="{{ route('sewa.create') }}" class="tombol tombol-garis">&larr; Kembali ke langkah 1</a>
    @else
        <form method="POST" action="{{ route('sewa.store') }}" enctype="multipart/form-data" class="form form-lebar">
            @csrf

            @foreach ($data as $nama => $nilai)
                <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
            @endforeach

            <h2>Garasi Tersedia</h2>

            <div class="tabel-wadah">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Pilih</th>
                            <th>Kode</th>
                            <th class="angka">Biaya Sewa</th>
                            <th class="angka">Biaya Isi Listrik</th>
                            <th class="angka">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pilihan as $item)
                            <tr>
                                <td>
                                    <input type="radio" name="garasi_id" value="{{ $item['garasi']->id }}"
                                           id="garasi-{{ $item['garasi']->id }}" required>
                                </td>
                                <td><label for="garasi-{{ $item['garasi']->id }}" class="kode">{{ $item['garasi']->kode }}</label></td>
                                <td class="angka">Rp{{ number_format($item['biaya']['sewa'], 0, ',', '.') }}</td>
                                <td class="angka">Rp{{ number_format($item['biaya']['listrik'], 0, ',', '.') }}</td>
                                <td class="angka"><strong>Rp{{ number_format($item['biaya']['total'], 0, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('garasi_id')
                <small class="error">{{ $message }}</small>
            @enderror

            <div class="isian">
                <label for="bukti_pembayaran">Bukti Pelunasan</label>
                <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/*" required>
                <small>File gambar, maksimal 2 MB.</small>
                @error('bukti_pembayaran')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="tombol-baris">
                <a href="{{ route('sewa.create') }}" class="tombol tombol-garis">&larr; Kembali ke langkah 1</a>
                <button type="submit">Simpan Sewa</button>
            </div>
        </form>
    @endif
@endsection
