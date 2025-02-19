#!/bin/bash

TMPFILE=/tmp/temp_config.$$
TMPFILETODEL=/tmp/temp_config_todelete.$$
if [ $EDIT_CONFIG ]; then
     grep -v reporting_params $1 > $TMPFILETODEL
     grep -v flush_time $TMPFILETODEL > $TMPFILE
     echo "reporting_params accounting=true reporting=true flush_time=00:00:15 joblog=true sharelog=00:00:00" >> $TMPFILE
     sleep 1
     mv $TMPFILE $1
     echo "Modified global configuration"
else
     export EDITOR=$0
     export EDIT_CONFIG=1
     qconf -mconf
fi
