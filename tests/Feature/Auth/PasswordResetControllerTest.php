<?php

use App\Mail\RecuperacaoSenhaMail;
use App\Models\LogAuditoria;
use App\Models\TokenRecuperacaoSenha;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('test_envia_token_para_email_existente', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create();

    $response = $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Se o email estiver cadastrado, você receberá instruções para redefinir a senha.',
        ]);

    expect(TokenRecuperacaoSenha::query()->where('usuario_id', $usuario->id)->where('usado', false)->count())->toBe(1);

    Mail::assertQueued(RecuperacaoSenhaMail::class);
});

test('test_retorna_200_para_email_inexistente_sem_revelar_existencia', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create();

    $respostaInexistente = $this->postJson('/api/auth/senha/esqueci', [
        'email' => 'naoexiste@example.com',
    ]);

    Mail::assertNothingQueued();

    $respostaExistente = $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ]);

    $respostaInexistente->assertOk();
    $respostaExistente->assertOk();

    expect($respostaInexistente->json())->toBe($respostaExistente->json());

    Mail::assertQueued(RecuperacaoSenhaMail::class);
});

test('test_invalida_tokens_anteriores_ao_gerar_novo', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create();

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => 'token-antigo-fixo',
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ])->assertOk();

    $antigo = TokenRecuperacaoSenha::query()->where('token', 'token-antigo-fixo')->first();
    expect($antigo)->not->toBeNull()
        ->and($antigo->usado)->toBeTrue();

    expect(TokenRecuperacaoSenha::query()->where('usuario_id', $usuario->id)->where('usado', false)->count())->toBe(1);
});

test('test_token_expira_em_30_minutos', function () {
    Mail::fake();

    $this->freezeSecond();

    $usuario = Usuario::factory()->create();

    $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ])->assertOk();

    $token = TokenRecuperacaoSenha::query()
        ->where('usuario_id', $usuario->id)
        ->where('usado', false)
        ->first();

    expect($token)->not->toBeNull()
        ->and($token->expira_em->equalTo(now()->addMinutes(30)))->toBeTrue();
});

test('test_email_de_recuperacao_e_enfileirado', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create();

    $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ])->assertOk();

    Mail::assertQueued(RecuperacaoSenhaMail::class, function (RecuperacaoSenhaMail $mail) use ($usuario): bool {
        return $mail->usuario->is($usuario)
            && $mail->tokenPlano !== '';
    });
});

test('test_registra_log_de_auditoria_na_solicitacao', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create();

    $this->postJson('/api/auth/senha/esqueci', [
        'email' => $usuario->email,
    ])->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'solicitacao_reset_senha')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $usuario->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_redefine_senha_com_token_valido', function () {
    Mail::fake();

    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha-antiga-8'),
    ]);

    $tokenPlano = 'token-valido-para-reset';

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => $tokenPlano,
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $response = $this->postJson('/api/auth/senha/redefinir', [
        'token' => $tokenPlano,
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Senha redefinida com sucesso.',
        ]);

    $usuario->refresh();

    expect(Hash::check('nova-senha-8', $usuario->senha_hash))->toBeTrue();
});

test('test_retorna_422_com_token_invalido', function () {
    $usuario = Usuario::factory()->create();

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => 'token-real',
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => 'token-errado',
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Token de recuperação inválido ou expirado.',
        ]);
});

test('test_retorna_422_com_token_expirado', function () {
    $usuario = Usuario::factory()->create();

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => 'token-expirado',
        'expira_em' => now()->subMinute(),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => 'token-expirado',
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Token de recuperação inválido ou expirado.',
        ]);
});

test('test_retorna_422_quando_email_nao_bate_com_token', function () {
    $usuario = Usuario::factory()->create();

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => 'token-email',
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => 'token-email',
        'email' => 'outro@example.com',
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'O email informado não corresponde a este token.',
        ]);
});

test('test_marca_token_como_usado_apos_redefinicao', function () {
    $usuario = Usuario::factory()->create();

    $tokenPlano = 'token-uso-unico';

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => $tokenPlano,
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => $tokenPlano,
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])->assertOk();

    $registro = TokenRecuperacaoSenha::query()->where('token', $tokenPlano)->first();
    expect($registro->usado)->toBeTrue();
});

test('test_revoga_todos_tokens_sanctum_apos_redefinicao', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senha12345'),
    ]);

    $usuario->createToken('brasa');
    $usuario->createToken('outro');

    $tokenPlano = 'token-reset-sanctum';

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => $tokenPlano,
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => $tokenPlano,
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])->assertOk();

    $usuario->refresh();

    expect($usuario->tokens()->count())->toBe(0);
});

test('test_registra_log_de_auditoria_na_conclusao', function () {
    $usuario = Usuario::factory()->create();

    $tokenPlano = 'token-audit-final';

    TokenRecuperacaoSenha::query()->create([
        'usuario_id' => $usuario->id,
        'token' => $tokenPlano,
        'expira_em' => now()->addMinutes(30),
        'usado' => false,
    ]);

    $this->postJson('/api/auth/senha/redefinir', [
        'token' => $tokenPlano,
        'email' => $usuario->email,
        'senha' => 'nova-senha-8',
        'senha_confirmation' => 'nova-senha-8',
    ])->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'reset_senha_concluido')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $usuario->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
