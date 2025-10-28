#!/bin/sh

# This command should be run before  starting the vault container for the first time
docker compose run --rm --entrypoint /bin/sh vault-server -c "chown -R $VAULT_USER:$VAULT_USER /vault/data"

# Manual steps to be run after initializing vault container
docker exec -it vault-server /bin/sh

set -e
apk add jq

vault operator init
vault operator unseal
vault login
vault auth enable jwt

cd vault/config

vault write auth/jwt/config oidc_discovery_url="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM" bound_issuer="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM"
vault write auth/jwt/role/user-role role_type="jwt" bound_audiences="account" user_claim="sub" policies="user-policy" ttl="1h"

JWT_ACCESSOR=$(vault auth list -format=json | jq -r '."jwt/".accessor')
sed "s/JWT_ACCESSOR/$JWT_ACCESSOR/g" jwt-user-policies-template.hcl > jwt-user-policies.hcl

vault policy write user-policy jwt-user-policies.hcl
vault secrets enable -path=secret kv-v2
vault write secret/config max_versions=1

# to revoke root token: vault token revoke "<token>"
