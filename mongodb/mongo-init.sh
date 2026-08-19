#!/bin/bash
set -e

echo ">>>>>>> Starting MongoDB initialization script"

mongosh <<EOF
db = db.getSiblingDB('$MONGO_MAIN_DB');

// Check if the application user exists
if (db.getUser("$MONGO_INITDB_USERNAME") === null) {
  db.createUser({
    user: "$MONGO_INITDB_USERNAME",
    pwd: "$MONGO_INITDB_PASSWORD",
    roles: [{
      role: "readWrite",
      db: "$MONGO_MAIN_DB"
    }]
  });
  print("Application user created.");
} else {
  print("Application user already exists.");
}

EOF

for mongo_document in /init_documents/*.json; do
    collection_name=$(basename "$mongo_document" .json)

    # Strip all whitespace and compare to "[]" to detect an empty array
    stripped_content=$(tr -d '[:space:]' < "$mongo_document")

    if [ "$stripped_content" == "[]" ]; then
        echo "Creating empty collection: ${collection_name}"
        mongosh "${MONGO_MAIN_DB}" \
            --username "${MONGO_INITDB_USERNAME}" \
            --password "${MONGO_INITDB_PASSWORD}" \
            --authenticationDatabase "${MONGO_MAIN_DB}" \
            --eval "db.createCollection('${collection_name}')"
    else
        echo "Importing into collection: ${collection_name}"
        mongoimport --db "${MONGO_MAIN_DB}" \
            --collection "${collection_name}" \
            --jsonArray \
            --username "${MONGO_INITDB_USERNAME}" \
            --password "${MONGO_INITDB_PASSWORD}" \
            --file "$mongo_document"
    fi
done