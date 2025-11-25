
# Vault Setup Manual Steps

## Check Vault user and group IDs

```bash
docker compose run --rm vault-server /bin/sh -c "id -u vault && id -g vault"
```

Set the variables (adjust if needed):

```bash
VAULT_USER=100    # Change if different
VAULT_GROUP=1000  # Change if different
```

## Fix data folder ownership inside the container

By default, the folder is owned by `root`. Update it:

```bash
docker compose run --rm --entrypoint /bin/sh vault-server -c "chown -R $VAULT_USER:$VAULT_GROUP /vault/data"
```

## Start the Vault container

```bash
docker compose up vault-server -d
```

## Enter the container and install basic tools

```bash
docker exec -it vault-server /bin/sh
```

Inside the container:

```bash
apk add jq
```

## Initialize and unseal Vault

```bash
vault operator init
vault operator unseal
vault login
vault auth enable jwt
```

## Configure JWT Authentication

Navigate to config directory:

```bash
cd vault/config
```

Configure JWT and create a role:

```bash
vault write auth/jwt/config oidc_discovery_url="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM" \
  bound_issuer="$KEYCLOAK_SERVER/realms/$KEYCLOAK_REALM"

vault write auth/jwt/role/user-role \
  role_type="jwt" \
  bound_audiences="account" \
  user_claim="sub" \
  policies="user-policy" \
  ttl="1h"
```

Retrieve the JWT accessor:

```bash
JWT_ACCESSOR=$(vault auth list -format=json | jq -r '."jwt/".accessor')
```

Generate the user policy file:

```bash
sed "s/JWT_ACCESSOR/$JWT_ACCESSOR/g" jwt-user-policies-template.hcl > jwt-user-policies.hcl
```

Write the policy:

```bash
vault policy write user-policy jwt-user-policies.hcl
```

## Enable KV secrets engine

```bash
vault secrets enable -path=secret kv-v2
vault write secret/config max_versions=1
```

## Revoke root token (optional but recommended)

```bash
vault token revoke "<token>"
```
