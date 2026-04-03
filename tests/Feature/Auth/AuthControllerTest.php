<?php

use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('test_login_com_credenciais_validas_retorna_token', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'usuario' => [
                'id',
                'nome',
                'email',
                'funcao',
                'brigada_id',
                'criado_em',
            ],
        ])
        ->assertJsonPath('usuario.id', $usuario->id)
        ->assertJsonPath('usuario.email', $usuario->email);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('test_login_com_email_inexistente_retorna_401', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'naoexiste@example.com',
        'senha' => 'qualquer123',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Credenciais inválidas.',
        ]);
});

test('test_login_com_senha_incorreta_retorna_401', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'outrasenha99',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Credenciais inválidas.',
        ]);
});

test('test_login_com_campos_ausentes_retorna_422', function () {
    $this->postJson('/api/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'senha']);
});

test('test_login_registra_log_de_auditoria', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ])->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'login')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $usuario->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->dados_json)->toHaveKeys(['ip', 'user_agent']);
});

test('test_logout_revoga_token_atual', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $token = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ])->assertOk()->json('token');

    $this->postJson('/api/auth/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertNoContent();

    expect(DB::table('personal_access_tokens')->count())->toBe(0);

    Auth::forgetGuards();

    $this->getJson('/api/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
});

test('test_logout_sem_autenticacao_retorna_401', function () {
    $this->postJson('/api/auth/logout')->assertUnauthorized();
});

test('test_me_retorna_usuario_autenticado', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $token = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ])->assertOk()->json('token');

    $this->getJson('/api/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $usuario->id)
        ->assertJsonPath('data.email', $usuario->email);
});

test('test_me_nao_expoe_senha_hash', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $token = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ])->assertOk()->json('token');

    $json = $this->getJson('/api/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()->json();

    expect(json_encode($json))->not->toContain('senha_hash');
});

test('test_me_nao_expoe_cpf', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $token = $this->postJson('/api/auth/login', [
        'email' => $usuario->email,
        'senha' => 'senha12345',
    ])->assertOk()->json('token');

    $json = $this->getJson('/api/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()->json();

    expect(json_encode($json))->not->toContain('cpf');
});

test('test_me_sem_autenticacao_retorna_401', function () {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});
