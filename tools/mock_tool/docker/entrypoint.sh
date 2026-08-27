#!/bin/bash
set -e

addgroup --gid 1000 application
adduser --uid 1000 --gid 1000 --gecos "" --disabled-password application

### Insert here privileged tasks.
#bindFS
mkdir -p /shared_data/public
mkdir -p /shared_data/userdata
mkdir -p /shared_scripts/


bindfs --force-user=application --force-group=application --create-for-user=1000 --create-for-group=1000 --chown-ignore --chgrp-ignore /shared_data/userdata_tmp /shared_data/userdata
bindfs --force-user=application --force-group=application --create-for-user=1000 --create-for-group=1000 --chown-ignore --chgrp-ignore /shared_data/public_tmp /shared_data/public
#bindfs --force-user=application --force-group=application --create-for-user=1000 --create-for-group=1000 --chown-ignore --chgrp-ignore /shared_scripts_tmp /shared_scripts



if [[ $# -gt 0 ]]; then
    exec sudo -H -u application -- "$@"
else
    exec sudo -H -u application -- bash
fi