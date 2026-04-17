<?php

use App\Models\Brigada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function usuarioAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->administrador()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
    ];
}

test('test_lista_usuarios_paginados', function () {
    $autor = Usuario::factory()->administrador()->create();
    Usuario::factory()->count(24)->create();

    $response = $this->getJson('/api/usuarios', usuarioAuthHeaders($autor));

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_usuarios_por_funcao', function () {
    Usuario::factory()->create(['funcao' => 'brigadista']);
    Usuario::factory()->administrador()->create();

    $response = $this->getJson('/api/usuarios?funcao=administrador', usuarioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('funcao')->unique()->all())->toBe(['administrador']);
});

test('test_filtra_usuarios_por_brigada', function () {
    $brigada = Brigada::factory()->create();
    Usuario::factory()->create(['brigada_id' => $brigada->id]);
    Usuario::factory()->create(['brigada_id' => null]);

    $response = $this->getJson('/api/usuarios?brigada_id='.$brigada->id, usuarioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.brigada_id'))->toBe($brigada->id);
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/usuarios')->assertUnauthorized();
});

test('test_retorna_usuario_com_brigada', function () {
    $brigada = Brigada::factory()->create();
    $usuario = Usuario::factory()->create(['brigada_id' => $brigada->id]);

    $response = $this->getJson('/api/usuarios/'.$usuario->id, usuarioAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $usuario->id)
        ->assertJsonPath('data.brigada_id', $brigada->id);
});

test('test_retorna_404_para_usuario_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/usuarios/'.$id, usuarioAuthHeaders())
        ->assertNotFound();
});

test('test_nao_expoe_senha_hash', function () {
    $usuario = Usuario::factory()->create();

    $json = $this->getJson('/api/usuarios/'.$usuario->id, usuarioAuthHeaders())
        ->assertOk()
        ->json();

    expect(json_encode($json))->not->toContain('senha_hash');
});

test('test_nao_expoe_cpf', function () {
    $usuario = Usuario::factory()->create();

    $json = $this->getJson('/api/usuarios/'.$usuario->id, usuarioAuthHeaders())
        ->assertOk()
        ->json();

    expect(json_encode($json))->not->toContain('cpf');
});

test('test_cria_usuario_com_dados_validos', function () {
    $brigada = Brigada::factory()->create();

    $payload = [
        'nome' => 'Usuário Novo',
        'email' => 'novo@example.com',
        'cpf' => '12345678901',
        'senha' => 'senha12345',
        'senha_confirmation' => 'senha12345',
        'funcao' => 'brigadista',
        'brigada_id' => $brigada->id,
    ];

    $response = $this->postJson('/api/usuarios', $payload, usuarioAuthHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Usuário Novo')
        ->assertJsonPath('data.email', 'novo@example.com')
        ->assertJsonPath('data.funcao', 'brigadista')
        ->assertJsonPath('data.brigada_id', $brigada->id);

    $this->assertDatabaseHas('usuarios', [
        'email' => 'novo@example.com',
        'cpf' => '12345678901',
    ]);
});

test('test_retorna_422_com_email_duplicado', function () {
    Usuario::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/usuarios', [
        'nome' => 'X',
        'email' => 'dup@example.com',
        'cpf' => '12345678901',
        'senha' => 'senha12345',
        'senha_confirmation' => 'senha12345',
        'funcao' => 'brigadista',
        'brigada_id' => null,
    ], usuarioAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('test_retorna_422_com_cpf_duplicado', function () {
    Usuario::factory()->create(['cpf' => '12345678901']);

    $this->postJson('/api/usuarios', [
        'nome' => 'X',
        'email' => 'ok@example.com',
        'cpf' => '12345678901',
        'senha' => 'senha12345',
        'senha_confirmation' => 'senha12345',
        'funcao' => 'brigadista',
        'brigada_id' => null,
    ], usuarioAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cpf']);
});

test('test_senha_e_armazenada_como_hash', function () {
    $this->postJson('/api/usuarios', [
        'nome' => 'X',
        'email' => 'hash@example.com',
        'cpf' => '99988877766',
        'senha' => 'senha12345',
        'senha_confirmation' => 'senha12345',
        'funcao' => 'brigadista',
        'brigada_id' => null,
    ], usuarioAuthHeaders())->assertCreated();

    $usuario = Usuario::query()->where('email', 'hash@example.com')->first();

    expect($usuario)->not->toBeNull()
        ->and(Hash::check('senha12345', $usuario->senha_hash))->toBeTrue();
});

test('test_registra_log_de_auditoria_na_criacao', function () {
    $autor = Usuario::factory()->administrador()->create();

    $this->postJson('/api/usuarios', [
        'nome' => 'X',
        'email' => 'audit-create@example.com',
        'cpf' => '11122233344',
        'senha' => 'senha12345',
        'senha_confirmation' => 'senha12345',
        'funcao' => 'administrador',
        'brigada_id' => null,
    ], usuarioAuthHeaders($autor))->assertCreated();

    $criado = Usuario::query()->where('email', 'audit-create@example.com')->first();

    expect($criado)->not->toBeNull();

    $log = LogAuditoria::query()
        ->where('acao', 'criacao_usuario')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $criado->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($autor->id);
});

test('test_atualiza_dados_do_usuario', function () {
    $usuario = Usuario::factory()->create(['nome' => 'Nome Antigo']);

    $this->putJson('/api/usuarios/'.$usuario->id, [
        'nome' => 'Nome Novo',
    ], usuarioAuthHeaders())->assertOk()
        ->assertJsonPath('data.nome', 'Nome Novo');

    expect($usuario->fresh()->nome)->toBe('Nome Novo');
});

test('test_update_ignora_campo_senha', function () {
    $usuario = Usuario::factory()->create([
        'senha_hash' => Hash::make('senhaAntiga123'),
    ]);

    $this->putJson('/api/usuarios/'.$usuario->id, [
        'nome' => 'Nome Novo',
        'senha' => 'senhaNova12345',
        'senha_confirmation' => 'senhaNova12345',
    ], usuarioAuthHeaders())->assertOk();

    $usuario->refresh();
    expect(Hash::check('senhaAntiga123', $usuario->senha_hash))->toBeTrue();
});

test('test_update_ignora_campo_funcao', function () {
    $usuario = Usuario::factory()->create(['funcao' => 'brigadista']);

    $this->putJson('/api/usuarios/'.$usuario->id, [
        'nome' => 'Nome Novo',
        'funcao' => 'administrador',
    ], usuarioAuthHeaders())->assertOk();

    expect($usuario->fresh()->funcao->value)->toBe('brigadista');
});

test('test_retorna_404_para_usuario_inexistente_no_update', function () {
    $id = (string) Str::uuid();

    $this->putJson('/api/usuarios/'.$id, [
        'nome' => 'X',
    ], usuarioAuthHeaders())->assertNotFound();
});

test('test_remove_usuario_sem_incendios', function () {
    $usuario = Usuario::factory()->create();

    $this->deleteJson('/api/usuarios/'.$usuario->id, [], usuarioAuthHeaders())
        ->assertNoContent();

    $this->assertSoftDeleted('usuarios', ['id' => $usuario->id]);
});

test('test_retorna_403_ao_remover_proprio_usuario', function () {
    $autor = Usuario::factory()->create();

    $this->deleteJson('/api/usuarios/'.$autor->id, [], usuarioAuthHeaders($autor))
        ->assertForbidden();
});

test('test_retorna_409_ao_remover_usuario_com_incendios', function () {
    $usuario = Usuario::factory()->create();
    $areaId = (string) Str::uuid();

    DB::table('areas_monitoradas')->insert([
        'id' => $areaId,
        'nome' => 'Area X',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);

    Incendio::query()->create([
        'latitude' => -16.5,
        'longitude' => -56.75,
        'usuario_id' => $usuario->id,
        'area_id' => $areaId,
        'local_critico_id' => null,
        'deteccao_satelite_id' => null,
    ]);

    $this->deleteJson('/api/usuarios/'.$usuario->id, [], usuarioAuthHeaders())
        ->assertStatus(409)
        ->assertJsonStructure(['message']);
});

test('test_revoga_tokens_sanctum_ao_remover_usuario', function () {
    $usuario = Usuario::factory()->create();
    $usuario->createToken('a');
    $usuario->createToken('b');

    expect(DB::table('personal_access_tokens')->where('tokenable_id', $usuario->id)->count())->toBe(2);

    $this->deleteJson('/api/usuarios/'.$usuario->id, [], usuarioAuthHeaders())
        ->assertNoContent();

    expect(DB::table('personal_access_tokens')->where('tokenable_id', $usuario->id)->count())->toBe(0);
});

test('test_registra_log_de_auditoria_na_remocao', function () {
    $autor = Usuario::factory()->administrador()->create();
    $alvo = Usuario::factory()->create();

    $this->deleteJson('/api/usuarios/'.$alvo->id, [], usuarioAuthHeaders($autor))
        ->assertNoContent();

    $log = LogAuditoria::query()
        ->where('acao', 'remocao_usuario')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $alvo->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($autor->id);
});

test('test_atualiza_funcao_do_usuario', function () {
    $usuario = Usuario::factory()->create(['funcao' => 'brigadista']);

    $this->patchJson('/api/usuarios/'.$usuario->id.'/funcao', [
        'funcao' => 'gestor',
    ], usuarioAuthHeaders())->assertOk()
        ->assertJsonPath('data.funcao', 'gestor');

    expect($usuario->fresh()->funcao->value)->toBe('gestor');
});

test('test_retorna_403_ao_alterar_propria_funcao', function () {
    $autor = Usuario::factory()->administrador()->create();

    $this->patchJson('/api/usuarios/'.$autor->id.'/funcao', [
        'funcao' => 'brigadista',
    ], usuarioAuthHeaders($autor))->assertForbidden();
});

test('test_registra_funcao_anterior_e_nova_no_log', function () {
    $alvo = Usuario::factory()->create(['funcao' => 'brigadista']);
    $autor = Usuario::factory()->administrador()->create();

    $this->patchJson('/api/usuarios/'.$alvo->id.'/funcao', [
        'funcao' => 'administrador',
    ], usuarioAuthHeaders($autor))->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'atualizacao_funcao_usuario')
        ->where('entidade_tipo', 'usuarios')
        ->where('entidade_id', $alvo->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->dados_json)->toHaveKeys(['funcao_anterior', 'funcao_nova'])
        ->and($log->dados_json['funcao_anterior'])->toBe('brigadista')
        ->and($log->dados_json['funcao_nova'])->toBe('administrador');
});

test('test_vincula_usuario_a_brigada', function () {
    $brigada = Brigada::factory()->create();
    $usuario = Usuario::factory()->create(['brigada_id' => null]);

    $this->patchJson('/api/usuarios/'.$usuario->id.'/brigada', [
        'brigada_id' => $brigada->id,
    ], usuarioAuthHeaders())->assertOk()
        ->assertJsonPath('data.brigada_id', $brigada->id);

    expect($usuario->fresh()->brigada_id)->toBe($brigada->id);
});

test('test_desvincula_usuario_de_brigada', function () {
    $brigada = Brigada::factory()->create();
    $usuario = Usuario::factory()->create(['brigada_id' => $brigada->id]);

    $this->patchJson('/api/usuarios/'.$usuario->id.'/brigada', [
        'brigada_id' => null,
    ], usuarioAuthHeaders())->assertOk()
        ->assertJsonPath('data.brigada_id', null);

    expect($usuario->fresh()->brigada_id)->toBeNull();
});

test('test_retorna_422_com_brigada_inexistente', function () {
    $usuario = Usuario::factory()->create();
    $id = (string) Str::uuid();

    $this->patchJson('/api/usuarios/'.$usuario->id.'/brigada', [
        'brigada_id' => $id,
    ], usuarioAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['brigada_id']);
});

test('gestor pode alterar funcao para brigadista', function () {
    $gestor = Usuario::factory()->gestor()->create();
    $alvo = Usuario::factory()->create(['funcao' => 'user']);

    $this->patchJson('/api/usuarios/'.$alvo->id.'/funcao', [
        'funcao' => 'brigadista',
    ], usuarioAuthHeaders($gestor))->assertOk()
        ->assertJsonPath('data.funcao', 'brigadista');
});

test('gestor nao pode promover a administrador', function () {
    $gestor = Usuario::factory()->gestor()->create();
    $alvo = Usuario::factory()->create(['funcao' => 'user']);

    $this->patchJson('/api/usuarios/'.$alvo->id.'/funcao', [
        'funcao' => 'administrador',
    ], usuarioAuthHeaders($gestor))->assertForbidden();
});

test('gestor nao pode alterar funcao de outro gestor', function () {
    $gestor = Usuario::factory()->gestor()->create();
    $alvo = Usuario::factory()->gestor()->create();

    $this->patchJson('/api/usuarios/'.$alvo->id.'/funcao', [
        'funcao' => 'brigadista',
    ], usuarioAuthHeaders($gestor))->assertForbidden();
});

test('administrador pode alternar bloqueio de usuario', function () {
    $admin = Usuario::factory()->administrador()->create();
    $alvo = Usuario::factory()->create(['bloqueado' => false]);

    $this->patchJson('/api/usuarios/'.$alvo->id.'/bloqueio', [], usuarioAuthHeaders($admin))->assertOk()
        ->assertJsonPath('data.bloqueado', true);
});

test('nao pode alternar proprio bloqueio', function () {
    $admin = Usuario::factory()->administrador()->create();

    $this->patchJson('/api/usuarios/'.$admin->id.'/bloqueio', [], usuarioAuthHeaders($admin))
        ->assertForbidden();
});

test('administrador_restaura_usuario_excluido', function () {
    $admin = Usuario::factory()->administrador()->create();
    $alvo = Usuario::factory()->create();
    $alvo->delete();

    $this->postJson('/api/usuarios/'.$alvo->id.'/restore', [], usuarioAuthHeaders($admin))
        ->assertOk()
        ->assertJsonPath('data.id', $alvo->id);

    expect(Usuario::find($alvo->id))->not->toBeNull();
});

test('gestor_nao_pode_restaurar_usuario', function () {
    $gestor = Usuario::factory()->gestor()->create();
    $alvo = Usuario::factory()->create();
    $alvo->delete();

    $this->postJson('/api/usuarios/'.$alvo->id.'/restore', [], usuarioAuthHeaders($gestor))
        ->assertForbidden();
});

test('restore_usuario_retorna_404_se_nao_estiver_excluido', function () {
    $admin = Usuario::factory()->administrador()->create();
    $alvo = Usuario::factory()->create();

    $this->postJson('/api/usuarios/'.$alvo->id.'/restore', [], usuarioAuthHeaders($admin))
        ->assertNotFound();
});
