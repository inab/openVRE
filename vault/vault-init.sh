#!/bin/sh

set -e

until vault status > /dev/null 2>&1; do
  sleep 1
done

echo "Vault is ready. Applying configuration..."

# Create a policy
vault login "$VAULT_DEV_ROOT_TOKEN_ID"

vault auth enable jwt
vault auth enable oidc

cd vault/config
vault policy write jwt-role-demo jwt-role-demo.hcl
vault policy write oidc-role-myrole oidc-role-myrole-policy.hcl

vault write auth/oidc/role/myrole allowed_redirect_uris="[$VAULT_ADDR/ui/vault/auth/oidc/oidc/callback, http://localhost:8250/oidc/callback]" user_claim="sub"
vault write auth/jwt/role/demo bound_audiences="account" allowed_redirect_uris="http://localhost:8250/oidc/callback" user_claim="sub" policies=jwt-role-demo role_type=jwt ttl=1h
vault write auth/jwt/role/demo role_type="jwt"
vault write auth/jwt/config default_role=demo bound_issuer="$FQDN_HOST_PROTOCOL://$FQDN_HOST:$KEYCLOAK_PORT/auth/realms/$KEYCLOAK_REALM" jwt_validation_pubkeys=@public-key.pem bound_audiences="account"

echo "Vault initialization complete."
