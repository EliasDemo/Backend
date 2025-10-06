<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
});

it('falla al intentar login con credenciales incorrectas', function () {
    $this->postJson('/api/login', [
        'username' => 'testuser',
        'password' => 'wrong-password',
    ])->assertStatus(401)
      ->assertJson(['message' => 'Las credenciales no son correctas.']);
});

it('login correcto devuelve token y user', function () {
    $this->postJson('/api/login', [
        'username' => 'testuser',
        'password' => 'password123',
    ])->assertStatus(200)
      ->assertJsonStructure([
          'message',
          'user' => ['id', 'username', 'email'], // <- ajusta a tu UserResource
          'roles',
          'token',
      ]);
});

it('bloquea login si el usuario está suspendido', function () {
    // ⚠️ Ajusta este estado a tu esquema real (boolean o timestamp):
    $suspended = User::factory()->create([
        'username' => 'suspended_user',
        'password' => Hash::make('password123'),
        // 'suspended' => true,
        // 'suspended_at' => now(),
    ]);

    $this->postJson('/api/login', [
        'username' => 'suspended_user',
        'password' => 'password123',
    ])->assertStatus(423)
      ->assertJson(['message' => 'Tu cuenta está suspendida.']);
});

it('puede cerrar sesión y revocar token', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/logout')
         ->assertStatus(200)
         ->assertJson(['message' => 'Sesión cerrada correctamente']);
});
