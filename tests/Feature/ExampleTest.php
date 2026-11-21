<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Pengunjung yang belum login diarahkan ke halaman login.
     */
    public function test_beranda_belum_login_dialihkan_ke_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
