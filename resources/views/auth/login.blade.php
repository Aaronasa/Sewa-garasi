@extends('layouts.app')

@section('judul', 'Login')

@section('konten')
    <div class="kartu-tamu">
        <h1>Login</h1>
        <p class="sub">Masuk ke akun Sewa Garasi Anda.</p>

        <form method="POST" action="{{ route('login.proses') }}" class="form">
            @csrf

            <div class="isian">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="nama@email.com" required autofocus>
                @error('email')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <div class="isian">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password')
                    <small class="error">{{ $message }}</small>
                @enderror
            </div>

            <button type="submit">Login</button>
        </form>

        <p class="tautan-bawah">Belum punya akun? <a href="{{ route('daftar') }}">Daftar sebagai penyewa</a></p>
    </div>
@endsection
