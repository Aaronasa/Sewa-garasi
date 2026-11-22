@extends('layouts.app')

@section('judul', 'Sewa Garasi Baru')

@section('konten')
    <div class="kepala-halaman">
        <div>
            <h1>Sewa Garasi Baru</h1>
            <p>Isi data kendaraan dan data sewa, lalu cari garasi yang tersedia.</p>
        </div>
    </div>

    <ol class="langkah">
        <li class="aktif">1. Data kendaraan dan sewa</li>
        <li>2. Pilih garasi dan upload bukti bayar</li>
    </ol>

    <form method="GET" action="{{ route('sewa.pilih-garasi') }}" class="form form-lebar">
        <div class="grid-2">
            <fieldset>
                <legend>Data Kendaraan</legend>

                <div class="isian">
                    <label for="tipe_kendaraan">Tipe Kendaraan</label>
                    <select id="tipe_kendaraan" name="tipe_kendaraan" required>
                        @foreach ($daftarTipeKendaraan as $tipe)
                            <option value="{{ $tipe->value }}" @selected(old('tipe_kendaraan') === $tipe->value)>{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                    @error('tipe_kendaraan')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="plat_nomor">Plat Nomor</label>
                    <input type="text" id="plat_nomor" name="plat_nomor" value="{{ old('plat_nomor') }}" maxlength="15">
                    <small>Kosongkan jika kendaraan sepeda.</small>
                    @error('plat_nomor')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="merek_tipe">Merek dan Tipe</label>
                    <input type="text" id="merek_tipe" name="merek_tipe" value="{{ old('merek_tipe') }}" maxlength="100" required>
                    @error('merek_tipe')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="grid-2">
                    <div class="isian">
                        <label for="kapasitas_baterai_kwh">Kapasitas Baterai (kWh)</label>
                        <input type="number" id="kapasitas_baterai_kwh" name="kapasitas_baterai_kwh"
                               value="{{ old('kapasitas_baterai_kwh') }}" min="0.01" step="0.01">
                        @error('kapasitas_baterai_kwh')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="isian">
                        <label for="sisa_baterai_kwh">Sisa Baterai (kWh)</label>
                        <input type="number" id="sisa_baterai_kwh" name="sisa_baterai_kwh"
                               value="{{ old('sisa_baterai_kwh') }}" min="0" step="0.01">
                        @error('sisa_baterai_kwh')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <small>Kapasitas dan sisa baterai hanya diisi untuk mobil listrik.</small>
            </fieldset>

            <fieldset>
                <legend>Data Sewa</legend>

                <div class="isian">
                    <label for="tipe_sewa">Tipe Sewa</label>
                    <select id="tipe_sewa" name="tipe_sewa" required>
                        @foreach ($daftarTipeSewa as $tipe)
                            <option value="{{ $tipe->value }}" @selected(old('tipe_sewa') === $tipe->value)>{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                    @error('tipe_sewa')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="jumlah_hari">Jumlah Hari</label>
                    <input type="number" id="jumlah_hari" name="jumlah_hari" value="{{ old('jumlah_hari') }}" min="1" max="30">
                    <small>Hanya untuk sewa harian, maksimal jumlah hari di bulan mulai dikurangi 1. Sewa bulanan = 1 bulan.</small>
                    @error('jumlah_hari')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                           value="{{ old('tanggal_mulai', $hariIni) }}" min="{{ $hariIni }}" required>
                    @error('tanggal_mulai')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="isian">
                    <label for="tipe_garasi">Tipe Garasi</label>
                    <select id="tipe_garasi" name="tipe_garasi" required>
                        @foreach ($daftarTipeGarasi as $tipe)
                            <option value="{{ $tipe->value }}" @selected(old('tipe_garasi') === $tipe->value)>{{ $tipe->label() }}</option>
                        @endforeach
                    </select>
                    <small>Sepeda dan sepeda motor: garasi sepeda. Mobil: garasi mobil. Mobil listrik: garasi mobil atau mobil listrik.</small>
                    @error('tipe_garasi')
                        <small class="error">{{ $message }}</small>
                    @enderror
                </div>
            </fieldset>
        </div>

        <div class="tombol-baris">
            <button type="submit">Cari Garasi Tersedia &rarr;</button>
        </div>
    </form>
@endsection
