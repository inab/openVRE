api_addr     = "https://vault-server:8200"
cluster_addr = "https://vault-server:8201"
cluster_name            = "vault-cluster"
disable_mlock           = true
ui                      = true

listener "tcp" {
address       = "0.0.0.0:8200"
tls_cert_file = "/etc/ssl/certs/vault.crt"
tls_key_file  = "/etc/ssl/certs/vault.key"
}

storage "raft" {
path    = "/vault/data"
node_id = "vault-server-node"
}
