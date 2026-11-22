<?php

namespace App\Http\Controllers;

use App\Enums\TipeGarasi;
use App\Models\Garasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin melihat daftar garasi dan membuat garasi baru secara berkelompok per tipe.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class GarasiController extends Controller
{
    /**
     * Menampilkan semua garasi beserta statusnya hari ini.
     */
    public function index(): View
    {
        $daftarGarasi = Garasi::orderBy('tipe')->orderBy('kode')->get();

        return view('admin.garasi.index', ['daftarGarasi' => $daftarGarasi]);
    }

    /**
     * Menampilkan form pembuatan garasi baru.
     */
    public function create(): View
    {
        return view('admin.garasi.create', ['daftarTipe' => TipeGarasi::cases()]);
    }

    /**
     * Membuat sejumlah garasi baru dengan kode berurutan sesuai tipenya, misalnya C011 sampai C015.
     *
     * @param  Request  $request  berisi tipe, jumlah, harga_harian, dan harga_listrik_per_kwh
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipe' => ['required', Rule::enum(TipeGarasi::class)],
            'jumlah' => ['required', 'integer', 'min:1', 'max:'.TipeGarasi::NOMOR_MAKSIMAL],
            'harga_harian' => ['required', 'integer', 'min:1'],
            'harga_listrik_per_kwh' => ['nullable', 'required_if:tipe,'.TipeGarasi::MobilListrik->value, 'integer', 'min:1'],
        ]);

        $tipe = TipeGarasi::from($data['tipe']);
        $nomorTerakhir = $this->nomorTerakhir($tipe);
        $sisaNomor = TipeGarasi::NOMOR_MAKSIMAL - $nomorTerakhir;

        if ($data['jumlah'] > $sisaNomor) {
            return back()->withInput()
                ->withErrors(['jumlah' => "Garasi tipe ini hanya bisa ditambah {$sisaNomor} lagi (maksimum 999)."]);
        }

        $hargaListrik = $tipe === TipeGarasi::MobilListrik ? $data['harga_listrik_per_kwh'] : null;
        $nomorAwal = $nomorTerakhir + 1;
        $nomorAkhir = $nomorTerakhir + $data['jumlah'];

        DB::transaction(function () use ($tipe, $nomorAwal, $nomorAkhir, $data, $hargaListrik) {
            for ($nomor = $nomorAwal; $nomor <= $nomorAkhir; $nomor++) {
                Garasi::create([
                    'kode' => $tipe->buatKode($nomor),
                    'tipe' => $tipe,
                    'harga_harian' => $data['harga_harian'],
                    'harga_listrik_per_kwh' => $hargaListrik,
                ]);
            }
        });

        return redirect()->route('admin.garasi.index')->with(
            'sukses',
            "{$data['jumlah']} garasi baru dibuat: {$tipe->buatKode($nomorAwal)} sampai {$tipe->buatKode($nomorAkhir)}."
        );
    }

    /**
     * Nomor urut terbesar yang sudah dipakai untuk tipe garasi ini, 0 jika belum ada.
     */
    private function nomorTerakhir(TipeGarasi $tipe): int
    {
        $kodeTerakhir = Garasi::where('tipe', $tipe)->max('kode');

        return $kodeTerakhir === null ? 0 : (int) substr($kodeTerakhir, 1);
    }

    /**
     * Menampilkan form ubah harga garasi.
     */
    public function edit(Garasi $garasi): View
    {
        return view('admin.garasi.edit', ['garasi' => $garasi]);
    }

    /**
     * Mengubah harga garasi. Kode dan tipe tidak bisa diubah karena kode mengikuti tipe dan nomor urut.
     * Harga hanya boleh diubah jika tidak ada sewa yang masih berjalan atau akan datang,
     * karena perpanjangan sewa menghitung ulang biaya dari harga garasi.
     *
     * @param  Request  $request  berisi harga_harian dan harga_listrik_per_kwh
     */
    public function update(Request $request, Garasi $garasi): RedirectResponse
    {
        if ($garasi->adaSewaBelumSelesai()) {
            return back()->with('gagal', "Harga garasi {$garasi->kode} tidak bisa diubah karena masih ada sewa yang berjalan atau akan datang.");
        }

        $isListrik = $garasi->tipe->isListrik();
        $data = $request->validate([
            'harga_harian' => ['required', 'integer', 'min:1'],
            'harga_listrik_per_kwh' => $isListrik ? ['required', 'integer', 'min:1'] : ['nullable'],
        ]);

        $garasi->update([
            'harga_harian' => $data['harga_harian'],
            'harga_listrik_per_kwh' => $isListrik ? $data['harga_listrik_per_kwh'] : null,
        ]);

        return redirect()->route('admin.garasi.index')->with('sukses', "Harga garasi {$garasi->kode} berhasil diubah.");
    }

    /**
     * Menghapus garasi yang belum pernah disewa. Garasi dengan riwayat sewa tidak boleh dihapus
     * supaya data sewa dan laporan tetap utuh.
     */
    public function destroy(Garasi $garasi): RedirectResponse
    {
        if ($garasi->sewa()->exists()) {
            return back()->with('gagal', "Garasi {$garasi->kode} tidak bisa dihapus karena punya riwayat sewa.");
        }

        $garasi->delete();

        return redirect()->route('admin.garasi.index')->with('sukses', "Garasi {$garasi->kode} berhasil dihapus.");
    }
}
