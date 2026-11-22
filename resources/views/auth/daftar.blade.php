@extends('layouts.app')

@section('judul', 'Daftar Penyewa')

@section('konten')
    <div class="kartu-tamu kartu-tamu-lebar">
        <h1>Daftar sebagai Penyewa</h1>
        <p class="sub">Setelah mendaftar, tunggu admin memverifikasi KTP Anda sebelum login.</p>

        <form method="POST" action="{{ route('daftar.proses') }}" class="form">
            @csrf

            <div class="grid-2">
                <div class="isian">
                    <label for="nik">NIK (Nomor KTP)</label>
                    <input type="text" id="nik" name="nik" value="{{ old('nik') }}" inputmode="numeric"
                           pattern="[0-9]{16}" maxlength="16" title="NIK harus 16 digit angka" required>
                    @error('nik')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" maxlength="100" required>
                    @error('nama')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="telepon">Nomor Telepon</label>
                    <input type="tel" id="telepon" name="telepon" value="{{ old('telepon') }}"
                           pattern="[0-9]{10,15}" title="Nomor telepon 10 sampai 15 digit angka" required>
                    @error('telepon')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" required>
                    @error('email')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="isian">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>
                <small>Minimal 8 karakter.</small>
                @error('password')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <button type="submit">Daftar</button>
        </form>

        <p class="tautan-bawah">Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
    </div>
@endsection
