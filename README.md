# Gateway Service

🇬🇧 English · [🇷🇺 Русский](#русский)

API gateway of a small microservice platform (fictional logistics company, portfolio project), Laravel 11:
authentication, authorisation and a reverse proxy in front of [`order-service`](https://github.com/aidar13/order-service) and [`notification-service`](https://github.com/aidar13/notification-service).

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

**Authorisation** has two independent checks:

1. **User** — roles and permissions (`spatie/laravel-permission`). Routes may declare `x-permission`.
2. **Application** — **scopes** of the OAuth client. A private route is reachable only if an active scope of the calling client covers it: the whole service (including routes imported later) or that single route. A token can be narrowed with the standard `scope` parameter. Deny by default.

Deactivating a user, a scope or a client takes effect immediately.

**Administration API** (`/api/admin`) — every action has its own permission, so duties can be split. The `admin` role holds all of them.

| Area | Permission | Endpoints |
|---|---|---|
| Services | `services.view` | GET `services`, GET `services/{id}` |
|  | `services.create` | POST `services` |
|  | `services.update` | PUT/PATCH `services/{id}` |
|  | `services.delete` | DELETE `services/{id}` |
|  | `services.import-routes` | POST `services/{id}/import-routes` |
| Routes | `routes.view` | GET `routes` |
| Roles | `roles.view` | GET `roles`, GET `roles/{id}` |
|  | `roles.create` | POST `roles` |
|  | `roles.update` | PUT/PATCH `roles/{id}` |
|  | `roles.delete` | DELETE `roles/{id}` |
| Permissions | `permissions.view` | GET `permissions`, GET `permissions/{id}` |
|  | `permissions.create` | POST `permissions` |
|  | `permissions.update` | PUT/PATCH `permissions/{id}` |
|  | `permissions.delete` | DELETE `permissions/{id}` |
| Users | `users.assign-roles` | PUT `users/{id}/roles` |
| Scopes | `scopes.view` | GET `scopes`, GET `scopes/{id}` |
|  | `scopes.create` | POST `scopes` |
|  | `scopes.update` | PUT/PATCH `scopes/{id}` |
|  | `scopes.delete` | DELETE `scopes/{id}` |
|  | `scopes.bind` | PUT `scopes/{id}/services/{serviceId}`, PUT `scopes/{id}/routes/{routeId}` |
|  | `scopes.unbind` | DELETE of the same two |
| OAuth clients | `clients.view` | GET `clients`, GET `clients/{id}` |
|  | `clients.create` | POST `clients` (with `scope_ids` also needs `clients.assign-scopes`) |
|  | `clients.assign-scopes` | PUT `clients/{id}/scopes` |
|  | `clients.revoke` | DELETE `clients/{id}` |

Safety rules: system roles and permissions cannot be renamed or deleted, the `admin` role keeps all system permissions, the last active administrator cannot lose the role, the client in use cannot revoke itself, re-importing routes keeps their scope bindings. `roles.update` and `users.assign-roles` let their holder obtain any permission, so grant them as carefully as the `admin` role.

**Gateway**

- registry of microservices; routes are **imported from each service's OpenAPI document** (`x-unauthenticated`, `x-permission`); everything is private by default;
- `ANY /gateway/{service}/{path}`: only registered routes are forwarded;
- identity is passed as `X-User`, `X-User-Email`, `X-User-Phone`, `X-User-Roles` plus an internal `X-Api-Key`; caller headers are copied by **allow-list**, so identity cannot be spoofed; the user's bearer token never reaches downstream services;
- raw bodies are forwarded byte for byte (webhook signatures stay valid), multipart uploads are rebuilt, outages become `502`.

## Architecture

```
Controller → FormRequest → Command → Handler → Contract → Repository / adapter → Model
Auth:     IssueOtpHandler / ResendOtpHandler ─ event OtpIssuedEvent → listener → encrypted job DeliverOtp → NotificationClient
          OtpGrant (/oauth/token) → VerifyOtpCommand
Gateway:  ProxyController → ProxyRequestHandler → ServiceRepository → RouteMatcher → ClientScopeGate + permission → UpstreamClient
Admin:    controllers → commands (Access, Gateway, Scope, Client) → handlers → repository contracts
          ImportServiceRoutesHandler → OpenApiFetcher → OpenApiParser → ServiceRepository
```

| Principle | Where |
|---|---|
| SRP | matching, parsing, forwarding and authorisation are separate classes; one command and handler per use case |
| OCP | a new sign-in method = a new OAuth grant class; a new delivery transport = a new `NotificationClient` |
| DIP | handlers depend on contracts bound in service providers |
| DRY | one token endpoint for every sign-in method; one `OtpIssuedEvent` path for first send and resend |
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

# admin: register a service, import its routes, switch it off
curl -X POST  $GW/api/admin/services "${A[@]}" -d '{"name":"billing","base_url":"http://billing"}'
curl -X POST  $GW/api/admin/services/2/import-routes "${A[@]}"
curl -X PATCH $GW/api/admin/services/2 "${A[@]}" -d '{"is_active":false}'
curl "$GW/api/admin/routes?service_id=2&search=invoice" "${A[@]}"

# admin: roles and permissions
curl -X POST $GW/api/admin/permissions "${A[@]}" -d '{"name":"reports.view"}'
curl -X POST $GW/api/admin/roles "${A[@]}" -d '{"name":"manager","permissions":["reports.view"]}'
curl -X PUT  $GW/api/admin/users/5/roles "${A[@]}" -d '{"roles":["manager"]}'

# admin: scopes and applications
curl -X POST $GW/api/admin/scopes "${A[@]}" -d '{"name":"orders:read","description":"Read orders"}'
curl -X PUT  $GW/api/admin/scopes/2/routes/3 "${A[@]}"          # one route
curl -X PUT  $GW/api/admin/scopes/2/services/2 "${A[@]}"        # or a whole service
curl -X POST $GW/api/admin/clients "${A[@]}" -d '{"name":"mobile","scope_ids":[2]}'   # secret is shown once

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

**Авторизация — две независимые проверки.** Пользователь: роли и права (spatie), маршрут может требовать `x-permission`. Приложение: **скоупы** OAuth-клиента — приватный маршрут доступен, только если активный скоуп клиента покрывает весь сервис (включая новые маршруты) или конкретный маршрут; токен можно сузить параметром `scope`; по умолчанию доступ закрыт.

**Админ-API** (`/api/admin`): сервисы и маршруты, роли, права, роли пользователей, скоупы с привязками, OAuth-клиенты. На каждое действие — отдельное право (`services.view`, `services.create`, `roles.update`, `scopes.bind`, `clients.revoke` и т. д., полный список в таблице выше); роль `admin` имеет все. Создать клиента сразу со скоупами можно только с правом `clients.assign-scopes`. Защиты: системные роли и права нельзя удалить или переименовать, роль `admin` сохраняет все системные права, последний администратор не может лишиться роли, нельзя отозвать клиента, через которого вошёл, повторный импорт маршрутов сохраняет привязки к скоупам. `roles.update` и `users.assign-roles` позволяют получить любое право, поэтому выдавать их нужно так же осторожно, как роль `admin`.

**Шлюз:** реестр микросервисов; маршруты импортируются из OpenAPI каждого сервиса (`x-unauthenticated`, `x-permission`), по умолчанию всё закрыто; проксируются только зарегистрированные маршруты; личность передаётся заголовками `X-User*` и внутренним ключом, заголовки клиента копируются по белому списку (подделать личность нельзя), токен пользователя вниз не уходит; тело передаётся без изменений (подпись вебхука сохраняется).

**Архитектура:** Controller → FormRequest → Command → Handler → контракт → репозиторий/адаптер. Event `OtpIssuedEvent` → listener → зашифрованный Job. SOLID: отдельные классы на сопоставление, разбор, проксирование (SRP), новый способ входа — новый grant (OCP), зависимости на интерфейсы (DIP).

**Запуск всей платформы** (три репозитория рядом): `./scripts/platform-up.sh`, затем `./scripts/smoke.sh` — сквозной сценарий «OTP → заказ → оплата → письма». Проверки: `composer test`, `composer analyse`, `composer lint`.
