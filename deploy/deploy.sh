#!/usr/bin/env bash
# Deploy automático de um projeto Sysborg (gerado pela skill sysborg-auto-deploy).
# Uso: ./deploy.sh <sha>   ou, pelo CI, via forced command: ssh deploy@vps <sha>
set -Eeuo pipefail

# ---- Configuração do projeto ----
PROJECT="sbblog"
APP_SERVICE="sbblog-app"
APP_PORT="8080"
HEALTH_PATH="/up"
MIGRATE_CMD="php artisan migrate --force"   # Expand/contract only: a rollback does not undo the schema.
POST_DEPLOY_CMD="php artisan snapshots:build"   # Refresh frontend snapshots after the release.
BACKUP_DB="yes"       # "no" se o projeto não usa banco
KEEP_BACKUPS=10
KEEP_IMAGES=3
HEALTH_TRIES=30       # x 2s
CURL_IMAGE="curlimages/curl:8.10.1"
# ---------------------------------

DIR="/home/deploy/$PROJECT"
cd "$DIR"

TAG="${1:-${SSH_ORIGINAL_COMMAND:-}}"
if [[ ! "$TAG" =~ ^[0-9a-f]{7,40}$ ]]; then
  echo "uso: deploy.sh <sha do commit> (recebido: '$TAG')" >&2
  exit 2
fi

exec 9>"$DIR/.deploy.lock"
flock -n 9 || { echo "já existe um deploy em andamento" >&2; exit 3; }

log() { echo "[$(date -Is)] $*" | tee -a "$DIR/deploy.log"; }
env_get() { grep -E "^$1=" .env | tail -n1 | cut -d= -f2- || true; }
set_tag() { sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=$1/" .env; }
compose() { docker compose --ansi never "$@"; }

grep -q '^IMAGE_TAG=' .env || { echo "IMAGE_TAG ausente no .env" >&2; exit 4; }
PREV_TAG="$(env_get IMAGE_TAG)"
DB_FILE=""
STAGE="preparo"

rollback() {
  log "rollback para ${PREV_TAG:-<nenhuma>}"
  set_tag "$PREV_TAG"
  compose up -d --remove-orphans || log "ROLLBACK FALHOU: intervenção manual necessária"
}

on_error() {
  local code=$?
  # set -E herda o trap nos subshells dos pipes; só o shell principal trata o erro.
  [[ "$BASHPID" == "$$" ]] || exit "$code"
  trap - ERR
  log "ERRO na etapa '$STAGE' (código $code)"
  case "$STAGE" in
    preparo|migrations)
      set_tag "$PREV_TAG"
      log "containers não foram trocados; IMAGE_TAG voltou para $PREV_TAG" ;;
    troca)
      [[ -n "$PREV_TAG" ]] && rollback ;;
  esac
  if [[ "$STAGE" != "preparo" && -n "$DB_FILE" ]]; then
    log "migrations NÃO são revertidas automaticamente. Backup: $DIR/$DB_FILE"
  fi
  exit "$code"
}
trap on_error ERR

log "deploy $PROJECT: ${PREV_TAG:-<nenhuma>} -> $TAG"

if [[ "$BACKUP_DB" == "yes" ]]; then
  mkdir -p -m 700 backups
  DB_FILE="backups/$(date +%Y%m%d-%H%M%S)-${PREV_TAG:-inicial}.dump"
  docker exec -e PGPASSWORD="$(env_get DB_PASSWORD)" postgres \
    pg_dump -h 127.0.0.1 -U "$(env_get DB_USER)" -Fc "$(env_get DB_NAME)" > "$DB_FILE"
  chmod 600 "$DB_FILE"
  log "backup do banco: $DB_FILE"
  ls -1t backups/*.dump | tail -n +$((KEEP_BACKUPS + 1)) | xargs -r rm -- || true
fi

set_tag "$TAG"
compose pull --quiet

if [[ -n "$MIGRATE_CMD" ]]; then
  STAGE="migrations"
  log "migrations: $MIGRATE_CMD"
  # shellcheck disable=SC2086
  compose run --rm --no-deps -T "$APP_SERVICE" $MIGRATE_CMD 2>&1 | tee -a "$DIR/deploy.log"
fi

STAGE="troca"
compose up -d --remove-orphans

STAGE="verificação"
healthy=no
for _ in $(seq 1 "$HEALTH_TRIES"); do
  if docker run --rm --network nginx_default "$CURL_IMAGE" \
       -fsS -m 3 -o /dev/null "http://$APP_SERVICE:$APP_PORT$HEALTH_PATH" 2>/dev/null; then
    healthy=yes
    break
  fi
  sleep 2
done

if [[ "$healthy" != yes ]]; then
  trap - ERR
  log "health check falhou em http://$APP_SERVICE:$APP_PORT$HEALTH_PATH"
  compose logs --no-color --tail=50 "$APP_SERVICE" 2>&1 | tee -a "$DIR/deploy.log" || true
  if [[ -n "$PREV_TAG" ]]; then rollback; fi
  [[ -n "$DB_FILE" ]] && log "migrations NÃO são revertidas automaticamente. Backup: $DIR/$DB_FILE"
  exit 1
fi

trap - ERR

if [[ -n "$POST_DEPLOY_CMD" ]]; then
  # shellcheck disable=SC2086
  compose exec -T "$APP_SERVICE" $POST_DEPLOY_CMD 2>&1 | tee -a "$DIR/deploy.log" \
    || log "AVISO: POST_DEPLOY_CMD falhou (deploy mantido)"
fi

# Limpa só as imagens antigas DESTE projeto (nunca prune global).
IMAGE="$(docker inspect --format '{{.Config.Image}}' "$APP_SERVICE")"
docker images "${IMAGE%:*}" --format '{{.Repository}}:{{.Tag}}' \
  | grep -v -e ":$TAG\$" -e ":${PREV_TAG:-__none__}\$" \
  | tail -n +"$((KEEP_IMAGES - 1))" \
  | xargs -r docker rmi >/dev/null 2>&1 || true

log "deploy $PROJECT ok: $TAG"
