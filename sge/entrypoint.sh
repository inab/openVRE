#!/bin/bash
set -e

SOCKET=/var/run/docker.sock
if [ -S "$SOCKET" ]; then
    echo "Adding application user to docker group"
    SOCKET_GID=$(stat -c '%g' "$SOCKET")
    EXISTING_GROUP=$(getent group "$SOCKET_GID" | cut -d: -f1 || true)

    if [ -n "$EXISTING_GROUP" ]; then
        usermod -aG "$EXISTING_GROUP" application
    else
        groupadd -g "$SOCKET_GID" dockerhost
        usermod -aG dockerhost application
    fi
fi

mkdir -p /shared_data
mkdir -p /shared_data/public
mkdir -p /shared_data/userdata
chown -R "${SHARED_DATA_UID}":"${SHARED_DATA_GID}" /shared_data

/usr/local/bin/setup_gridengine.sh &&
/usr/local/bin/modify_config.sh &&
/usr/local/bin/modify_sched_config.sh &&
/usr/local/bin/setup_submitter.sh /home/application/submit_hosts.list &&
(/usr/local/bin/run_sched_logger.sh &) &&
tail -f /var/spool/gridengine/qmaster/messages /var/spool/gridengine/execd/sgecore/messages