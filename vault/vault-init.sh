#!/bin/sh

set -e

apk add jq

until vault status > /dev/null 2>&1; do
  sleep 1
done

echo "Vault is ready. Applying configuration..."

# Create a policy
vault login "$VAULT_DEV_ROOT_TOKEN_ID"

vault auth enable jwt

cd vault/config

vault write auth/jwt/config oidc_discovery_url="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM" bound_issuer="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM"

vault write auth/jwt/role/user-role role_type="jwt" bound_audiences="account" user_claim="sub" policies="user-policy" ttl="1h"

JWT_ACCESSOR=$(vault auth list -format=json | jq -r '."jwt/".accessor')
sed "s/JWT_ACCESSOR/$JWT_ACCESSOR/g" jwt-user-policies-template.hcl > jwt-user-policies.hcl
vault policy write user-policy jwt-user-policies.hcl
vault write secret/config max_versions=1

# No need to run 'vault secrets enable -path=secret kv-v2' in dev mode, it's auto-enabled

echo "Vault initialization complete."
