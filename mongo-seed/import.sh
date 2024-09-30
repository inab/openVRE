#!/bin/bash

apt-get update
apt-get install -y git rsync

# Import JSON files into MongoDB using environment variables for credentials
for f in /install_data/database/*.json; do
    mongoimport --host ${MONGO_SERVER} \
                --db ${MONGO_DB} \
                --port ${MONGO_PORT} \
                --username ${MONGO_INITDB_ROOT_USERNAME} \
                --password ${MONGO_INITDB_ROOT_PASSWORD} \
                --authenticationDatabase admin \
                --file $f
done

# Copy userdata
rsync -av --delete /install_data/data/userdata /shared_data



