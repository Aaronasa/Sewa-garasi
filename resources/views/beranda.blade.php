@extends('layouts.app')

@section('judul', 'Beranda')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Selamat datang, {{ auth()->user()->nama }}</h1>
            <p>Anda masuk sebagai <strong>{{ auth()->user()->peran->value }}</strong>. Pilih menu di bawah.</p>
        </div>
    </div>

    <div class="grid-menu">
        @can('admin')
            <a href="{{ route('admin.verifikasi.index') }}" class="kartu-menu">
                <strong>Verifikasi Pendaftar Baru</strong>
                <span>Cek NIK dengan KTP penyewa baru, lalu terima atau tolak.</span>
            </a>
            <a href="{{ route('admin.garasi.index') }}" class="kartu-menu">
                <strong>Daftar dan Buat Garasi</strong>
                <span>Lihat semua garasi dan buat garasi baru per tipe.</span>
            </a>
            <a href="{{ route('admin.perpanjangan.index') }}" class="kartu-menu">
                <strong>Perpanjangan Sewa</strong>
                <span>Ubah tanggal selesai garasi yang sedang disewa hari ini.</span>
            </a>
            <a href="{{ route('admin.laporan.index') }}" class="kartu-menu">
                <strong>Laporan Sewa</strong>
                <span>Rekap sewa per bulan, per hari, per tipe garasi, dan per penyewa.</span>
            </a>
        @else
            <a href="{{ route('sewa.create') }}" class="kartu-menu">
                <strong>Sewa Garasi Baru</strong>
                <span>Isi data kendaraan, pilih garasi yang tersedia, lalu upload bukti bayar.</span>
            </a>
            <a href="{{ route('sewa.index') }}" class="kartu-menu">
                <strong>Sewa Saya</strong>
                <span>Lihat riwayat sewa garasi beserta total biayanya.</span>
            </a>
        @endcan
    </div>
@endsection
