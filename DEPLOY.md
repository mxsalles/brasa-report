# Deploy com Docker — brasa-report

## Estrutura de arquivos para adicionar ao repositório

```
brasa-report/
├── Dockerfile
├── docker-compose.prod.yml
├── render.yaml
├── .dockerignore
└── docker/
    ├── entrypoint.sh
    ├── nginx/
    │   ├── nginx.conf
    │   └── default.conf
    ├── php/
    │   ├── php.ini
    │   └── opcache.ini
    └── supervisor/
        └── supervisord.conf
```

---

## 1. Criar `.dockerignore` na raiz do projeto

```
node_modules
.git
.env
vendor
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
bootstrap/cache/*
```

---

## 2. Configurar o `.env` de produção no servidor

Copie o `.env.production` gerado para `.env` no servidor e ajuste:

```bash
# Campos obrigatórios para trocar:
APP_URL=https://seu-dominio-real.com
DB_PASSWORD=uma_senha_forte_aqui
```

> ⚠️ **Não commite o `.env` no git.** Adicione `.env` ao `.gitignore` se ainda não estiver.

---

## 3. Subir os containers

```bash
# Na raiz do projeto, com o .env configurado:
docker compose -f docker-compose.prod.yml up -d --build

# Acompanhar logs de inicialização:
docker compose -f docker-compose.prod.yml logs -f app
```

O `entrypoint.sh` vai automaticamente:
1. Aguardar o PostgreSQL iniciar
2. Rodar `config:cache`, `route:cache`, `view:cache`, `event:cache`
3. Rodar `php artisan migrate --force`
4. Confirmar que `deploy:seed` existe (`php artisan help deploy:seed`), senão encerra com erro e mostra a saída do Artisan
5. Rodar `php artisan deploy:seed`
6. Iniciar Nginx + PHP-FPM via Supervisor

---

## 4. Render (Docker)

- **Fonte única:** migrações e `deploy:seed` rodam no [`docker/entrypoint.sh`](docker/entrypoint.sh) depois do Postgres. Não configure **Pre-Deploy Command** / **Release Command** no painel com `migrate` + `deploy:seed` de novo: isso duplica trabalho e pode rodar em snapshot diferente do container durante o rollout.
- Opcional: use o [`render.yaml`](render.yaml) na raiz como Blueprint (ajuste `name`, `plan`, `region` ao vincular o repositório).
- **Shell / comandos manuais:** só depois do deploy aparecer como concluído e o serviço saudável; durante o rollout, instâncias podem estar em versões diferentes do código.

---

## 5. Deploy no Railway (recomendado para vocês)

O Railway suporta deploy direto via Dockerfile:

1. No painel Railway: **New Project → Deploy from GitHub repo**
2. Selecione o repositório `brasa-report`
3. Railway detecta o `Dockerfile` automaticamente
4. Adicione um serviço **PostgreSQL** pelo marketplace do Railway
5. Na aba **Variables** do serviço app, configure:
   ```
   APP_KEY=base64:7uJUI0ZEv7Khe7DZhkNgVPPd4EUL0mGFN2uyoqsTTCk=
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<dominio-gerado>.railway.app
   DB_CONNECTION=pgsql
   DB_HOST=<host-do-postgres-railway>
   DB_PORT=5432
   DB_DATABASE=railway
   DB_USERNAME=postgres
   DB_PASSWORD=<senha-gerada-pelo-railway>
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```
6. Railway faz o build e deploy automaticamente a cada push no `main`

> O Railway injeta as variáveis de ambiente diretamente, sem precisar do arquivo `.env` no servidor.

---

## 6. Atualizar após mudanças

```bash
git push origin main
# Railway faz rebuild automático

# Ou manualmente em VPS:
git pull
docker compose -f docker-compose.prod.yml up -d --build app
```

---

## 7. Troubleshooting

```bash
# Ver logs do app
docker compose -f docker-compose.prod.yml logs app

# Acessar o container
docker compose -f docker-compose.prod.yml exec app sh

# Rodar Artisan manualmente
docker compose -f docker-compose.prod.yml exec app php artisan migrate:status
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
```