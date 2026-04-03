# BRASA — Documentação de Contexto

> Leia este arquivo antes de qualquer chat novo relacionado ao projeto.
> Ele consolida decisões de arquitetura, stack, schema e convenções adotadas pelo time.

---

## O que é o Brasa

Sistema de gestão e inteligência preditiva de incêndios florestais no **Pantanal brasileiro**. Desenvolvido por seis estudantes com impacto extensionista real — o sistema apoia brigadas de combate ao fogo, gestores ambientais e comunidades em áreas de risco.

**Objetivo central:** reduzir o tempo de resposta a incêndios e mapear áreas de maior risco de ignição, permitindo atuação preventiva em vez de reativa.

---

## Stack

| Camada            | Tecnologia                     |
| ----------------- | ------------------------------ |
| Backend           | PHP + Laravel 11               |
| Frontend          | ReactJS + Inertia.js           |
| Banco de dados    | PostgreSQL via Supabase        |
| API meteorológica | OpenMeteo                      |
| API de satélite   | NASA FIRMS                     |
| Dados geográficos | GeoPackage (importação manual) |

---

## Convenções do projeto

- **Idioma dos campos:** português (nomes de tabelas, colunas, enums)
- **UUIDs:** gerados via `gen_random_uuid()` — nativo do PostgreSQL 13+, sem extensões
- **Timestamps:** sempre `TIMESTAMPTZ` — nunca `TIMESTAMP` sem timezone (incompatível com Supabase)
- **Campos `criado_em` e `atualizado_em`:** autogeridos no banco via `DEFAULT NOW()` e trigger
- **Sem extensões externas:** `uuid-ossp` e `postgis` não são utilizados — coordenadas em `NUMERIC(10,7)`, geometria em WKT (`TEXT`)
- **Chaves primárias:** sempre `id UUID`
- **Soft delete:** não adotado — deleção física com `ON DELETE` explícito por tabela

---

## Módulos da aplicação

### Módulo 1 — Gestão de Usuários e Segurança

Autenticação, controle de acesso por papel e recuperação de senha.

### Módulo 2 — Monitoramento e Operações de Campo

Registro de incêndios, despacho de brigadas, importação de dados meteorológicos e geográficos, visualização em mapa.

### Módulo 3 — Relatórios e Inteligência

Análise histórica, tempo de resposta, zonas de risco. _(Relatórios são gerados no client — sem tabela de relatórios no banco.)_

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

**`alertas` é polimórfica sem FKs** — intencional. Os campos `origem_id (UUID)` + `origem_tabela (VARCHAR)` identificam a origem do alerta. O Eloquent gerencia via `morphTo`/`morphMany`. Não adicionar FKs nessa tabela.

**`leituras_meteorologicas` vinculada ao incêndio** — ao registrar um incêndio, as condições climáticas do momento são atreladas via `incendio_id`. A tabela não se relaciona com `areas_monitoradas`.

**`locais_criticos` é independente** — cadastrada previamente, associada opcionalmente ao incêndio via `local_critico_id` em `incendios`. Cálculo de distância feito no client.

**`despachos_brigada` tem CHECK de timeline** — banco garante `despachado_em ≤ chegada_em ≤ finalizado_em`. O campo `finalizado_em` registra o encerramento do combate.

**Sem tabela de relatórios** — geração de PDF/CSV é responsabilidade do client.

---

## O que já foi produzido

| Artefato                                       | Arquivo                      |
| ---------------------------------------------- | ---------------------------- |
| Schema DDL PostgreSQL final                    | `brasa_schema_postgres.sql`  |
| Prompt para geração de migrations (Laravel 11) | `prompt_migrations_brasa.md` |
| AuthController | `app/Http/Controllers/AuthController.php` |
| LoginRequest | `app/Http/Requests/LoginRequest.php` |
| UsuarioResource | `app/Http/Resources/UsuarioResource.php` |
| Testes de autenticação | `tests/Feature/Auth/AuthControllerTest.php` |

---

## O que ainda precisa ser feito

- [ ] Migrations Laravel (geradas a partir do `prompt_migrations_brasa.md`)
- [ ] Models Eloquent com relacionamentos
- [ ] Seeders de desenvolvimento
- [ ] Controllers e Form Requests
- [ ] Integração OpenMeteo
- [ ] Integração NASA FIRMS
- [x] Autenticação e middleware de papéis (AuthController — Sanctum)
- [ ] Visualização de mapa (frontend)
- [ ] Sistema de alertas (push + email)

---

## Controllers implementados

### AuthController
- `POST /api/auth/login` — público
- `POST /api/auth/logout` — auth:sanctum
- `GET  /api/auth/me` — auth:sanctum

Registra log de auditoria em login e logout.
Token Sanctum stateless. Campos sensíveis nunca expostos nas respostas.
