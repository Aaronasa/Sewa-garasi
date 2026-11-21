<?php

namespace Database\Factories;

use App\Enums\Peran;
use App\Enums\StatusPendaftaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('################'),
            'nama' => fake()->name(),
            'telepon' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'peran' => Peran::Penyewa,
            'status' => StatusPendaftaran::Menunggu,
        ];
    }

    /**
     * Pengguna dengan peran admin (admin tidak punya NIK dan langsung diterima).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'nik' => null,
            'peran' => Peran::Admin,
            'status' => StatusPendaftaran::Diterima,
        ]);
    }

    /**
     * Penyewa yang pendaftarannya sudah diterima admin.
     */
    public function diterima(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPendaftaran::Diterima,
        ]);
    }
}
