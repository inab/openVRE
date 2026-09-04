#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# generate-certs.sh — generate a self-signed CA and a server certificate
# signed by it, with Subject Alternative Names (SANs).
#
# Configure via environment variables or just
# edit the values below.
#
# Example:
#   CA_CN="internal-vault-ca" \
#   SERVER_CN="vault-server" \
#   SERVER_NAME="vault" \
#   SANS="DNS:vault-server,DNS:vault-server.internal,DNS:localhost" \
#   OUT_DIR="config/tls" \
#   ./generate-certs.sh
# ---------------------------------------------------------------------------

# --- Configuration (override via env) ---------------------------------------
OUT_DIR="${OUT_DIR:-tls}"                       # output directory
SERVER_NAME="${SERVER_NAME:-server}"            # base filename for server key/cert
CA_NAME="${CA_NAME:-ca}"                        # base filename for CA key/cert
CA_CN="${CA_CN:-internal-ca}"                   # CA subject CN
SERVER_CN="${SERVER_CN:-my-server}"             # server subject CN
SANS="${SANS:-DNS:localhost,DNS:my-server}"     # comma-separated SAN list
CA_DAYS="${CA_DAYS:-3650}"                      # CA validity (days)
CERT_DAYS="${CERT_DAYS:-1825}"                  # server cert validity (days)
KEY_BITS="${KEY_BITS:-4096}"                    # RSA key size
COMBINE_PEM="${COMBINE_PEM:-true}"              # also write combined PEM files

# --- Derived paths ------------------------------------------------------------
CA_KEY="$OUT_DIR/${CA_NAME}.key"
CA_CRT="$OUT_DIR/${CA_NAME}.crt"
SRV_KEY="$OUT_DIR/${SERVER_NAME}.key"
SRV_CSR="$OUT_DIR/${SERVER_NAME}.csr"
SRV_CRT="$OUT_DIR/${SERVER_NAME}.crt"
SAN_CNF="$OUT_DIR/san.cnf"

mkdir -p "$OUT_DIR"

# 1) Generate the root CA (self-signed cert + private key)
openssl req -x509 -nodes -sha256 -newkey "rsa:${KEY_BITS}" -days "$CA_DAYS" \
  -keyout "$CA_KEY" -out "$CA_CRT" \
  -subj "/CN=${CA_CN}"

# 2) Generate the server private key and CSR
openssl req -new -nodes -newkey "rsa:${KEY_BITS}" \
  -keyout "$SRV_KEY" -out "$SRV_CSR" \
  -subj "/CN=${SERVER_CN}"

# 3) Write the SAN config used to sign the server cert
cat > "$SAN_CNF" <<EOF
subjectAltName = ${SANS}
EOF

# 4) Sign the server CSR with the CA, embedding the SANs
openssl x509 -req -sha256 -in "$SRV_CSR" \
  -CA "$CA_CRT" -CAkey "$CA_KEY" -CAcreateserial \
  -out "$SRV_CRT" -days "$CERT_DAYS" -extfile "$SAN_CNF"

# 5) Optionally combine into PEM files (useful for e.g. MongoDB)
if [[ "$COMBINE_PEM" == "true" ]]; then
  cat "$CA_KEY" "$CA_CRT" > "$OUT_DIR/${CA_NAME}-combined.pem"
  cat "$SRV_KEY" "$SRV_CRT" > "$OUT_DIR/${SERVER_NAME}-combined.pem"
fi

# 6) Secure the private keys
chmod 600 "$CA_KEY" "$SRV_KEY"

echo "Issued:"
echo "  CA cert:     $CA_CRT"
echo "  Server cert: $SRV_CRT (CN=${SERVER_CN}, SAN=${SANS})"
[[ "$COMBINE_PEM" == "true" ]] && echo "  Combined PEMs written alongside the above."