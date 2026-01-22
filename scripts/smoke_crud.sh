#!/usr/bin/env bash
# Smoke tests for CRUD API (login, create, list, update, delete)
# Requirements: curl, python3

set -euo pipefail

BASE_URL=${BASE_URL:-"http://localhost/crud/api"}
ADMIN_LOGIN=${ADMIN_LOGIN:-"admin"}
ADMIN_PASS=${ADMIN_PASS:-"admin"}
START_SERVER=${START_SERVER:-0}
SERVER_PORT=${SERVER_PORT:-8006}
PROJECT_ROOT=${PROJECT_ROOT:-"$(cd "$(dirname "$0")/.." && pwd)"}

COOKIE_JAR=$(mktemp)
CLEANUP() {
  rm -f "$COOKIE_JAR"
  if [[ -n "${SERVER_PID:-}" ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
  fi
}
trap CLEANUP EXIT

log() { printf "[SMOKE] %s\n" "$*"; }

if [[ "$START_SERVER" == "1" ]]; then
  BASE_URL="http://127.0.0.1:${SERVER_PORT}/crud/api"
  log "Iniciando servidor embutido em ${BASE_URL%/crud/api}"
  php -S 127.0.0.1:"${SERVER_PORT}" -t "$PROJECT_ROOT" >/tmp/phpserver_accel.log 2>&1 &
  SERVER_PID=$!
  sleep 1
fi

parse_json() {
  local json="$1" key="$2"
  python3 - <<'PY'
import json,sys,os
json_str=os.environ['JSON_STR']
key=os.environ['JSON_KEY']
try:
    data=json.loads(json_str)
    val=data
    for part in key.split('.'):
        val=val[part]
    print(val)
except Exception:
    sys.exit(1)
PY
}

die() { echo "Erro: $*" >&2; exit 1; }

log "Login como ${ADMIN_LOGIN}"
LOGIN_RESP=$(curl -s -c "$COOKIE_JAR" -X POST \
  -d "login=${ADMIN_LOGIN}" -d "senha=${ADMIN_PASS}" \
  "$BASE_URL/login.php")
export JSON_STR="$LOGIN_RESP" JSON_KEY="csrf_token"
CSRF_TOKEN=$(parse_json "$LOGIN_RESP" "csrf_token") || die "Falha ao obter CSRF do login: $LOGIN_RESP"
export JSON_KEY="usuario.id"
ADMIN_ID=$(parse_json "$LOGIN_RESP" "usuario.id") || die "Falha ao obter ID do usuário: $LOGIN_RESP"
log "Autenticado. CSRF=${CSRF_TOKEN} ID=${ADMIN_ID}"

RANDOM_SUFFIX=$(date +%s)
NEW_LOGIN="tester_${RANDOM_SUFFIX}"
NEW_EMAIL="tester_${RANDOM_SUFFIX}@example.com"

log "Criando usuário ${NEW_LOGIN}"
CREATE_RESP=$(curl -s -b "$COOKIE_JAR" -X POST \
  -H "X-CSRF-Token: ${CSRF_TOKEN}" \
  -d "nome=Tester ${RANDOM_SUFFIX}" \
  -d "email=${NEW_EMAIL}" \
  -d "login=${NEW_LOGIN}" \
  -d "senha=teste123" \
  -d "cpf=${RANDOM_SUFFIX}" \
  -d "role=001" \
  "$BASE_URL/criar_usuario.php")
export JSON_STR="$CREATE_RESP" JSON_KEY="id"
NEW_USER_ID=$(parse_json "$CREATE_RESP" "id") || die "Falha ao criar usuário: $CREATE_RESP"
log "Usuário criado ID=${NEW_USER_ID}"

log "Listando usuários"
LIST_RESP=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/listar_usuario.php")
export JSON_STR="$LIST_RESP" JSON_KEY="csrf_token"
CSRF_TOKEN=$(parse_json "$LIST_RESP" "csrf_token") || die "Falha ao obter novo CSRF: $LIST_RESP"
log "Listagem OK (registros: $(python3 - <<'PY'
import json,os
try:
    data=json.loads(os.environ['JSON_STR'])
    print(len(data.get('data', [])))
except Exception:
    print('n/a')
PY
))"

echo "$LIST_RESP" > /tmp/list_resp.json

die_if_missing() {
  JSON_STR="$1" python3 - "${NEW_LOGIN}" <<'PY'
import json,sys,os
needle=sys.argv[1]
data=json.loads(os.environ['JSON_STR'])
if not any(u.get('login')==needle for u in data.get('data', [])):
    sys.exit(1)
PY
}
die_if_missing "$LIST_RESP" || die "Usuário criado não encontrado na listagem"

log "Atualizando usuário ${NEW_USER_ID}"
UPDATE_RESP=$(curl -s -b "$COOKIE_JAR" -X POST \
  -H "X-CSRF-Token: ${CSRF_TOKEN}" \
  -d "id=${NEW_USER_ID}" \
  -d "nome=Tester Atualizado" \
  "$BASE_URL/editar.php")
log "Update resposta: $UPDATE_RESP"

log "Excluindo usuário ${NEW_USER_ID}"
DELETE_RESP=$(curl -s -b "$COOKIE_JAR" -X POST \
  -H "X-CSRF-Token: ${CSRF_TOKEN}" \
  -d "id=${NEW_USER_ID}" \
  "$BASE_URL/excluir.php")
log "Delete resposta: $DELETE_RESP"

log "Smoke tests finalizados com sucesso"
