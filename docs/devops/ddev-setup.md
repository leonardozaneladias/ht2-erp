---
titulo: Guia DDEV + OrbStack — Instalar, Configurar e Rodar
versao: 1.0.0
data: 2026-06-02
publico: Desenvolvedores, QA, SRE
status: aprovado
---

# Guia DDEV + OrbStack

Este é o guia **prático e copiável** para instalar, configurar e rodar o ambiente
de desenvolvimento deste projeto — e de qualquer projeto novo criado a partir
deste boilerplate. O ambiente oficial é o **[DDEV](https://docs.ddev.com/)**, com
**OrbStack** como provider Docker no macOS.

> Toda a configuração do ambiente vive versionada em `.ddev/`. Quem clona o repo
> obtém exatamente o mesmo ambiente — sem "na minha máquina funciona".

Sumário:

1. [Pré-requisitos (instalar uma vez por máquina)](#1-pré-requisitos)
2. [Rodar este projeto (do clone ao login)](#2-rodar-este-projeto)
3. [HTTPS confiável (mkcert)](#3-https-confiável-mkcert)
4. [O que fica versionado em `.ddev/`](#4-o-que-fica-versionado-em-ddev)
5. [Iniciar um projeto novo a partir do boilerplate](#5-iniciar-um-projeto-novo)
6. [Comandos do dia a dia](#6-comandos-do-dia-a-dia)
7. [Troubleshooting (problemas reais já encontrados)](#7-troubleshooting)

---

## 1. Pré-requisitos

Apenas **OrbStack** e **DDEV** no host. PHP, Composer, Node e npm vêm dentro do
container — não instale no host.

### 1.1 macOS (recomendado)

```bash
brew install orbstack          # provider Docker rápido e leve
brew install ddev/ddev/ddev
```

Depois **abra o app OrbStack uma vez**, selecione **"Docker"** e aprove o helper
privilegiado (pede senha de admin). A partir daí o OrbStack assume o contexto
Docker automaticamente (não precisa de `docker context use`).

> **Se o `brew install` falhar com "/opt/homebrew is not writable":** o Homebrew
> está com permissões quebradas. Conserte com
> `sudo chown -R "$(whoami)" /opt/homebrew` e rode o install de novo.

### 1.2 Windows / Linux

DDEV é cross-platform; o `.ddev/` do repo é idêntico. Use **Docker Desktop**
(Windows com WSL2, ou macOS) ou **Docker Engine** nativo (Linux) no lugar do
OrbStack. Instale o DDEV pela [doc oficial](https://docs.ddev.com/en/stable/users/install/).

### 1.3 Conferir

```bash
orb version          # OrbStack (macOS)
ddev version         # 1.24+
docker context show  # deve ser "orbstack" (macOS) ou "desktop-linux"
```

---

## 2. Rodar este projeto

```bash
git clone <repo> && cd erp

cp .env.example .env     # já vem alinhado ao DDEV (DB/Redis/Mailpit)

ddev start               # sobe containers + hooks (composer install, migrate, npm install)
make setup               # 1x: APP_KEY, migrate --seed, assets Horizon/Pulse, build de produção

ddev launch              # abre https://ht2ml-platform.ddev.site no navegador
```

Login admin: **`admin@example.com` / `password`** (e `gestor@example.com` / `password`).

O que o `ddev start` faz:

1. Sobe os containers: `web` (PHP 8.4 + Nginx + Node 22), `db` (PostgreSQL 16), `redis`.
2. Reescreve as credenciais de banco no `.env` (project type `laravel` → `db`/`db`/`db`).
3. Inicia o **Horizon** como daemon persistente (`web_extra_daemons`).
4. Roda os hooks `post-start`: `composer install`, `php artisan migrate --force`, `npm install`.

O `make setup` cobre o que **não** é idempotente (chave, seed, publicação de
assets de Horizon/Pulse, build) — rode-o **uma vez** após o primeiro `ddev start`.

### Verificar que está tudo no ar

```bash
ddev describe                                   # web/db/redis = OK
ddev exec supervisorctl status | grep horizon   # webextradaemons:horizon RUNNING
ddev mailpit                                    # abre a UI de e-mails
```

| Recurso | URL / Comando                        |
| ------- | ------------------------------------ |
| App     | https://ht2ml-platform.ddev.site            |
| Horizon | https://ht2ml-platform.ddev.site/horizon    |
| Pulse   | https://ht2ml-platform.ddev.site/pulse      |
| Mailpit | `ddev mailpit`                       |
| Vite    | `make dev` → `…ddev.site:5173` (HMR) |
| Banco   | `ddev psql`                          |

---

## 3. HTTPS confiável (mkcert)

Na primeira vez, o `https://*.ddev.site` aparece como "não confiável" até instalar
a CA local do mkcert no trust store do sistema:

```bash
mkcert -install        # pede senha de admin; instala a CA no system trust store
ddev restart           # regenera/reconhece o certificado confiável
```

Para **Firefox** (que usa um trust store próprio), instale também o `certutil` e
rode de novo:

```bash
brew install nss && mkcert -install
```

Sem isso o ambiente funciona igual — só mostra aviso de certificado no navegador.

---

## 4. O que fica versionado em `.ddev/`

O DDEV gera muitos arquivos transitórios em `.ddev/`, mas o próprio
`.ddev/.gitignore` (gerado por ele) ignora tudo que é efêmero. **Versionados** ficam
apenas:

- **`.ddev/config.yaml`** — a configuração do projeto.
- **`.ddev/docker-compose.redis.yaml`** — o serviço Redis (cache/sessão/fila/broadcast).

Principais escolhas em `config.yaml` (e o porquê):

| Campo                                                  | Valor                            | Motivo                                                                                                                                                                                                                                                                                                                          |
| ------------------------------------------------------ | -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `type` / `php_version` / `database` / `nodejs_version` | laravel / 8.4 / postgres 16 / 22 | Stack do projeto                                                                                                                                                                                                                                                                                                                |
| `performance_mode`                                     | `none`                           | O projeto vive em `/Users/Shared`; o Mutagen tenta criar um diretório de staging no diretório **pai** (`/Users/Shared/projects/GDF`), que não é gravável → `permission denied`. Bind mounts diretos evitam isso. Se mover o projeto para uma pasta do seu usuário, pode remover esta linha e usar o Mutagen (default no macOS). |
| `web_extra_daemons`                                    | `php artisan horizon`            | Horizon roda como daemon persistente no container web                                                                                                                                                                                                                                                                           |
| `web_extra_exposed_ports`                              | vite 5173                        | Expõe o Vite dev server (HMR) em `…ddev.site:5173`                                                                                                                                                                                                                                                                              |
| `hooks.post-start`                                     | composer/migrate/npm             | Automatiza deps + schema a cada `ddev start` (sem seed — não é idempotente)                                                                                                                                                                                                                                                     |

---

## 5. Iniciar um projeto novo

A partir deste boilerplate, para começar um projeto novo:

```bash
./bin/init-project.sh        # interativo: pergunta nome, slug e domínio de e-mail
```

O script renomeia marca/slug em `composer.json`, `package.json`, `.env.example`,
**`.ddev/config.yaml`** (`name: <slug>` → define `https://<slug>.ddev.site`),
`README.md`, `CLAUDE.md`, `AGENTS.md`, e ajusta `APP_URL`. Depois:

```bash
cp .env.example .env
ddev start && make setup
mkcert -install              # se ainda não fez nesta máquina
ddev launch                  # https://<slug>.ddev.site
```

> Para fazer manualmente sem o script: edite `name:` em `.ddev/config.yaml` e
> `APP_URL` no `.env` para o mesmo slug, e rode `ddev restart`.

---

## 6. Comandos do dia a dia

Atalhos via `Makefile` (wrappers do `ddev`):

```bash
make up          # ddev start
make down        # ddev stop
make bash        # shell no container web (ddev ssh)
make dev         # Vite dev server (HMR)
make fresh       # migrate:fresh --seed (recria o banco)
make test        # php artisan test
make lint        # Pint + Prettier
make quality     # Lint + PHPStan + Test
make horizon     # reinicia o daemon do Horizon
make setup       # setup inicial (key, seed, assets, build)
```

Direto pelo `ddev`:

```bash
ddev artisan <cmd>      ddev composer <cmd>      ddev npm <cmd>
ddev ssh                ddev psql                ddev logs -f
ddev restart            ddev describe            ddev mailpit
```

---

## 7. Troubleshooting

### `brew install` falha com "/opt/homebrew is not writable"

Permissões do Homebrew quebradas. Fix: `sudo chown -R "$(whoami)" /opt/homebrew`.

### `ddev start` falha com "port 5172 (ou 80/443) already in use"

Há outra instância do DDEV segurando as portas — geralmente o **mesmo projeto
rodando em outro provider** (ex.: trocou do Docker Desktop para o OrbStack). Pare
no provider antigo e suba no novo:

```bash
docker context use desktop-linux && ddev poweroff   # libera as portas no provider antigo
docker context use orbstack && ddev start
```

### Troquei de provider e o banco está vazio / sem usuários

Cada provider (Docker Desktop vs OrbStack) tem **volumes próprios** — o banco do
OrbStack começa zerado. Re-semeie:

```bash
make fresh        # ou: ddev artisan db:seed --force
```

### HTTPS "não confiável" no navegador

Falta instalar a CA do mkcert: `mkcert -install && ddev restart`. Para Firefox:
`brew install nss && mkcert -install`.

### Mutagen falhando ("permission denied" criando staging)

O projeto está numa pasta cujo **diretório pai** não é gravável (ex.: `/Users/Shared`).
Mantenha `performance_mode: none` em `.ddev/config.yaml` (já é o default deste repo).

### Horizon não processa jobs / mudei `config/horizon.php`

```bash
make horizon      # reinicia o daemon
ddev exec supervisorctl status
```

### Vite manifest ausente (`Unable to locate file in Vite manifest`)

```bash
ddev npm run build      # gera public/build/manifest.json
# ou, com HMR:  make dev
```

### Recriar o ambiente do zero (apaga dados locais)

```bash
ddev delete -O && ddev start && make setup
```

---

## Referências

- [`docs/devops/dev-setup.md`](dev-setup.md) — setup detalhado, editor, workflow diário, FAQ
- [`docs/devops/infra.md`](infra.md) — serviços, portas, Vite/Horizon, troubleshooting
- [docs.ddev.com](https://docs.ddev.com/) — documentação oficial do DDEV
- [orbstack.dev](https://orbstack.dev/) — OrbStack

Última atualização: 2026-06-02.
