
#!/bin/bash

while true; do
    log_output="===== SGE Scheduler Log - $(date) ====="
    qstat_output=$(qstat -j '*' 2>/dev/null)

    if [ $? -eq 0 ]; then
        log_output="$log_output
$(echo "$qstat_output" | grep -e 'job_number' -e 'scheduling info')"
    else
        log_output="$log_output
No jobs running"
    fi

    printf "%s\n" "$log_output"
    sleep 5
done
