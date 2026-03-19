<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Tabela: usuarios
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('email', 255)->unique();
            $table->char('cpf', 11)->unique();
            $table->string('senha_hash', 255);
            $table->enum('funcao', ['brigadista', 'gestor', 'admin'])->default('brigadista');
            $table->timestamp('criado_em')->useCurrent();
        });

        // Tabela: tokens_recuperacao_senha
        Schema::create('tokens_recuperacao_senha', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('token', 255)->unique();
            $table->dateTime('expira_em');
            $table->boolean('usado')->default(false);
            $table->timestamp('criado_em')->useCurrent();
        });

        // Tabela: brigadas
        Schema::create('brigadas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('tipo', 100);
            $table->decimal('latitude_atual', 10, 7)->nullable();
            $table->decimal('longitude_atual', 10, 7)->nullable();
            $table->boolean('disponivel')->default(true);
        });

        // Tabela: brigada_membros (N:N)
        Schema::create('brigada_membros', function (Blueprint $table) {
            $table->foreignUuid('brigada_id')->constrained('brigadas')->onDelete('cascade');
            $table->foreignUuid('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->primary(['brigada_id', 'usuario_id']);
        });

        // Tabela: areas_monitoradas
        Schema::create('areas_monitoradas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('caminho_geopackage', 500)->nullable();
            $table->text('geometria_wkt')->nullable();
            $table->timestamp('importado_em')->useCurrent();
        });

        // Tabela: locais_criticos
        Schema::create('locais_criticos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->enum('tipo', ['residencia', 'escola', 'infraestrutura']);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('descricao')->nullable();
            $table->foreignUuid('area_id')->nullable()->constrained('areas_monitoradas')->onDelete('set null');
            $table->index(['latitude', 'longitude'], 'idx_locais_criticos_coords');
        });

        // Tabela: leituras_meteorologicas
        Schema::create('leituras_meteorologicas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('area_id')->constrained('areas_monitoradas')->onDelete('cascade');
            $table->decimal('temperatura', 5, 2);
            $table->decimal('umidade', 5, 2);
            $table->decimal('velocidade_vento', 6, 2);
            $table->timestamp('registrado_em')->useCurrent()->index();
            $table->boolean('gera_alerta')->default(false)->index();
        });

        // Tabela: deteccoes_satelite
        Schema::create('deteccoes_satelite', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->dateTime('detectado_em')->index();
            $table->decimal('confianca', 5, 2)->comment('0 a 100');
            $table->string('fonte', 100)->default('NASA FIRMS');
            $table->index(['latitude', 'longitude'], 'idx_deteccoes_coords');
        });

        // Tabela: incendios
        Schema::create('incendios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('detectado_em')->useCurrent()->index();
            $table->enum('nivel_risco', ['alto', 'medio', 'baixo'])->default('alto')->index();
            $table->enum('status', ['ativo', 'contido', 'resolvido'])->default('ativo')->index();
            $table->foreignUuid('registrado_por')->constrained('usuarios')->onDelete('restrict');
            $table->foreignUuid('area_id')->nullable()->constrained('areas_monitoradas')->onDelete('set null');
            $table->foreignUuid('deteccao_satelite_id')->nullable()->constrained('deteccoes_satelite')->onDelete('set null');
            $table->index(['latitude', 'longitude'], 'idx_incendios_coords');
        });

        // Tabela: incendio_locais_criticos
        Schema::create('incendio_locais_criticos', function (Blueprint $table) {
            $table->foreignUuid('incendio_id')->constrained('incendios')->onDelete('cascade');
            $table->foreignUuid('local_critico_id')->constrained('locais_criticos')->onDelete('cascade');
            $table->decimal('distancia_metros', 10, 2)->nullable();
            $table->primary(['incendio_id', 'local_critico_id']);
        });

        // Tabela: despachos_brigada
        Schema::create('despachos_brigada', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('incendio_id')->constrained('incendios')->onDelete('cascade');
            $table->foreignUuid('brigada_id')->constrained('brigadas')->onDelete('restrict');
            $table->timestamp('despachado_em')->useCurrent();
            $table->dateTime('chegada_em')->nullable();
            $table->text('observacoes')->nullable();
        });

        // Tabela: alertas
        Schema::create('alertas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->enum('tipo', ['temperatura_alta', 'umidade_baixa', 'fogo_detectado', 'proximidade_local_critico']);
            $table->text('mensagem');
            $table->uuid('origem_id')->comment('ID da leitura, deteccao ou incendio');
            $table->string('origem_tabela', 100);
            $table->timestamp('enviado_em')->useCurrent()->index();
            $table->boolean('entregue')->default(false);
        });

        // Tabela: relatorios
        Schema::create('relatorios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('gerado_por')->constrained('usuarios')->onDelete('restrict');
            $table->enum('tipo', ['historico_incendios', 'zonas_risco', 'tempo_resposta', 'locais_criticos']);
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->timestamp('gerado_em')->useCurrent();
            $table->string('caminho_pdf', 500)->nullable();
            $table->string('caminho_csv', 500)->nullable();
        });

        // Tabela: logs_auditoria
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('acao', 150);
            $table->string('entidade_tipo', 100);
            $table->uuid('entidade_id')->nullable();
            $table->json('dados_json')->nullable();
            $table->timestamp('registrado_em')->useCurrent()->index();
            $table->index(['entidade_tipo', 'entidade_id'], 'idx_logs_entidade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
        Schema::dropIfExists('relatorios');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('despachos_brigada');
        Schema::dropIfExists('incendio_locais_criticos');
        Schema::dropIfExists('incendios');
        Schema::dropIfExists('deteccoes_satelite');
        Schema::dropIfExists('leituras_meteorologicas');
        Schema::dropIfExists('locais_criticos');
        Schema::dropIfExists('areas_monitoradas');
        Schema::dropIfExists('brigada_membros');
        Schema::dropIfExists('brigadas');
        Schema::dropIfExists('tokens_recuperacao_senha');
        Schema::dropIfExists('usuarios');
    }
};
