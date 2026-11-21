<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusPendaftaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mengatur login, logout, dan pendaftaran penyewa baru.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class AuthController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function tampilkanLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses login. Penyewa yang belum diterima admin tidak boleh masuk.
     *
     * @param  Request  $request  berisi email dan password
     */
    public function login(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::validate($kredensial)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $user = User::where('email', $kredensial['email'])->first();

        if (! $user->isDiterima()) {
            $pesan = $user->status === StatusPendaftaran::Ditolak
                ? 'Pendaftaran Anda ditolak oleh admin.'
                : 'Pendaftaran Anda masih menunggu verifikasi admin.';

            return back()->withErrors(['email' => $pesan])->onlyInput('email');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('beranda'));
    }

    /**
     * Menampilkan halaman pendaftaran penyewa.
     */
    public function tampilkanDaftar(): View
    {
        return view('auth.daftar');
    }

    /**
     * Menyimpan pendaftaran penyewa baru dengan status menunggu verifikasi admin.
     *
     * @param  Request  $request  berisi nik, nama, telepon, email, dan password
     */
    public function daftar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'nama' => ['required', 'string', 'max:100'],
            'telepon' => ['required', 'digits_between:10,15'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            ...$data,
            'peran' => Peran::Penyewa,
            'status' => StatusPendaftaran::Menunggu,
        ]);

        return redirect()->route('login')
            ->with('sukses', 'Pendaftaran berhasil. Silakan tunggu verifikasi admin sebelum login.');
    }

    /**
     * Keluar dari aplikasi dan menghapus sesi.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
