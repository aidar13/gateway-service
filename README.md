# Gateway Service

🇬🇧 English · [🇷🇺 Русский](#русский)

API gateway of a small microservice platform (fictional logistics company, portfolio project), Laravel 11:
authentication, authorisation and a reverse proxy in front of [`order-service`](../order-service) and [`notification-service`](../notification-service).

## Features

**Authentication — OAuth2 (Laravel Passport), one token endpoint**

| Grant | Use |
|---|---|
| `password` | sign in with a **login** (username, e-mail or phone) and password |
| `hash_and_code` (custom grant) | sign in with a **one-time code** sent by SMS or e-mail |
| `refresh_token` | rotate tokens |

**One-time codes, designed against abuse**

- only a hash of the code is stored; the queued delivery job is encrypted;
- TTL 5 min, single use, **attempt budget** (counted atomically, not refilled by "resend"), resend cooldown and limit, rate limiting;
- the account is created only **after** the code is confirmed; such accounts have no password, so password login is impossible for them;
- **pre-hijacking guard**: if a contact was registered but never verified, the first code sign-in hands the account to its real owner — password removed, other unverified contacts dropped, old tokens revoked.

**Authorisation** — roles and permissions (`spatie/laravel-permission`): `admin`, `client`; admin API is guarded by `gateway.manage`; deactivating a user takes effect immediately.

**Gateway**

- registry of microservices; routes are **imported from each service's OpenAPI document** (`x-unauthenticated`, `x-permission`); everything is private by default;
- `ANY /gateway/{service}/{path}`: only registered routes are forwarded;
- identity is passed as `X-User`, `X-User-Email`, `X-User-Phone`, `X-User-Roles` plus an internal `X-Api-Key`; caller headers are copied by **allow-list**, so identity cannot be spoofed; the user's bearer token never reaches downstream services;
- raw bodies are forwarded byte for byte (webhook signatures stay valid), multipart uploads are rebuilt, outages become `502`.

## Architecture

```
Controller → FormRequest → Command → Handler → Contract → Repository / adapter → Model
Auth:     IssueOtpHandler / ResendOtpHandler ─ event OtpIssued → listener → encrypted job DeliverOtp → NotificationClient
          OtpGrant (/oauth/token) → VerifyOtpCommand
Gateway:  ProxyController → ProxyRequestHandler → ServiceRepository → RouteMatcher → authorise → UpstreamClient
          ImportServiceRoutesHandler → OpenApiFetcher → OpenApiParser → ServiceRepository
```

| Principle | Where |
|---|---|
| SRP | matching, parsing, forwarding and authorisation are separate classes; one command and handler per use case |
| OCP | a new sign-in method = a new OAuth grant class; a new delivery transport = a new `NotificationClient` |
| DIP | handlers depend on contracts bound in service providers |
| DRY | one token endpoint for every sign-in method; one `OtpIssued` path for first send and resend |
| KISS | one catch-all proxy route and a ~60-line matcher instead of generating framework routes from the database |

## Commands and handlers

Every use case is a **command** (immutable data, `app/Commands`) handled by exactly one **handler** (`app/Handlers`). Callers depend on the `CommandBus` interface; `LaravelCommandBus` runs it on Laravel's bus synchronously, and the map lives in `CommandBusServiceProvider`. `Command<TResult>` is generic, so PHPStan knows what `dispatch()` returns, and a test fails the build if a command has no handler.

```php
$user = $this->bus->dispatch(new VerifyOtpCommand($hash, $code)); // User|null
```

## Run the whole platform

Clone the three repositories side by side, then:

```bash
./scripts/platform-up.sh   # build, migrate, seed, generate keys, import routes
./scripts/smoke.sh         # OTP sign-in → order → payment → e-mails, end to end
```

Gateway: http://localhost:8000 · Mailpit (all e-mails, including codes): http://localhost:8025 · SMS are written to `../notification-service/storage/logs/laravel.log`.
All secrets in `docker-compose.platform.yml` are throw-away local values.

Seeded admin: `admin` / `admin-password-123` (`SEED_ADMIN_PASSWORD`). OAuth client: id printed by the seeder, secret `SEED_CLIENT_SECRET`.

## API examples

```bash
GW=localhost:8000
J=(-H "Content-Type: application/json" -H "Accept: application/json")

# register and sign in with a login
curl -X POST $GW/api/auth/register "${J[@]}" -d '{"name":"Jane","username":"jane","email":"jane@example.test","password":"password-123"}'
curl -X POST $GW/oauth/token "${J[@]}" -d '{"grant_type":"password","client_id":"<id>","client_secret":"local-web-client-secret","username":"jane","password":"password-123"}'

# sign in with a one-time code
curl -X POST $GW/api/auth/code "${J[@]}" -d '{"phone":"+15550001122"}'          # → {"data":{"hash":"…"}}
curl -X POST $GW/api/auth/code-repeat "${J[@]}" -d '{"hash":"<hash>"}'
curl -X POST $GW/oauth/token "${J[@]}" -d '{"grant_type":"hash_and_code","client_id":"<id>","client_secret":"local-web-client-secret","hash":"<hash>","code":"<code>"}'

A=(-H "Authorization: Bearer <access_token>" "${J[@]}")
curl $GW/api/auth/me "${A[@]}"
curl -X POST $GW/api/auth/logout "${A[@]}"

# admin: register a service and import its routes
curl -X POST $GW/api/admin/services "${A[@]}" -d '{"name":"billing","base_url":"http://billing"}'
curl -X POST $GW/api/admin/services/2/import-routes "${A[@]}"

# call a microservice through the gateway
curl $GW/gateway/orders/api/products "${J[@]}"
curl $GW/gateway/orders/api/orders "${A[@]}"
```

OpenAPI: `docs/openapi.json`, Swagger UI at `/docs` (`composer docs`).

## Quality tools

```bash
composer test      # PHPUnit (SQLite): both grants, OTP limits, registry, proxy security
composer analyse   # Larastan level 8
composer lint      # Pint
```

---

## Русский

API-шлюз небольшой микросервисной платформы (вымышленная логистика, проект для портфолио) на Laravel 11: аутентификация, авторизация и reverse proxy перед `order-service` и `notification-service`.

**Вход (OAuth2, Passport, единая точка `/oauth/token`):** по логину (username, e-mail или телефон) и паролю — grant `password`; по одноразовому коду из SMS/e-mail — собственный grant `hash_and_code`; обновление — `refresh_token`.

**Защита OTP:** хранится только хэш кода, job в очереди зашифрован; TTL 5 минут, одноразовость, лимит попыток (атомарный, повторная отправка его не восстанавливает), пауза и лимит на повтор, rate limit; аккаунт создаётся только после подтверждения кода и не имеет пароля. Если контакт был указан при регистрации, но не подтверждён, первый вход по коду передаёт аккаунт настоящему владельцу контакта (пароль и чужие сессии сбрасываются).

**Авторизация:** роли и права (spatie), админ-API под `gateway.manage`, деактивация действует сразу.

**Шлюз:** реестр микросервисов; маршруты импортируются из OpenAPI каждого сервиса (`x-unauthenticated`, `x-permission`), по умолчанию всё закрыто; проксируются только зарегистрированные маршруты; личность передаётся заголовками `X-User*` и внутренним ключом, заголовки клиента копируются по белому списку (подделать личность нельзя), токен пользователя вниз не уходит; тело передаётся без изменений (подпись вебхука сохраняется).

**Архитектура:** Controller → FormRequest → Command → Handler → контракт → репозиторий/адаптер. Event `OtpIssued` → listener → зашифрованный Job. SOLID: отдельные классы на сопоставление, разбор, проксирование (SRP), новый способ входа — новый grant (OCP), зависимости на интерфейсы (DIP).

**Запуск всей платформы** (три репозитория рядом): `./scripts/platform-up.sh`, затем `./scripts/smoke.sh` — сквозной сценарий «OTP → заказ → оплата → письма». Проверки: `composer test`, `composer analyse`, `composer lint`.
