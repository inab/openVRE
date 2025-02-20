#!/bin/bash

TMPFILE=/tmp/temp_sched_config.$$
TMPFILETODEL=/tmp/temp_sched_config_todelete.$$
if [ $EDIT_CONFIG ]; then
     grep -v schedd_job_info $1 > $TMPFILETODEL
     grep -v params $TMPFILETODEL > $TMPFILE
     rm $TMPFILETODEL
     echo "schedd_job_info true" >> $TMPFILE
     echo "params MONITOR=1" >> $TMPFILE
     sleep 1
     mv $TMPFILE $1
     echo "Modified scheduler configuration"
else
     export EDITOR=$0
     export EDIT_CONFIG=1
     qconf -msconf
fi
