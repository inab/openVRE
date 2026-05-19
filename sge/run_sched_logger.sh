
#!/bin/bash

while true; do
    log_output="===== SGE Scheduler Log - $(date) ====="
    qstat_output=$(qstat -j '*' 2>/dev/null)

    if [ $? -eq 0 ]; then
        log_output="$log_output
        $(echo "$qstat_output" | grep -e 'job_number' -e 'scheduling info')"
        printf "%s\n" "$log_output"
    fi

    sleep 5
done
