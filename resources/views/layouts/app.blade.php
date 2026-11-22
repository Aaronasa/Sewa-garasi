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
                    <a href="{{ route('admin.verifikasi.index') }}" @class(['aktif' => request()->routeIs('admin.verifikasi.*')])>Verifikasi Pendaftar</a>
                    <a href="{{ route('admin.garasi.index') }}" @class(['aktif' => request()->routeIs('admin.garasi.*')])>Garasi</a>
                    <a href="{{ route('admin.perpanjangan.index') }}" @class(['aktif' => request()->routeIs('admin.perpanjangan.*')])>Perpanjangan</a>
                    <a href="{{ route('admin.laporan.index') }}" @class(['aktif' => request()->routeIs('admin.laporan.*')])>Laporan</a>
                @endcan
                @can('penyewa')
                    <a href="{{ route('sewa.index') }}" @class(['aktif' => request()->routeIs('sewa.index')])>Sewa Saya</a>
                    <a href="{{ route('sewa.create') }}" @class(['aktif' => request()->routeIs('sewa.create', 'sewa.pilih-garasi')])>Sewa Garasi Baru</a>
                @endcan

                <div class="pengguna">
                    <span>{{ auth()->user()->nama }} <small>({{ auth()->user()->peran->value }})</small></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="tombol-garis tombol-kecil">Logout</button>
                    </form>
                </div>
            </nav>
        @endauth
    </header>

    <main class="kontainer">
        @if (session('sukses'))
            <p class="pesan pesan-sukses">{{ session('sukses') }}</p>
        @endif

        @yield('konten')
    </main>

    <footer class="kaki">Sewa Garasi &copy; {{ date('Y') }}</footer>
</body>
</html>
