#!/bin/bash
set -e

echo ">>>>>>> Starting MongoDB initialization script"

mongo <<EOF
db = db.getSiblingDB('openVRE');

// Check if the root user exists
if (db.getUser("$MONGO_INITDB_ROOT_USERNAME") === null) {
  db.createUser({
    user: "$MONGO_INITDB_ROOT_USERNAME",
    pwd: "$MONGO_INITDB_ROOT_PASSWORD",
    roles: [{
      role: "dbOwner",
      db: "openVRE"
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
      db: "openVRE"
    }]
  });
  print("Application user created.");
} else {
  print("Application user already exists.");
}

EOF
