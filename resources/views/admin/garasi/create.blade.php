@extends('layouts.app')

@section('judul', 'Buat Garasi')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Buat Garasi Baru</h1>
            <p>Buat beberapa garasi sekaligus dengan tipe dan harga yang sama.</p>
        </div>
        <a href="{{ route('admin.garasi.index') }}" class="tombol tombol-garis">&larr; Kembali</a>
    </div>

    <p class="info">
        Kode garasi dibuat otomatis: <strong>S</strong> untuk sepeda, <strong>C</strong> untuk mobil,
        <strong>E</strong> untuk mobil listrik, lalu 3 digit nomor urut.
    </p>

    <form method="POST" action="{{ route('admin.garasi.store') }}" class="form">
        @csrf

        <div class="grid-2">
            <div class="isian">
                <label for="tipe">Tipe Garasi</label>
                <select id="tipe" name="tipe" required>
                    @foreach ($daftarTipe as $tipe)
                        <option value="{{ $tipe->value }}" @selected(old('tipe') === $tipe->value)>{{ $tipe->label() }}</option>
                    @endforeach
                </select>
                @error('tipe')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="isian">
                <label for="jumlah">Jumlah Garasi</label>
                <input type="number" id="jumlah" name="jumlah" value="{{ old('jumlah', 1) }}" min="1" max="999" required>
                @error('jumlah')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="isian">
                <label for="harga_harian">Harga Sewa Harian (Rp)</label>
                <input type="number" id="harga_harian" name="harga_harian" value="{{ old('harga_harian') }}" min="1" required>
                <small>Harga bulanan otomatis 25 x harga harian.</small>
                @error('harga_harian')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="isian">
                <label for="harga_listrik_per_kwh">Harga Listrik per kWh (Rp)</label>
                <input type="number" id="harga_listrik_per_kwh" name="harga_listrik_per_kwh"
                       value="{{ old('harga_listrik_per_kwh') }}" min="1">
                <small>Hanya diisi untuk garasi mobil listrik.</small>
                @error('harga_listrik_per_kwh')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="tombol-baris">
            <button type="submit">Simpan</button>
        </div>
    </form>
@endsection
