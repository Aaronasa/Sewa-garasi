@extends('layouts.app')

@section('judul', 'Login')

@section('konten')
    <h1>Login</h1>

    <form method="POST" action="{{ route('login.proses') }}" class="form">
        @csrf

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <small class="error">{{ $message }}</small>
        @enderror

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        @error('password')
            <small class="error">{{ $message }}</small>
        @enderror

        <button type="submit">Login</button>
    </form>

    <p>Belum punya akun? <a href="{{ route('daftar') }}">Daftar sebagai penyewa</a></p>
@endsection
