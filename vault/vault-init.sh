# Manual steps

# Check vault user id and group id
docker compose run --rm vault-server /bin/sh -c "id -u vault && id -g vault"

VAULT_USER=100 # Change if different
VAULT_GROUP=1000 # Change if different

# Change data folder ownership inside the container (by default it's owned by root)
docker compose run --rm --entrypoint /bin/sh vault-server -c "chown -R $VAULT_USER:$VAULT_GROUP /vault/data"

# Start the container
docker compose up vault-server -d

# Enter the container and setup
docker exec -it vault-server /bin/sh

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
