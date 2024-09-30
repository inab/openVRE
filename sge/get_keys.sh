#!/bin/bash

# Define the key IDs you want to download
KEYS=("54404762BBB6E853" "0E98404D386FA1D9" "AA8E81B4331F7F50" "DCC9EFBF77E11517" "112695A0E562B32A" "04EE7237B7D453EC" "EF0F382A1A7B6500" "648ACFD622F3D138" "DCC9EFBF77E11517")

# Define the keyserver URLs to check
SERVERS=("hkp://keyserver.ubuntu.com:80" "hkp://pgp.mit.edu:80" "hkp://keys.gnupg.net:80" "hkp://pool.sks-keyservers.net:80" "hkp://ipv4.pool.sks-keyservers.net:80")

# Create a directory to store the keys
mkdir -p keys

# Loop through each key and server, download the key, and export it to a file
for key in "${KEYS[@]}"; do
    for server in "${SERVERS[@]}"; do
        echo "Fetching key $key from $server"
        gpg --batch --keyserver "$server" --recv-keys "$key"
        if [ $? -eq 0 ]; then
            gpg --export -a "$key" > "keys/$key.asc"
            echo "Key $key fetched and exported to keys/$key.asc"
            break
        else
            echo "Failed to fetch key $key from $server"
        fi
    done
done

