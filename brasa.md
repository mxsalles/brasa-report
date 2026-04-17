# Canindé — Documentação de Contexto

> Leia este arquivo antes de qualquer chat novo relacionado ao projeto.
> Ele consolida decisões de arquitetura, stack, schema, convenções e regras de agente.
> **Não prossiga sem ter lido integralmente.**

---

## O que é o Canindé

Sistema de gestão e inteligência preditiva de incêndios florestais no **Pantanal brasileiro**. Desenvolvido por seis estudantes com impacto extensionista real — apoia brigadas de combate ao fogo, gestores ambientais e comunidades em áreas de risco.

**Objetivo central:** reduzir tempo de resposta a incêndios e mapear áreas de maior risco de ignição, permitindo atuação preventiva em vez de reativa.

---

## Dívida técnica — branding e legado BRASA

Por decisão de branding, o produto passou a chamar-se **Canindé** (UI alinhada ao protótipo em `caninde`, incluindo logo em `public/images/logo-caninde.png` e paleta azul/âmbar do protótipo).

| Área                        | Situação                                                                                                                       |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **Utilizador / APP_NAME**   | Usar **Canindé** em e-mails, ecrãs e variável `APP_NAME` em novos ambientes (ver `.env.example`).                              |
| **Este ficheiro**           | Mantém o nome histórico `brasa.md` para não quebrar referências em regras de agente e fluxos existentes.                       |
| **Schema e artefactos SQL** | Ficheiros como `brasa_schema_postgres.sql` e prompts `prompt_*_brasa.md` conservam o prefixo **brasa** até migração explícita. |
| **Código / tokens**         | Novos tokens Sanctum usam o nome `caninde`; tokens ou strings antigas com `brasa` podem coexistir até serem revistos.          |

---

## Stack

| Camada            | Tecnologia                     |
| ----------------- | ------------------------------ |
| Backend           | PHP + Laravel 12               |
| Frontend          | ReactJS + Inertia.js           |
| Banco de dados    | PostgreSQL via Supabase        |
| Idioma / Locale   | pt_BR (validação, UI, labels)  |
| API meteorológica | OpenMeteo                      |
| API de satélite   | NASA FIRMS                     |
| Dados geográficos | GeoPackage (importação manual) |

---

## Frontend (Inertia)

### Rotas alinhadas ao protótipo Canindé

**Páginas com dados reais:** dashboard (KPIs via props Inertia), registrar-incendio (área padrão «Pantanal Geral» via prop Inertia `areaPadrao`; envio com POST `/api/incendios`), brigadas (dados reais via `BrigadasPageController` + CRUD via API), mapa (incêndios reais via `MapaPageController` + condições climáticas via **OpenMeteo** no backend, mesmo serviço/cache que o dashboard).
**Páginas ainda com mock:** alertas. **Administração** usa dados reais (Inertia + API para alterações).

As rotas autenticadas **mapa**, **registrar-incendio**, **alertas**, **brigadas** e **administracao** estão registadas em Laravel e expostas na sidebar. Mapa usa **Leaflet** com dados reais do banco.

Dashboard agora também consome `GET /api/dashboard` via axios (Sanctum stateful) para atualização e inclui clima atual via **OpenMeteo** (chamada no backend com cache).

---

## Autenticação web

- **Engine:** Laravel Fortify + sessão (guard `web`)
- **Registro:** público, `funcao = 'user'` por padrão — promoção a brigadista/gestor/administrador por gestor ou administrador
- **Verificação de email:** obrigatória — middleware `verified` em todas as rotas do app
- **Mailer:** Mailpit (`localhost:1025`) em desenvolvimento
- **Promoção de papéis:** `PATCH /api/usuarios/{usuario}/funcao` — gestores só podem definir `user` ou `brigadista`; apenas administradores podem definir `gestor` ou `administrador`. Conta bloqueada (`bloqueado`) impede login e APIs autenticadas.
- **API mobile (futuro):** `AuthController` com Sanctum mantido intacto em `app/Http/Controllers/AuthController.php`
- **Campos customizados:** `senha_hash` (não `password`), `nome` (não `name`), `cpf` adicional
- **Política de senha (produção):** em `APP_ENV=production`, `Password::defaults()` em `app/Providers/AppServiceProvider.php` (`configureDefaults`) exige apenas **mínimo de 8 caracteres** — sem obrigatoriedade de maiúsculas/minúsculas mistas, números, símbolos nem verificação de senhas vazadas (`uncompromised`). Fora de produção, o callback devolve `null` e o Laravel usa o padrão do framework. Regras de nova senha e confirmação continuam centralizadas na trait `PasswordValidationRules` com `Password::default()`.
- **Utilizadores de teste (não produção):** `UsuarioTesteSeeder` garante três contas para desenvolvimento (todos com senha `12345678` em texto plano no seeder, persistida com `Hash::make`): brigadista `teste@gmail.com` (CPF `12345678954`, nome «Teste»); gestor `gestor@gmail.com`; administrador `admin@gmail.com`. Semeado no arranque da aplicação por `AppServiceProvider` quando a tabela `usuarios` existe e o email ainda não existe (mesmo padrão do `AreaMonitoradaSeeder`); também invocado por `DatabaseSeeder`. O seeder não executa em `APP_ENV=production`.
- **SPA + `/api` (Inertia + axios):** `config/sanctum.php` usa `guard` `web` e uma lista **stateful** que junta `SANCTUM_STATEFUL_DOMAINS` a defaults (localhost, `APP_URL`, etc.); para domínios Valet/Herd personalizados (ex.: `caninde.test`), inclua o host na env ou confirme que corresponde ao `APP_URL`. O cliente chama `GET /sanctum/csrf-cookie` no arranque (`resources/js/app.tsx`). Pedidos `axios` a `/api` enviam `Referer` quando o browser omite, para o middleware Sanctum reconhecer pedidos «do frontend».

---

## Internacionalização (i18n)

O aplicativo é 100 % pt-BR. Todas as strings visíveis ao usuário — validação, autenticação, paginação, labels, mensagens de erro, toasts, modais, aria-labels — estão em português brasileiro.

| Camada                    | Como funciona                                                                                                                          |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| **Locale Laravel**        | `config/app.php` → `locale = pt_BR`, `fallback_locale = pt_BR`, `faker_locale = pt_BR`; `.env` com `APP_LOCALE=pt_BR`.                 |
| **Arquivos de idioma**    | `lang/pt_BR/validation.php`, `auth.php`, `passwords.php`, `pagination.php` — publicados via `php artisan lang:publish` e traduzidos.    |
| **Atributos de validação** | Mapeados em `lang/pt_BR/validation.php` → `attributes` (nome, e-mail, senha, senha atual, confirmação da senha, código, token).        |
| **Frontend (React)**      | Strings hardcoded em pt-BR em todos os componentes, páginas, layouts, hooks e UI base (sidebar, dialog, sheet, spinner, breadcrumb).     |
| **Blade**                 | `app.blade.php` com fallback `Canindé` no `<title>`; `<html lang="pt-BR">` via `app()->getLocale()`.                                   |

**Regra:** nenhuma string em inglês visível ao usuário final. Novos componentes devem seguir a mesma convenção.

---

## Convenções do projeto

- **Idioma dos campos:** português (tabelas, colunas, enums)
- **UUIDs:** gerados via `gen_random_uuid()` — nativo PostgreSQL 13+, sem extensões
- **Timestamps:** sempre `TIMESTAMPTZ` — nunca `TIMESTAMP` sem timezone
- **Campos `criado_em` e `atualizado_em`:** autogeridos no banco via `DEFAULT NOW()` e trigger
- **Sem extensões externas:** sem `uuid-ossp`, sem `postgis` — coordenadas em `NUMERIC(10,7)`, geometria de áreas em GeoJSON (`longText` / JSON serializado)
- **Chaves primárias:** sempre `id UUID`
- **Soft delete:** adotado em `brigadas`, `incendios` e `usuarios` (coluna `deleted_at` em `TIMESTAMPTZ`). Os `DELETE` da API correspondentes marcam exclusão lógica; listagens e resolução por ID ignoram registros eliminados. Outras entidades continuam com remoção física onde já existia.

---

## Regras de agente

Estas regras aplicam-se a **todo chat de implementação** do projeto, sem exceção.

### Fluxo obrigatório

```
1. PLANEJAMENTO   → listar arquivos, responsabilidades, dependências
2. DESENVOLVIMENTO
3. COBERTURA DE TESTES
4. REVISÃO
5. ATUALIZAÇÃO DA DOCUMENTAÇÃO (`brasa.md`)
```

Aguardar aprovação explícita entre Planejamento e Desenvolvimento.
Não avançar etapas sem conclusão da anterior.

### Regra de escrita única por arquivo

- Cada arquivo é escrito **uma vez**, completo e final.
- **Não editar o mesmo arquivo mais de uma vez** sem aprovação explícita.
- Se um problema for identificado após a escrita: **descrever em texto e aguardar instrução** — não corrigir autonomamente.
- Sem loops de autocorreção. Sem refatorações não solicitadas.
- Execução de `vendor/bin/pint --dirty` não conta como edição — é formatação automática e pode ser aplicada livremente após a escrita.

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
| `areas_monitoradas`        | Regiões geográficas sob monitoramento (GeoJSON interno; upload GeoJSON/KML/SHP) |
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

Coluna `bloqueado` (boolean, default false) em `usuarios` — bloqueio operacional; não confundir com exclusão de conta.
Tabelas `brigadas`, `incendios` e `usuarios` possuem `deleted_at` (`TIMESTAMPTZ`, nullable) para exclusão lógica.

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
funcao_usuario:     user | brigadista | gestor | administrador
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
// → expõe: id, nome, email, funcao, brigada_id, brigada_nome (se relação carregada), bloqueado, criado_em

// modo restrito — usado em BrigadaResource ao listar membros
new UsuarioResource($usuario, true)
// → expõe apenas: id, nome, funcao
```

**Regra:** qualquer controller que instanciar `UsuarioResource` deve declarar explicitamente qual modo usa.
Modo completo não expõe `senha_hash` nem `cpf` em nenhuma circunstância.
Modo restrito é exclusivo para contextos de listagem aninhada — evita vazar `email` em respostas de brigada.

---

## Models — padrão HasUuids

`HasUuids` auditado e confirmado em todos os Models:
`Usuario`, `TokenRecuperacaoSenha`, `Brigada`, `AreaMonitorada`, `LocalCritico`,
`DeteccaoSatelite`, `Incendio`, `LeituraMeteorologica`, `DespachoBrigada`, `Alerta`, `LogAuditoria`.

Todos os Models têm `$keyType = 'string'` e `$incrementing = false`.

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
| GeoConverterService                | `app/Services/GeoConverterService.php`                                        | GeoJSON interno; uploads GeoJSON, KML, SHP (ZIP) via phayes/geophp + php-shapefile |
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
| RegistrarChegadaRequest            | `app/Http/Requests/DespachoBrigada/RegistrarChegadaRequest.php`               | —                                                     |
| FinalizarDespachoRequest           | `app/Http/Requests/DespachoBrigada/FinalizarDespachoRequest.php`              | —                                                     |
| DespachoBrigadaResource            | `app/Http/Resources/DespachoBrigadaResource.php`                              | —                                                     |
| DespachoBrigadaFactory             | `database/factories/DespachoBrigadaFactory.php`                               | —                                                     |
| Testes de despacho de brigada      | `tests/Feature/DespachoBrigadaControllerTest.php`                             | —                                                     |
| AlertaController                   | `app/Http/Controllers/AlertaController.php`                                   | —                                                     |
| AlertaResource                     | `app/Http/Resources/AlertaResource.php`                                       | —                                                     |
| AlertaFactory                      | `database/factories/AlertaFactory.php`                                        | —                                                     |
| Testes de alerta                   | `tests/Feature/AlertaControllerTest.php`                                      | —                                                     |
| LogAuditoriaController             | `app/Http/Controllers/LogAuditoriaController.php`                             | —                                                     |
| LogAuditoriaResource               | `app/Http/Resources/LogAuditoriaResource.php`                                 | —                                                     |
| LogAuditoriaFactory                | `database/factories/LogAuditoriaFactory.php`                                  | —                                                     |
| Testes de log auditoria            | `tests/Feature/LogAuditoriaControllerTest.php`                                | —                                                     |
| CreateNewUsuario                   | `app/Actions/Fortify/CreateNewUsuario.php`                                    | —                                                     |
| UpdateUsuarioPassword              | `app/Actions/Fortify/UpdateUsuarioPassword.php`                               | —                                                     |
| Migration email_verified_at        | migration add_email_verified_at_to_usuarios_table                             | —                                                     |
| Testes de registro                 | `tests/Feature/Auth/RegistrationTest.php`                                     | —                                                     |
| Testes de verificação              | `tests/Feature/Auth/EmailVerificationTest.php`                                | —                                                     |
| UsuarioSeeder                      | `database/seeders/UsuarioSeeder.php`                                          | —                                                     |
| UsuarioTesteSeeder                 | `database/seeders/UsuarioTesteSeeder.php`                                       | Utilizador de teste; ignorado em produção             |
| DatabaseSeeder                     | `database/seeders/DatabaseSeeder.php`                                         | —                                                     |
| DashboardController                | `app/Http/Controllers/DashboardController.php`                               | —                                                     |
| AreaMonitoradaSeeder               | `database/seeders/AreaMonitoradaSeeder.php`                                     | —                                                     |
| Testes do seeder usuário teste     | `tests/Feature/UsuarioTesteSeederTest.php`                                    | —                                                     |
| Testes de dashboard                | `tests/Feature/DashboardControllerTest.php`                                    | —                                                     |
| BrigadasPageController (Inertia)  | `app/Http/Controllers/BrigadasPageController.php`                              | Página Inertia com dados reais + CRUD via API          |
| Testes da página brigadas         | `tests/Feature/BrigadasPageControllerTest.php`                                 | —                                                     |
| MapaPageController (Inertia)      | `app/Http/Controllers/MapaPageController.php`                                  | Página Inertia com dados reais do mapa                 |
| Testes da página mapa             | `tests/Feature/MapaPageControllerTest.php`                                     | —                                                     |
| Migration em_combate              | `database/migrations/2026_04_16_222620_add_em_combate_to_status_incendio_enum.php` | Adiciona 'em_combate' ao enum PostgreSQL           |
| Arquivos de idioma pt_BR          | `lang/pt_BR/validation.php`, `auth.php`, `passwords.php`, `pagination.php`     | Validação, auth, senhas, paginação traduzidos          |
| Teste de locale                   | `tests/Unit/AppLocaleTest.php`                                                 | Garante locale/fallback/faker = pt_BR                  |
| Teste de política de senha        | `tests/Unit/PasswordPolicyTest.php`                                            | Produção: mínimo 8 caracteres sem regras de complexidade |

---

## O que ainda precisa ser feito

- [x] Autenticação — AuthController (Sanctum)
- [x] Recuperação de senha — PasswordResetController
- [x] BrigadaController — CRUD + atualização de localização
- [x] AreaMonitoradaController — CRUD + importação GeoPackage (MVP síncrono)
- [x] LocalCriticoController — CRUD
- [x] UsuarioController — CRUD + atualizarFuncao + atualizarBrigada
- [x] DeteccaoSateliteController — ingestão + consulta (integração NASA FIRMS pendente)
- [x] IncendioController — registro + status (fluxo linear com em_combate) + risco
- [x] LeituraMeteorologicaController — registro + consulta aninhada
- [x] DespachoBrigadaController — despacho + chegada + finalização
- [x] AlertaController — leitura + marcarEntregue
- [x] LogAuditoriaController — somente leitura, admin only
- [x] Autenticação web completa — Fortify + verificação de email + Mailpit
- [ ] Migrations Laravel
- [ ] Models Eloquent com relacionamentos
- [x] Seeders de desenvolvimento
- [x] HasUuids — auditoria e uniformização em todos os Models
- [x] Integração OpenMeteo (dashboard: clima atual via API; persistência/Job ainda pendente)
- [ ] Integração NASA FIRMS
- [x] Visualização de mapa (frontend — dados reais, popup + dialog de gerenciamento de status)
- [ ] Sistema de alertas (push + email)
- [x] Registro de incêndio — formulário real + Leaflet + toast
- [x] Dashboard — KPIs reais do banco
- [x] Área padrão "Pantanal Geral" — seeder
- [x] Nacionalização pt-BR — locale, lang files, frontend, UI, hooks

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
| 12    | `LogAuditoriaController`         | 4     | — (somente leitura, administrador only)                         |

---

## Controllers implementados

### AuthController

- `POST /api/auth/login` — público
- `POST /api/auth/logout` — auth:sanctum
- `GET  /api/auth/me` — auth:sanctum

Registra log de auditoria em login e logout.
Token Sanctum stateless. Campos sensíveis nunca expostos nas respostas.
Login API recusa conta com `bloqueado = true` (403).

### DashboardController (API)

- `GET /api/dashboard` — auth:sanctum (user, brigadista, gestor, administrador)

### PasswordResetController

- `POST /api/auth/senha/esqueci` — público
- `POST /api/auth/senha/redefinir` — público

Fluxo stateless sem Sanctum. Token expira em 30 min.
Email enfileirado — não bloqueia resposta. Resposta sempre 200 em `esqueci`
para não revelar existência de conta. Todos os tokens Sanctum revogados após reset.

### BrigadaController

- `GET    /api/brigadas` — auth:sanctum (user, brigadista, gestor, administrador)
- `POST   /api/brigadas` — auth:sanctum (gestor, administrador)
- `GET    /api/brigadas/{brigada}` — auth:sanctum (user, brigadista, gestor, administrador)
- `PUT    /api/brigadas/{brigada}` — auth:sanctum (gestor, administrador)
- `DELETE /api/brigadas/{brigada}` — auth:sanctum (gestor, administrador)
- `POST   /api/brigadas/{id}/restore` — auth:sanctum (gestor, administrador) — restaura apenas registro **excluso logicamente** (`onlyTrashed`)
- `PATCH  /api/brigadas/{brigada}/localizacao` — auth:sanctum (brigadista, gestor, administrador)

Bloqueia remoção de brigada com membros vinculados (409).
`DELETE` aplica soft delete (`deleted_at`) quando a remoção é permitida; `POST .../restore` reverte a exclusão lógica.
Log de auditoria em criação, atualização, remoção, restauração e atualização de localização.
Papéis: middleware `funcao` + `nao-bloqueado` (ver `routes/api.php`).
Membros listados via `UsuarioResource` em modo restrito (`$somenteMembroBrigada = true`).

### AreaMonitoradaController

- `GET    /api/areas-monitoradas` — auth:sanctum (gestor, administrador)
- `POST   /api/areas-monitoradas` — auth:sanctum (administrador)
- `GET    /api/areas-monitoradas/{area}` — auth:sanctum (gestor, administrador)
- `PUT    /api/areas-monitoradas/{area}` — auth:sanctum (administrador)
- `DELETE /api/areas-monitoradas/{area}` — auth:sanctum (administrador)

Upload opcional (`arquivo`): GeoJSON, KML ou ZIP com shapefile — normalizado para GeoJSON (`geometria_geojson`, `longText`) via `GeoConverterService` (phayes/geophp, gasparesganga/php-shapefile).
Bloqueia remoção de área com incêndios vinculados (409).
Arquivo original removido do storage junto com o registro.
Log de auditoria em criação, atualização e remoção.

### LocalCriticoController

- `GET    /api/locais-criticos` — auth:sanctum (gestor, administrador)
- `POST   /api/locais-criticos` — auth:sanctum (administrador)
- `GET    /api/locais-criticos/{local}` — auth:sanctum (gestor, administrador)
- `PUT    /api/locais-criticos/{local}` — auth:sanctum (administrador)
- `DELETE /api/locais-criticos/{local}` — auth:sanctum (administrador)

Tabela independente — sem FK de saída.
Bloqueia remoção de local com incêndios vinculados (409).
Filtros por tipo e nome em index.
Cálculo de distância é responsabilidade do client — não implementado no backend.
Log de auditoria em criação, atualização e remoção.
Papéis: middleware `funcao` + `nao-bloqueado` (ver `routes/api.php`).

### UsuarioController

- `GET    /api/usuarios` — auth:sanctum (administrador)
- `POST   /api/usuarios` — auth:sanctum (administrador)
- `GET    /api/usuarios/{usuario}` — auth:sanctum (administrador)
- `PUT    /api/usuarios/{usuario}` — auth:sanctum (administrador)
- `DELETE /api/usuarios/{usuario}` — auth:sanctum (administrador)
- `POST   /api/usuarios/{id}/restore` — auth:sanctum (administrador) — restaura apenas conta **excluída logicamente**
- `PATCH  /api/usuarios/{usuario}/funcao` — auth:sanctum (gestor, administrador) — gestor só `user`/`brigadista` e não altera outros gestores/administradores
- `PATCH  /api/usuarios/{usuario}/brigada` — auth:sanctum (gestor, administrador) — gestor não altera brigada de gestores/administradores
- `PATCH  /api/usuarios/{usuario}/bloqueio` — auth:sanctum (gestor, administrador) — gestor não altera bloqueio de gestores/administradores

Bloqueia remoção do próprio usuário autenticado (403).
`DELETE` aplica soft delete (`deleted_at`) quando a remoção é permitida; `POST .../restore` reverte a exclusão lógica (só administrador).
Bloqueia remoção de usuário com incêndios vinculados (409).
Bloqueia alteração da própria função (403). Bloqueio da própria conta (403).
Tokens Sanctum revogados antes da remoção.
senha_hash e cpf nunca expostos. UsuarioResource em modo completo (inclui `bloqueado`, `brigada_nome` quando cargada).
Log de auditoria em criação, atualização, remoção, restauração, mudança de função, brigada e bloqueio/desbloqueio.
Papéis: middleware `funcao` + `nao-bloqueado` (ver `routes/api.php`).

### BrigadasPageController (Inertia)

- `GET /brigadas` — web `auth`, `verified`, `nao-bloqueado` — exibe brigadas com dados reais (contagem de membros), despachos ativos/finalizados e CRUD condicional (gestor/administrador).

Props Inertia: `brigadas`, `despachosAtivos` (sem `finalizado_em`, sem limite), `despachosFinalizados` (com `finalizado_em`, limit 20), `podeGerenciar` (boolean), `funcaoAutenticado`, `usuariosDisponiveis` (usuários sem brigada, não bloqueados — só quando `podeGerenciar`), `incendiosAtivos` (incêndios com status ativo/em combate/contido, com área eager loaded — só quando `podeGerenciar`).
Cada item em `brigadas` pode incluir `operacao_incendio`: `null` ou `{ fase, incendio_status, area_nome }` quando existe despacho em aberto — `fase` é `em_deslocamento` (sem `chegada_em`) ou `em_combate` (com `chegada_em`). No card: `StatusBadge` do incêndio + selo de fase e área.
Frontend usa toggle segmentado (padrão da página de administração) para alternar entre aba "Brigadas" (cards + Nova Brigada) e aba "Despachos" (despachos ativos, histórico finalizado, botão Despachar).
Frontend consome API (`POST /api/brigadas`, `PUT`, `DELETE`) para operações de escrita, `GET /api/brigadas/{brigada}` para detalhes com membros (sob demanda via Dialog), e `PATCH /api/usuarios/{usuario}/brigada` para vincular/desvincular membros ao salvar.
Formulário de criar/editar: nome, tipo, disponível, seleção de membros (com busca). Coordenadas não são definidas na criação — são atualizadas via despacho.
Dialog de despacho em 2 etapas: (1) selecionar incêndio ativo/contido, (2) selecionar múltiplas brigadas disponíveis. Ao confirmar, para cada brigada selecionada executa `POST /api/incendios/{id}/despachos` + `PATCH /api/brigadas/{id}/localizacao` com coordenadas do incêndio. Brigadas indisponíveis aparecem desabilitadas visualmente.
Dialog de gerenciamento de status: ao clicar num despacho (gestor/administrador), abre dialog com detalhes e botão para avançar status (registrar chegada → finalizar com observações opcionais).

### AdministracaoController (Inertia)

- `GET /administracao` — web `auth`, `verified`, `nao-bloqueado`, `funcao:gestor|administrador` — lista usuários e logs (paginado) para a UI de administração.

### DeteccaoSateliteController

- `GET  /api/deteccoes-satelite` — auth:sanctum (gestor, administrador)
- `POST /api/deteccoes-satelite` — auth:sanctum (administrador)
- `GET  /api/deteccoes-satelite/{deteccao}` — auth:sanctum (gestor, administrador)
- `POST /api/deteccoes-satelite/lote` — auth:sanctum (administrador)

Registros imutáveis — sem update e destroy por design.
Ingestão individual e em lote (máx 500 por requisição).
storeLote atômico via DB::transaction().
Filtros por fonte, confiança mínima e intervalo de data.
Integração real NASA FIRMS pendente — será implementada como Job/Service.
Log de auditoria em store e storeLote.
Papéis: middleware `funcao` + `nao-bloqueado` (ver `routes/api.php`).

### IncendioController

- `GET   /api/incendios` — auth:sanctum (brigadista, gestor, administrador)
- `POST  /api/incendios` — auth:sanctum (brigadista, gestor, administrador)
- `GET   /api/incendios/{incendio}` — auth:sanctum (brigadista, gestor, administrador)
- `GET   /api/incendios/{incendio}/historico` — auth:sanctum (brigadista, gestor, administrador) — linha do tempo (registro, logs, despachos, métricas)
- `PUT   /api/incendios/{incendio}` — auth:sanctum (gestor, administrador)
- `DELETE /api/incendios/{incendio}` — auth:sanctum (gestor, administrador) — soft delete; bloqueado (409) se existir despacho com `finalizado_em` nulo
- `PATCH /api/incendios/{incendio}/status` — auth:sanctum (gestor, administrador)
- `PATCH /api/incendios/{incendio}/risco` — auth:sanctum (gestor, administrador)
- `POST  /api/incendios/{id}/restore` — auth:sanctum (gestor, administrador) — restaura apenas ocorrência **excluída logicamente**

`DELETE` e `restore` registram auditoria (`remocao_incendio`, `restauracao_incendio`).
usuario_id sempre do usuário autenticado — nunca do payload.
status não aceito em store nem update — endpoint dedicado.
Status segue fluxo linear: `ativo` → `em_combate` → `contido` → `resolvido`. Transições fora de ordem são rejeitadas (422). O status `em_combate` é atingido automaticamente quando a primeira brigada registra chegada no local (`DespachoBrigadaController::registrarChegada`).
Eager load de area, localCritico, deteccaoSatelite, usuario.
Log de auditoria em registro, atualização, mudança de status e risco.
Papéis: middleware `funcao` + `nao-bloqueado` (ver `routes/api.php`).

### MapaPageController (Inertia)

- `GET /mapa` — web `auth`, `verified`, `nao-bloqueado` — exibe mapa Leaflet com incêndios reais do banco.

Props Inertia: `incendios` (ativos, em combate, contidos e resolvidos com área e local crítico), `condicoesClimaticas` — mesmo payload que o clima do dashboard (`temperatura_c`, `umidade_pct`, `atualizado_em` via **OpenMeteo**, cache de 15 minutos, coordenadas em `config/services.php` → `open_meteo`) ou `null` se a API falhar; `podeGerenciar` (boolean — gestor/administrador).
Frontend: mapa Leaflet com marcadores coloridos por status, popup com detalhes, dialog com toggle Detalhes/Histórico (histórico via `GET /api/incendios/{incendio}/historico` quando aplicável), painel lateral de ocorrências, card de condições climáticas. Legenda inclui Ativo, Em Combate, Contido, Resolvido.

### LeituraMeteorologicaController

- `GET  /api/incendios/{incendio}/leituras` — auth:sanctum (brigadista, gestor, administrador)
- `POST /api/incendios/{incendio}/leituras` — auth:sanctum (brigadista, gestor, administrador)
- `GET  /api/incendios/{incendio}/leituras/{leitura}` — auth:sanctum (brigadista, gestor, administrador)

Rotas aninhadas — leituras existem apenas no contexto de um incêndio.
Sem update e destroy — registros de contexto imutáveis.
incendio_id sempre da rota — nunca do payload.
Threshold de alerta avaliado automaticamente: temperatura > 30°C ou umidade < 40%.
Log de auditoria em store.
Integração real OpenMeteo pendente — será implementada como Job/Service.
Papéis: middleware `funcao` + `nao-bloqueado`.

### DespachoBrigadaController

- `GET   /api/incendios/{incendio}/despachos` — auth:sanctum (brigadista, gestor, administrador)
- `POST  /api/incendios/{incendio}/despachos` — auth:sanctum (gestor, administrador)
- `GET   /api/incendios/{incendio}/despachos/{despacho}` — auth:sanctum (brigadista, gestor, administrador)
- `PATCH /api/incendios/{incendio}/despachos/{despacho}/chegada` — auth:sanctum (gestor, administrador)
- `PATCH /api/incendios/{incendio}/despachos/{despacho}/finalizar` — auth:sanctum (gestor, administrador)

Rotas aninhadas — despachos existem apenas no contexto de um incêndio.
Sem update genérico e sem destroy — registros operacionais históricos.
Ciclo de vida: despacho → chegada → finalização.
CHECK de timeline garantido no banco — não replicado no controller.
store bloqueia brigada já despachada sem finalização (409).
store marca brigada indisponível. finalizar marca brigada disponível.
tempo_resposta_minutos calculado no resource.
Log de auditoria em store, registrarChegada e finalizar.
Papéis: middleware `funcao` + `nao-bloqueado`.

### AlertaController

- `GET   /api/alertas` — auth:sanctum (brigadista, gestor, administrador)
- `GET   /api/alertas/{alerta}` — auth:sanctum (brigadista, gestor, administrador)
- `PATCH /api/alertas/{alerta}/entregue` — auth:sanctum (brigadista, gestor, administrador)

Somente leitura + patch — criação via Observer/Job (pendente implementação).
Tabela polimórfica sem FKs — origem_id e origem_tabela expostos diretamente.
marcarEntregue bloqueia se já entregue (422).
Log de auditoria em marcarEntregue.
Papéis: middleware `funcao` + `nao-bloqueado`.

### LogAuditoriaController

- `GET /api/logs-auditoria` — auth:sanctum (administrador)
- `GET /api/logs-auditoria/{log}` — auth:sanctum (administrador)

Somente leitura — registros imutáveis por design.
Paginação de 50 por página.
Filtros por ação, entidade_tipo, entidade_id, usuario_id e intervalo de data.
dados_json exposto como array — cast JSONB.
UsuarioResource em modo completo.
Papéis: middleware `funcao` + `nao-bloqueado`.

---

## Dívida técnica

| Item                                           | Localização                                                       | Descrição                                                                                                                                                                                            |
| ---------------------------------------------- | ----------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `UsuarioResource` flag `$somenteMembroBrigada` | `app/Http/Resources/UsuarioResource.php`                          | Argumento opcional adicionado fora do escopo. Documentado na seção Resources. Será polido após implementação de todos os controllers.                                                                |
| Shapefile (.shp) solto                         | `app/Services/GeoConverterService.php`                             | Upload único de `.shp` pode falhar sem `.dbf`/`.shx`; preferir ZIP com todos os ficheiros.                                                                                                        |
| `AuthController` Sanctum vs Fortify            | `app/Http/Controllers/AuthController.php`                         | AuthController foi construído para API stateless. O fluxo web migrou para Fortify. O controller permanece para uso futuro mobile. Testes do AuthController testam a camada de API — não o fluxo web. |
