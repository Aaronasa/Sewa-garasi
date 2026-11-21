@extends('layouts.app')

@section('judul', 'Beranda')

@section('konten')
    <h1>Selamat datang, {{ auth()->user()->nama }}</h1>

    @can('admin')
        <p>Anda masuk sebagai <strong>admin</strong>. Pilih menu:</p>
        <ul>
            <li><a href="{{ route('admin.verifikasi.index') }}">Verifikasi Pendaftar Baru</a></li>
            <li><a href="{{ route('admin.garasi.index') }}">Daftar dan Buat Garasi</a></li>
            <li><a href="{{ route('admin.perpanjangan.index') }}">Perpanjangan Sewa</a></li>
            <li><a href="{{ route('admin.laporan.index') }}">Laporan Sewa</a></li>
        </ul>
    @else
        <p>Anda masuk sebagai <strong>penyewa</strong>. Pilih menu:</p>
        <ul>
            <li><a href="{{ route('sewa.create') }}">Sewa Garasi Baru</a></li>
            <li><a href="{{ route('sewa.index') }}">Sewa Saya</a></li>
        </ul>
    @endcan
@endsection