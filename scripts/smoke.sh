#!/usr/bin/env sh
# End-to-end check through the gateway: OTP sign-in (e-mail and SMS) → order → payment → e-mails.
# Note: the gateway allows 10 sign-in requests per minute per IP, so wait a minute between repeated runs.
set -eu
cd "$(dirname "$0")/.."

GW=${GW:-http://localhost:8000}
MAIL=${MAIL:-http://localhost:8025}
CLIENT_SECRET=local-web-client-secret
WEBHOOK_SECRET=local-webhook-secret
EMAIL="smoke-$(date +%s)@example.test"
dc() { docker compose -p portfolio-platform --env-file .env.platform -f docker-compose.platform.yml "$@"; }

json() { python3 -c "import sys,json; d=json.load(sys.stdin); print($1)"; }
req() { curl -sS --fail-with-body -H 'Accept: application/json' -H 'Content-Type: application/json' "$@"; }
mail_text() { # newest message for $EMAIL whose subject contains $1
    for _ in $(seq 1 30); do
        id=$(curl -sS "$MAIL/api/v1/search?query=to:$EMAIL" | json "next((m['ID'] for m in d['messages'] if '$1' in m['Subject']), '')")
        [ -n "$id" ] && { curl -sS "$MAIL/api/v1/message/$id" | json "d['Text']"; return 0; }
        sleep 1
    done
    echo "no e-mail with subject '$1'" >&2; return 1
}

CLIENT_ID=$(dc exec -T mysql mysql -uapp -psecret -N gateway -e 'select id from oauth_clients where password_client = 1 limit 1' 2>/dev/null | tr -d '\r')

echo "1. request a one-time code for $EMAIL"
HASH=$(req -X POST "$GW/api/auth/code" -d "{\"email\":\"$EMAIL\"}" | json "d['data']['hash']")

echo "2. read the code from the e-mail"
CODE=$(mail_text 'sign-in code' | grep -oE '[0-9]{6}' | head -1)

echo "3. exchange hash + code for a token"
TOKEN=$(req -X POST "$GW/oauth/token" -d "{\"grant_type\":\"hash_and_code\",\"client_id\":\"$CLIENT_ID\",\"client_secret\":\"$CLIENT_SECRET\",\"hash\":\"$HASH\",\"code\":\"$CODE\"}" | json "d['access_token']")

echo "3b. sign in by phone: the code goes out as SMS (written to the notification log)"
PHONE="+1555$(date +%s | tail -c 8)"
PHONE_HASH=$(req -X POST "$GW/api/auth/code" -d "{\"phone\":\"$PHONE\"}" | json "d['data']['hash']")
SMS_CODE=''
for _ in $(seq 1 30); do
    SMS_CODE=$(grep -a "$PHONE" ../notification-service/storage/logs/laravel.log 2>/dev/null | grep -oE 'Code: [0-9]{6}' | tail -1 | grep -oE '[0-9]{6}' || true)
    [ -n "$SMS_CODE" ] && break
    sleep 1
done
[ -n "$SMS_CODE" ] || { echo "no SMS for $PHONE" >&2; exit 1; }
req -X POST "$GW/oauth/token" -d "{\"grant_type\":\"hash_and_code\",\"client_id\":\"$CLIENT_ID\",\"client_secret\":\"$CLIENT_SECRET\",\"hash\":\"$PHONE_HASH\",\"code\":\"$SMS_CODE\"}" | json "'   token type: ' + d['token_type']"

echo "4. password login of the seeded admin"
req -X POST "$GW/oauth/token" -d "{\"grant_type\":\"password\",\"client_id\":\"$CLIENT_ID\",\"client_secret\":\"$CLIENT_SECRET\",\"username\":\"admin\",\"password\":\"admin-password-123\"}" | json "'   token type: ' + d['token_type']"

echo "5. anonymous request to a protected route is refused"
status=$(curl -s -o /dev/null -w '%{http_code}' -H 'Accept: application/json' "$GW/gateway/orders/api/orders")
[ "$status" = 401 ] || { echo "expected 401, got $status" >&2; exit 1; }

echo "6. create an order through the gateway"
ORDER=$(req -X POST "$GW/gateway/orders/api/orders" -H "Authorization: Bearer $TOKEN" -d '{"delivery_address":"12 Lake St","items":[{"sku":"BOX-M","quantity":3}]}')
ORDER_ID=$(echo "$ORDER" | json "d['data']['id']")
TOTAL=$(echo "$ORDER" | json "d['data']['total_cents']")

echo "7. start a payment"
PAYMENT=$(req -X POST "$GW/gateway/orders/api/orders/$ORDER_ID/pay" -H "Authorization: Bearer $TOKEN" -H 'Idempotency-Key: smoke-1' -d '{"provider":"fakepay"}' | json "d['data']['id']")

echo "8. provider calls the webhook (signed raw body, passed through the gateway)"
BODY="{\"payment_id\":\"$PAYMENT\",\"status\":\"succeeded\",\"amount_cents\":$TOTAL}"
SIGNATURE=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" | sed 's/^.* //')
req -X POST "$GW/gateway/orders/api/payments/webhook/fakepay" -H "X-Signature: $SIGNATURE" -d "$BODY" | json "'   payment: ' + d['status']"

echo "9. order is paid"
STATUS=$(req "$GW/gateway/orders/api/orders/$ORDER_ID" -H "Authorization: Bearer $TOKEN" | json "d['data']['status']")
[ "$STATUS" = paid ] || { echo "expected paid, got $STATUS" >&2; exit 1; }

echo "10. receipts arrived"
mail_text 'created' >/dev/null
mail_text 'Payment received' >/dev/null

echo "11. logout revokes the token"
curl -sS -o /dev/null -X POST "$GW/api/auth/logout" -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN"
status=$(curl -s -o /dev/null -w '%{http_code}' -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN" "$GW/gateway/orders/api/orders")
[ "$status" = 401 ] || { echo "expected 401 after logout, got $status" >&2; exit 1; }

echo "SMOKE TEST PASSED"
