#!/bin/bash
set -e

echo ">>>>>>> Starting MongoDB initialization script"

mongo <<EOF
db = db.getSiblingDB('$MONGO_DB');

// Check if the root user exists
if (db.getUser("$MONGO_INITDB_ROOT_USERNAME") === null) {
  db.createUser({
    user: "$MONGO_INITDB_ROOT_USERNAME",
    pwd: "$MONGO_INITDB_ROOT_PASSWORD",
    roles: [{
      role: "dbOwner",
      db: "$MONGO_DB"
    }]
  });
  print("Root user created.");
} else {
  print("Root user already exists.");
}

// Check if the application user exists
if (db.getUser("$MONGO_INITDB_USERNAME") === null) {
  db.createUser({
    user: "$MONGO_INITDB_USERNAME",
    pwd: "$MONGO_INITDB_PASSWORD",
    roles: [{
      role: "readWrite",
      db: "$MONGO_DB"
    }]
  });
  print("Application user created.");
} else {
  print("Application user already exists.");
}

EOF

for mongo_document in /init_documents/*.json; do
    mongoimport --db ${MONGO_DB} \
                --jsonArray \
                --port ${MONGO_PORT} \
                --username ${MONGO_INITDB_ROOT_USERNAME} \
                --password ${MONGO_INITDB_ROOT_PASSWORD} \
                --authenticationDatabase admin \
                --file $mongo_document
done
