path "auth/oidc/role/myrole" {
  capabilities = ["create", "read", "update", "delete"]
}

path "secret/mysecret" {
  capabilities = ["create", "read", "update", "delete"]
}
