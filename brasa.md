# BRASA — Documentação de Contexto

> Leia este arquivo antes de qualquer chat novo relacionado ao projeto.
> Ele consolida decisões de arquitetura, stack, schema, convenções e regras de agente.
> **Não prossiga sem ter lido integralmente.**

---

## O que é o Brasa

Sistema de gestão e inteligência preditiva de incêndios florestais no **Pantanal brasileiro**. Desenvolvido por seis estudantes com impacto extensionista real — apoia brigadas de combate ao fogo, gestores ambientais e comunidades em áreas de risco.

**Objetivo central:** reduzir tempo de resposta a incêndios e mapear áreas de maior risco de ignição, permitindo atuação preventiva em vez de reativa.

---

## Stack

| Camada            | Tecnologia                     |
| ----------------- | ------------------------------ |
| Backend           | PHP + Laravel 12               |
| Frontend          | ReactJS + Inertia.js           |
| Banco de dados    | PostgreSQL via Supabase        |
| API meteorológica | OpenMeteo                      |
| API de satélite   | NASA FIRMS                     |
| Dados geográficos | GeoPackage (importação manual) |

---

## Convenções do projeto

- **Idioma dos campos:** português (tabelas, colunas, enums)
- **UUIDs:** gerados via `gen_random_uuid()` — nativo PostgreSQL 13+, sem extensões
- **Timestamps:** sempre `TIMESTAMPTZ` — nunca `TIMESTAMP` sem timezone
- **Campos `criado_em` e `atualizado_em`:** autogeridos no banco via `DEFAULT NOW()` e trigger
- **Sem extensões externas:** sem `uuid-ossp`, sem `postgis` — coordenadas em `NUMERIC(10,7)`, geometria em WKT (`TEXT`)
- **Chaves primárias:** sempre `id UUID`
- **Soft delete:** não adotado — deleção física com `ON DELETE` explícito

---

## Regras de agente

Estas regras aplicam-se a **todo chat de implementação** do projeto, sem exceção.

### Fluxo obrigatório

```
1. PLANEJAMENTO   → listar arquivos, responsabilidades, dependências
2. DESENVOLVIMENTO
3. COBERTURA DE TESTES
4. REVISÃO
5. ATUALIZAÇÃO DO BRASA.md
```

Aguardar aprovação explícita entre Planejamento e Desenvolvimento.
Não avançar etapas sem conclusão da anterior.

### Regra de escrita única por arquivo

- Cada arquivo é escrito **uma vez**, completo e final.
- **Não editar o mesmo arquivo mais de uma vez** sem aprovação explícita.
- Se um problema for identificado após a escrita: **descrever em texto e aguardar instrução** — não corrigir autonomamente.
- Sem loops de autocorreção. Sem refatorações não solicitadas.

### Suite de testes

Ao final de cada implementação, **rodar a suite de testes completa** — não apenas os testes do arquivo recém-criado.

```bash
php artisan test
```

Reportar: quantos passaram, quantos falharam, quais falharam e por quê.
Não encerrar a etapa de revisão com testes quebrados.

### Escopo de cada chat

- Um controller por chat. Contexto limpo por tarefa.
- Não implementar além do escopo definido no prompt.
- Não abrir refatorações de arquivos existentes salvo instrução explícita.

---

## Módulos da aplicação

### Módulo 1 — Gestão de Usuários e Segurança

Autenticação, controle de acesso por papel e recuperação de senha.

### Módulo 2 — Monitoramento e Operações de Campo

Registro de incêndios, despacho de brigadas, importação de dados meteorológicos e geográficos, visualização em mapa.

### Módulo 3 — Relatórios e Inteligência

Análise histórica, tempo de resposta, zonas de risco. _(Relatórios gerados no client — sem tabela de relatórios no banco.)_

---

## Schema do banco

### Tabelas ativas

| Tabela                     | Descrição                                               |
| -------------------------- | ------------------------------------------------------- |
| `brigadas`                 | Brigadas de combate ao fogo                             |
| `usuarios`                 | Usuários do sistema (brigadistas, gestores, admins)     |
| `tokens_recuperacao_senha` | Tokens de reset de senha (expiram em 30 min)            |
| `areas_monitoradas`        | Regiões geográficas sob monitoramento (GeoPackage/WKT)  |
| `locais_criticos`          | Pontos sensíveis: residências, escolas, infraestrutura  |
| `deteccoes_satelite`       | Detecções de fogo via NASA FIRMS                        |
| `incendios`                | Ocorrências de incêndio registradas                     |
| `leituras_meteorologicas`  | Condições climáticas no momento do registro do incêndio |
| `despachos_brigada`        | Despacho de brigadas para ocorrências                   |
| `alertas`                  | Alertas automáticos — tabela polimórfica                |
| `logs_auditoria`           | Log imutável de todas as operações                      |

### Relacionamentos principais

```
brigadas
  └── usuarios (brigada_id nullable — um usuário pertence a uma brigada)

usuarios
  ├── tokens_recuperacao_senha (1:N)
  ├── incendios (1:N via usuario_id — quem registrou)
  └── logs_auditoria (1:N via usuario_id nullable)

areas_monitoradas
  └── incendios (1:N via area_id — OBRIGATÓRIO)

locais_criticos
  └── incendios (1:N via local_critico_id — OPCIONAL)

deteccoes_satelite
  └── incendios (1:N via deteccao_satelite_id — OPCIONAL)

incendios
  ├── leituras_meteorologicas (1:N — condições climáticas do incêndio)
  └── despachos_brigada (1:N)

alertas — polimórfica
  origem_id + origem_tabela → leituras_meteorologicas | deteccoes_satelite | incendios
```

### ENUMs PostgreSQL

```sql
funcao_usuario:     brigadista | gestor | admin
nivel_risco:        alto | medio | baixo
status_incendio:    ativo | contido | resolvido
tipo_local_critico: residencia | escola | infraestrutura
tipo_alerta:        temperatura_alta | umidade_baixa | fogo_detectado | proximidade_local_critico
```

### Decisões de design relevantes

**`alertas` é polimórfica sem FKs** — intencional. `origem_id` + `origem_tabela` identificam a origem. Eloquent via `morphTo`/`morphMany`. Não adicionar FKs nessa tabela.

**`leituras_meteorologicas` vinculada ao incêndio** — condições climáticas atreladas via `incendio_id` no momento do registro. Sem relação com `areas_monitoradas`.

**`locais_criticos` é independente** — associada opcionalmente ao incêndio via `local_critico_id` em `incendios`. Cálculo de distância feito no client.

**`despachos_brigada` tem CHECK de timeline** — banco garante `despachado_em ≤ chegada_em ≤ finalizado_em`. `finalizado_em` registra encerramento do combate.

**Sem tabela de relatórios** — geração de PDF/CSV é responsabilidade do client.

---

## Resources e comportamentos especiais

### UsuarioResource — modo restrito

`UsuarioResource` aceita um segundo argumento opcional `$somenteMembroBrigada` (boolean).

```php
// modo completo (padrão) — usado em AuthController e UsuarioController
new UsuarioResource($usuario)
// → expõe: id, nome, email, funcao, brigada_id, criado_em

// modo restrito — usado em BrigadaResource ao listar membros
new UsuarioResource($usuario, true)
// → expõe apenas: id, nome, funcao
```

**Regra:** qualquer controller que instanciar `UsuarioResource` deve declarar explicitamente qual modo usa.
Modo completo não expõe `senha_hash` nem `cpf` em nenhuma circunstância.
Modo restrito é exclusivo para contextos de listagem aninhada — evita vazar `email` em respostas de brigada.

---

## Models — padrão HasUuids

`HasUuids` confirmado em: `Brigada`, `AreaMonitorada`, `LocalCritico`, `DeteccaoSatelite`.
Demais Models não auditados — uniformizar na sprint de débito.

**Regra para chats futuros:** ao criar ou tocar qualquer Model, verificar se `HasUuids` está presente. Se não estiver — adicionar. Não tratar como escopo expandido. É correção de consistência.

---

## O que já foi produzido

| Artefato                           | Arquivo                                                                       | Versão atual                                          |
| ---------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------- |
| Schema DDL PostgreSQL final        | `brasa_schema_postgres.sql`                                                   | —                                                     |
| Prompt migrations (Laravel 12)     | `prompt_migrations_brasa.md`                                                  | —                                                     |
| Prompt Models Eloquent             | `prompt_models_brasa.md`                                                      | —                                                     |
| AuthController                     | `app/Http/Controllers/AuthController.php`                                     | —                                                     |
| LoginRequest                       | `app/Http/Requests/LoginRequest.php`                                          | —                                                     |
| UsuarioResource                    | `app/Http/Resources/UsuarioResource.php`                                      | modificado no BrigadaController — ver seção Resources |
| Testes de autenticação             | `tests/Feature/Auth/AuthControllerTest.php`                                   | —                                                     |
| PasswordResetController            | `app/Http/Controllers/Auth/PasswordResetController.php`                       | —                                                     |
| EsqueciSenhaRequest                | `app/Http/Requests/Auth/EsqueciSenhaRequest.php`                              | —                                                     |
| RedefinirSenhaRequest              | `app/Http/Requests/Auth/RedefinirSenhaRequest.php`                            | —                                                     |
| RecuperacaoSenhaMail               | `app/Mail/RecuperacaoSenhaMail.php`                                           | —                                                     |
| Testes de recuperação de senha     | `tests/Feature/Auth/PasswordResetControllerTest.php`                          | —                                                     |
| BrigadaController                  | `app/Http/Controllers/BrigadaController.php`                                  | —                                                     |
| StoreBrigadaRequest                | `app/Http/Requests/Brigada/StoreBrigadaRequest.php`                           | —                                                     |
| UpdateBrigadaRequest               | `app/Http/Requests/Brigada/UpdateBrigadaRequest.php`                          | —                                                     |
| AtualizarLocalizacaoBrigadaRequest | `app/Http/Requests/Brigada/AtualizarLocalizacaoBrigadaRequest.php`            | —                                                     |
| BrigadaResource                    | `app/Http/Resources/BrigadaResource.php`                                      | —                                                     |
| Testes de brigada                  | `tests/Feature/BrigadaControllerTest.php`                                     | —                                                     |
| AreaMonitoradaController           | `app/Http/Controllers/AreaMonitoradaController.php`                           | —                                                     |
| StoreAreaMonitoradaRequest         | `app/Http/Requests/AreaMonitorada/StoreAreaMonitoradaRequest.php`             | —                                                     |
| UpdateAreaMonitoradaRequest        | `app/Http/Requests/AreaMonitorada/UpdateAreaMonitoradaRequest.php`            | —                                                     |
| AreaMonitoradaResource             | `app/Http/Resources/AreaMonitoradaResource.php`                               | —                                                     |
| GeoPackageService                  | `app/Services/GeoPackageService.php`                                          | —                                                     |
| Testes de área monitorada          | `tests/Feature/AreaMonitoradaControllerTest.php`                              | —                                                     |
| LocalCriticoController             | `app/Http/Controllers/LocalCriticoController.php`                             | —                                                     |
| StoreLocalCriticoRequest           | `app/Http/Requests/LocalCritico/StoreLocalCriticoRequest.php`                 | —                                                     |
| UpdateLocalCriticoRequest          | `app/Http/Requests/LocalCritico/UpdateLocalCriticoRequest.php`                | —                                                     |
| LocalCriticoResource               | `app/Http/Resources/LocalCriticoResource.php`                                 | —                                                     |
| LocalCriticoFactory                | `database/factories/LocalCriticoFactory.php`                                  | —                                                     |
| Testes de local crítico            | `tests/Feature/LocalCriticoControllerTest.php`                                | —                                                     |
| UsuarioController                  | `app/Http/Controllers/UsuarioController.php`                                  | —                                                     |
| StoreUsuarioRequest                | `app/Http/Requests/Usuario/StoreUsuarioRequest.php`                           | —                                                     |
| UpdateUsuarioRequest               | `app/Http/Requests/Usuario/UpdateUsuarioRequest.php`                          | —                                                     |
| AtualizarFuncaoRequest             | `app/Http/Requests/Usuario/AtualizarFuncaoRequest.php`                        | —                                                     |
| AtualizarBrigadaRequest            | `app/Http/Requests/Usuario/AtualizarBrigadaRequest.php`                       | —                                                     |
| Testes de usuário                  | `tests/Feature/UsuarioControllerTest.php`                                     | —                                                     |
| DeteccaoSateliteController         | `app/Http/Controllers/DeteccaoSateliteController.php`                         | —                                                     |
| StoreDeteccaoSateliteRequest       | `app/Http/Requests/DeteccaoSatelite/StoreDeteccaoSateliteRequest.php`         | —                                                     |
| StoreLoteDeteccaoSateliteRequest   | `app/Http/Requests/DeteccaoSatelite/StoreLoteDeteccaoSateliteRequest.php`     | —                                                     |
| DeteccaoSateliteResource           | `app/Http/Resources/DeteccaoSateliteResource.php`                             | —                                                     |
| DeteccaoSateliteFactory            | `database/factories/DeteccaoSateliteFactory.php`                              | —                                                     |
| Testes de detecção satélite        | `tests/Feature/DeteccaoSateliteControllerTest.php`                            | —                                                     |
| IncendioController                 | `app/Http/Controllers/IncendioController.php`                                 | —                                                     |
| StoreIncendioRequest               | `app/Http/Requests/Incendio/StoreIncendioRequest.php`                         | —                                                     |
| UpdateIncendioRequest              | `app/Http/Requests/Incendio/UpdateIncendioRequest.php`                        | —                                                     |
| AtualizarStatusRequest             | `app/Http/Requests/Incendio/AtualizarStatusRequest.php`                       | —                                                     |
| AtualizarRiscoRequest              | `app/Http/Requests/Incendio/AtualizarRiscoRequest.php`                        | —                                                     |
| IncendioResource                   | `app/Http/Resources/IncendioResource.php`                                     | —                                                     |
| AreaMonitoradaFactory              | `database/factories/AreaMonitoradaFactory.php`                                | —                                                     |
| IncendioFactory                    | `database/factories/IncendioFactory.php`                                      | —                                                     |
| Testes de incêndio                 | `tests/Feature/IncendioControllerTest.php`                                    | —                                                     |
| LeituraMeteorologicaController     | `app/Http/Controllers/LeituraMeteorologicaController.php`                     | —                                                     |
| StoreLeituraMeteorologicaRequest   | `app/Http/Requests/LeituraMeteorologica/StoreLeituraMeteorologicaRequest.php` | —                                                     |
| LeituraMeteorologicaResource       | `app/Http/Resources/LeituraMeteorologicaResource.php`                         | —                                                     |
| LeituraMeteorologicaFactory        | `database/factories/LeituraMeteorologicaFactory.php`                          | —                                                     |
| Testes de leitura meteorológica    | `tests/Feature/LeituraMeteorologicaControllerTest.php`                        | —                                                     |
| DespachoBrigadaController          | `app/Http/Controllers/DespachoBrigadaController.php`                          | —                                                     |
| StoreDespachoBrigadaRequest        | `app/Http/Requests/DespachoBrigada/StoreDespachoBrigadaRequest.php`           | —                                                     |
| RegistrarChegadaRequest            | `app/Http/Requests/DespachoBrigada/RegistrarChegadaRequest.php`             | —                                                     |
| FinalizarDespachoRequest           | `app/Http/Requests/DespachoBrigada/FinalizarDespachoRequest.php`              | —                                                     |
| DespachoBrigadaResource            | `app/Http/Resources/DespachoBrigadaResource.php`                              | —                                                     |
| DespachoBrigadaFactory             | `database/factories/DespachoBrigadaFactory.php`                               | —                                                     |
| Testes de despacho de brigada      | `tests/Feature/DespachoBrigadaControllerTest.php`                             | —                                                     |

---

## O que ainda precisa ser feito

- [x] Autenticação — AuthController (Sanctum)
- [x] Recuperação de senha — PasswordResetController
- [x] BrigadaController — CRUD + atualização de localização
- [x] AreaMonitoradaController — CRUD + importação GeoPackage (MVP síncrono)
- [x] LocalCriticoController — CRUD
- [x] UsuarioController — CRUD + atualizarFuncao + atualizarBrigada
- [x] DeteccaoSateliteController — ingestão + consulta (integração NASA FIRMS pendente)
- [x] IncendioController — registro + status + risco
- [x] LeituraMeteorologicaController — registro + consulta aninhada
- [x] DespachoBrigadaController — despacho + chegada + finalização
- [ ] AlertaController
- [ ] LogAuditoriaController
- [ ] Migrations Laravel
- [ ] Models Eloquent com relacionamentos
- [ ] Seeders de desenvolvimento
- [ ] Integração OpenMeteo
- [ ] Integração NASA FIRMS
- [ ] Visualização de mapa (frontend)
- [ ] Sistema de alertas (push + email)

---

## Sequência de controllers

| Ordem | Controller                       | Grupo | Depende de                                                      |
| ----- | -------------------------------- | ----- | --------------------------------------------------------------- |
| 1     | `AuthController`                 | 1     | —                                                               |
| 2     | `PasswordResetController`        | 1     | `TokenRecuperacaoSenha`, `Usuario`                              |
| 3     | `BrigadaController`              | 1     | —                                                               |
| 4     | `AreaMonitoradaController`       | 1     | —                                                               |
| 5     | `LocalCriticoController`         | 1     | —                                                               |
| 6     | `UsuarioController`              | 2     | `Brigada`                                                       |
| 7     | `DeteccaoSateliteController`     | 2     | —                                                               |
| 8     | `IncendioController`             | 3     | `Usuario`, `AreaMonitorada`, `LocalCritico`, `DeteccaoSatelite` |
| 9     | `LeituraMeteorologicaController` | 3     | `Incendio`                                                      |
| 10    | `DespachoBrigadaController`      | 4     | `Incendio`, `Brigada`                                           |
| 11    | `AlertaController`               | 4     | — (somente leitura + patch)                                     |
| 12    | `LogAuditoriaController`         | 4     | — (somente leitura, admin only)                                 |

---

## Controllers implementados

### AuthController

- `POST /api/auth/login` — público
- `POST /api/auth/logout` — auth:sanctum
- `GET  /api/auth/me` — auth:sanctum

Registra log de auditoria em login e logout.
Token Sanctum stateless. Campos sensíveis nunca expostos nas respostas.

### PasswordResetController

- `POST /api/auth/senha/esqueci` — público
- `POST /api/auth/senha/redefinir` — público

Fluxo stateless sem Sanctum. Token expira em 30 min.
Email enfileirado — não bloqueia resposta. Resposta sempre 200 em `esqueci`
para não revelar existência de conta. Todos os tokens Sanctum revogados após reset.

### BrigadaController

- `GET    /api/brigadas` — auth:sanctum (gestor, admin)
- `POST   /api/brigadas` — auth:sanctum (admin)
- `GET    /api/brigadas/{brigada}` — auth:sanctum (gestor, admin)
- `PUT    /api/brigadas/{brigada}` — auth:sanctum (admin)
- `DELETE /api/brigadas/{brigada}` — auth:sanctum (admin)
- `PATCH  /api/brigadas/{brigada}/localizacao` — auth:sanctum (brigadista, gestor, admin)

Bloqueia remoção de brigada com membros vinculados (409).
Log de auditoria em criação, atualização, remoção e atualização de localização.
Controle de papel via middleware — pendente implementação do middleware de papéis.
Membros listados via `UsuarioResource` em modo restrito (`$somenteMembroBrigada = true`).

### AreaMonitoradaController

- `GET    /api/areas-monitoradas` — auth:sanctum (gestor, admin)
- `POST   /api/areas-monitoradas` — auth:sanctum (admin)
- `GET    /api/areas-monitoradas/{area}` — auth:sanctum (gestor, admin)
- `PUT    /api/areas-monitoradas/{area}` — auth:sanctum (admin)
- `DELETE /api/areas-monitoradas/{area}` — auth:sanctum (admin)

Importação de GeoPackage via PDO/SQLite (MVP síncrono — sem Job).
Geometria armazenada como WKT em TEXT — sem PostGIS.
Bloqueia remoção de área com incêndios vinculados (409).
Arquivo removido do storage junto com o registro.
Log de auditoria em criação, atualização e remoção.
Conversão WKB→WKT pendente como dívida técnica.

### LocalCriticoController

- `GET    /api/locais-criticos` — auth:sanctum (gestor, admin)
- `POST   /api/locais-criticos` — auth:sanctum (admin)
- `GET    /api/locais-criticos/{local}` — auth:sanctum (gestor, admin)
- `PUT    /api/locais-criticos/{local}` — auth:sanctum (admin)
- `DELETE /api/locais-criticos/{local}` — auth:sanctum (admin)

Tabela independente — sem FK de saída.
Bloqueia remoção de local com incêndios vinculados (409).
Filtros por tipo e nome em index.
Cálculo de distância é responsabilidade do client — não implementado no backend.
Log de auditoria em criação, atualização e remoção.
Controle de papel via middleware — pendente implementação do middleware de papéis.

### UsuarioController

- `GET    /api/usuarios` — auth:sanctum (admin)
- `POST   /api/usuarios` — auth:sanctum (admin)
- `GET    /api/usuarios/{usuario}` — auth:sanctum (admin)
- `PUT    /api/usuarios/{usuario}` — auth:sanctum (admin)
- `DELETE /api/usuarios/{usuario}` — auth:sanctum (admin)
- `PATCH  /api/usuarios/{usuario}/funcao` — auth:sanctum (admin)
- `PATCH  /api/usuarios/{usuario}/brigada` — auth:sanctum (admin, gestor)

Bloqueia remoção do próprio usuário autenticado (403).
Bloqueia remoção de usuário com incêndios vinculados (409).
Bloqueia alteração da própria função (403).
Tokens Sanctum revogados antes da remoção.
senha_hash e cpf nunca expostos. UsuarioResource em modo completo.
Log de auditoria em criação, atualização, remoção, mudança de função e brigada.
Controle de papel via middleware — pendente implementação do middleware de papéis.

### DeteccaoSateliteController

- `GET  /api/deteccoes-satelite` — auth:sanctum (gestor, admin)
- `POST /api/deteccoes-satelite` — auth:sanctum (admin)
- `GET  /api/deteccoes-satelite/{deteccao}` — auth:sanctum (gestor, admin)
- `POST /api/deteccoes-satelite/lote` — auth:sanctum (admin)

Registros imutáveis — sem update e destroy por design.
Ingestão individual e em lote (máx 500 por requisição).
storeLote atômico via DB::transaction().
Filtros por fonte, confiança mínima e intervalo de data.
Integração real NASA FIRMS pendente — será implementada como Job/Service.
Log de auditoria em store e storeLote.
Controle de papel via middleware — pendente implementação do middleware de papéis.

### IncendioController

- `GET   /api/incendios` — auth:sanctum (brigadista, gestor, admin)
- `POST  /api/incendios` — auth:sanctum (brigadista, gestor, admin)
- `GET   /api/incendios/{incendio}` — auth:sanctum (brigadista, gestor, admin)
- `PUT   /api/incendios/{incendio}` — auth:sanctum (gestor, admin)
- `PATCH /api/incendios/{incendio}/status` — auth:sanctum (brigadista, gestor, admin)
- `PATCH /api/incendios/{incendio}/risco` — auth:sanctum (gestor, admin)

Sem destroy — registros históricos imutáveis.
usuario_id sempre do usuário autenticado — nunca do payload.
status não aceito em store nem update — endpoint dedicado.
Eager load de area, localCritico, deteccaoSatelite, usuario.
Log de auditoria em registro, atualização, mudança de status e risco.
Controle de papel via middleware — pendente implementação do middleware de papéis.

### LeituraMeteorologicaController

- `GET  /api/incendios/{incendio}/leituras` — auth:sanctum (brigadista, gestor, admin)
- `POST /api/incendios/{incendio}/leituras` — auth:sanctum (brigadista, gestor, admin)
- `GET  /api/incendios/{incendio}/leituras/{leitura}` — auth:sanctum (brigadista, gestor, admin)

Rotas aninhadas — leituras existem apenas no contexto de um incêndio.
Sem update e destroy — registros de contexto imutáveis.
incendio_id sempre da rota — nunca do payload.
Threshold de alerta avaliado automaticamente: temperatura > 30°C ou umidade < 40%.
Log de auditoria em store.
Integração real OpenMeteo pendente — será implementada como Job/Service.
Controle de papel via middleware — pendente.

### DespachoBrigadaController

- `GET   /api/incendios/{incendio}/despachos`                      — auth:sanctum (brigadista, gestor, admin)
- `POST  /api/incendios/{incendio}/despachos`                      — auth:sanctum (gestor, admin)
- `GET   /api/incendios/{incendio}/despachos/{despacho}`           — auth:sanctum (brigadista, gestor, admin)
- `PATCH /api/incendios/{incendio}/despachos/{despacho}/chegada`   — auth:sanctum (brigadista, gestor, admin)
- `PATCH /api/incendios/{incendio}/despachos/{despacho}/finalizar` — auth:sanctum (brigadista, gestor, admin)

Rotas aninhadas — despachos existem apenas no contexto de um incêndio.
Sem update genérico e sem destroy — registros operacionais históricos.
Ciclo de vida: despacho → chegada → finalização.
CHECK de timeline garantido no banco — não replicado no controller.
store bloqueia brigada já despachada sem finalização (409).
store marca brigada indisponível. finalizar marca brigada disponível.
tempo_resposta_minutos calculado no resource.
Log de auditoria em store, registrarChegada e finalizar.
Controle de papel via middleware — pendente.

---

## Dívida técnica

| Item                                           | Localização                                                       | Descrição                                                                                                                                                                                                             |
| ---------------------------------------------- | ----------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `UsuarioResource` flag `$somenteMembroBrigada` | `app/Http/Resources/UsuarioResource.php`                          | Argumento opcional adicionado fora do escopo. Documentado na seção Resources. Será polido após implementação de todos os controllers.                                                                                 |
| Conversão WKB→WKT                              | `app/Services/GeoPackageService.php`                              | GeoPackage armazena geometria em WKB binário. MVP armazena valor bruto. Conversão real para WKT pendente.                                                                                                             |
| `extensions:gpkg` vs `mimes:gpkg`              | `app/Http/Requests/AreaMonitorada/StoreAreaMonitoradaRequest.php` | Validação usa `extensions:gpkg` por limitação do MIME no fluxo de testes. Comportamento diverge do prompt original. Revisar na sprint de débito.                                                                      |
| `HasUuids` inconsistente nos Models            | `app/Models/*`                                                    | Confirmado em `Brigada`, `AreaMonitorada`, `LocalCritico`, `DeteccaoSatelite`, `Incendio`. `LeituraMeteorologica` tocada — verificar se HasUuids foi aplicado. Demais não auditados. Uniformizar na sprint de débito. |
