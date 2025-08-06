#!/bin/bash
set -e
echo uid is "${SHARED_DATA_UID}"
echo gid is "${SHARED_DATA_GID}"

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