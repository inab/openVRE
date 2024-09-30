path "auth/jwt/role/demo" {
  capabilities = ["create", "read", "update", "delete"]
}

path "secret/*" {
  capabilities = ["create", "read", "update", "delete"]
}

path "auth/token/lookup-self" {
  capabilities = ["read"]
}
path "auth/token/renew-self" {
  capabilities = ["update"]
}
path "auth/token/revoke-self" {
  capabilities = ["update"]
}  
