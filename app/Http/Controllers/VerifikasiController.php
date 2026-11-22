<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusPendaftaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin memverifikasi penyewa baru: menerima atau menolak pendaftaran setelah cek KTP.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class VerifikasiController extends Controller
{
    /**
     * Menampilkan daftar penyewa yang masih menunggu verifikasi.
     */
    public function index(): View
    {
        $pendaftar = User::where('peran', Peran::Penyewa)
            ->where('status', StatusPendaftaran::Menunggu)
            ->orderBy('created_at')
            ->get();

        return view('admin.verifikasi.index', ['pendaftar' => $pendaftar]);
    }

    /**
     * Menerima pendaftaran penyewa sehingga penyewa bisa login.
     */
    public function terima(User $user): RedirectResponse
    {
        return $this->ubahStatus($user, StatusPendaftaran::Diterima, 'diterima');
    }

    /**
     * Menolak pendaftaran penyewa sehingga penyewa tidak bisa login.
     */
    public function tolak(User $user): RedirectResponse
    {
        return $this->ubahStatus($user, StatusPendaftaran::Ditolak, 'ditolak');
    }

    /**
     * Mengubah status pendaftar. Hanya penyewa yang masih menunggu yang boleh diubah.
     */
    private function ubahStatus(User $user, StatusPendaftaran $statusBaru, string $keterangan): RedirectResponse
    {
        abort_unless($user->status === StatusPendaftaran::Menunggu, 404);

        $user->update(['status' => $statusBaru]);

        return redirect()->route('admin.verifikasi.index')
            ->with('sukses', "Pendaftaran {$user->nama} {$keterangan}.");
    }
}
