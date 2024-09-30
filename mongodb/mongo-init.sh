#!/bin/bash
set -e

mongo <<EOF
echo ">>>>>>> trying to create database and users"

db=db.getSiblingDB('openVRE');
use openVRE;

db.createUser({
  user:  '$MONGO_INITDB_ROOT_USERNAME',
  pwd: '$MONGO_INITDB_ROOT_PASSWORD',
  roles: [{
    role: 'dbOwner',
    db: 'openVRE'
  }]
});

use openVRE;
db.createUser({
  user:  '$MONGO_INITDB_USERNAME',
  pwd: '$MONGO_INITDB_PASSWORD',
  roles: [{
    role: 'readWrite',
    db: 'openVRE'
  }]
});

db.createCollection("VREcollection")

EOF
