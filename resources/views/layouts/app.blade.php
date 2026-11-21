<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') - Sewa Garasi</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="navbar">
        <a href="{{ route('beranda') }}" class="logo">Sewa Garasi</a>

        @auth
            <nav class="menu">
                    @can('admin')
                    <a href="{{ route('admin.verifikasi.index') }}">Verifikasi Pendaftar</a>
                    <a href="{{ route('admin.garasi.index') }}">Garasi</a>
                    <a href="{{ route('admin.perpanjangan.index') }}">Perpanjangan</a>
                    <a href="{{ route('admin.laporan.index') }}">Laporan</a>
                @endcan
                    @can('penyewa')
                    <a href="{{ route('sewa.index') }}">Sewa Saya</a>
                    <a href="{{ route('sewa.create') }}">Sewa Garasi Baru</a>
                @endcan
                <span>{{ auth()->user()->nama }} ({{ auth()->user()->peran->value }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tombol-kecil">Logout</button>
                </form>

            </nav>
        @endauth
    </header>

    <main class="kontainer">
        @if (session('sukses'))
            <p class="pesan pesan-sukses">{{ session('sukses') }}</p>
        @endif

        @yield('konten')
    </main>
</body>
</html>