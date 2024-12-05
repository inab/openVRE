#!/bin/bash

# reading from a file the list of hostnames (one per line)
# adding them as submit hosts

while read -r S_HOST
do
    echo "Adding new submit host: " $S_HOST
    qconf -as $S_HOST
done < $1
