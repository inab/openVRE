#!/bin/bash

echo "VAULT_TOKEN: $VAULT_TOKEN"
echo "VAULT_ADDRESS: $VAULT_ADDRESS"

EGA_ENCRYPTED_FILES_MOUNTINGPOINT="/encrypted_files"
EGA_USERNAME=$(curl --header "X-Vault-Token: $VAULT_TOKEN" "$VAULT_ADDRESS" -s | jq -r '.data.data.EGA.username')

echo $EGA_USERNAME

USER_ID=$(id -u)
GROUP_ID=$(id -g)

echo $USER_ID
echo $GROUP_ID

user_private_key_file="/tmp/ega_secret_key"
USER_PRIVATE_KEY=$(curl --header "X-Vault-Token: $VAULT_TOKEN" "$VAULT_ADDRESS" -s | jq -r '.data.data.EGA.crypt4gh_priv')

echo "$USER_PRIVATE_KEY" | base64 --decode > $user_private_key_file
chmod 600  $user_private_key_file

echo -e "\nMounting remote EGA OUTBOX..."

EGA_OUTBOX_ENDPOINT="outbox.spain.ega-archive.org"

sshfs -P 2233 -o reconnect -o BatchMode=yes -o IdentityFile="$user_private_key_file" -o allow_other -o default_permissions -o uid=$USER_ID -o gid=$GROUP_ID -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null "$EGA_USERNAME"@"$EGA_OUTBOX_ENDPOINT":./ "$EGA_ENCRYPTED_FILES_MOUNTINGPOINT"

if [[ $(mountpoint $EGA_ENCRYPTED_FILES_MOUNTINGPOINT) ]]; then
        echo "Mounted"
else
        echo "$EGA_ENCRYPTED_FILES_MOUNTINGPOINT is not a mount point"
        exit 1
fi

echo "list $EGA_ENCRYPTED_FILES_MOUNTINGPOINT"
ls -al "$EGA_ENCRYPTED_FILES_MOUNTINGPOINT"

echo -e "\nSetting up crypt4ghfs..."

cryptfs_conf="/tmp/fs.conf"

cat >  $cryptfs_conf <<EOL
[DEFAULT]
rootdir=${EGA_ENCRYPTED_FILES_MOUNTINGPOINT}
log_level=DEBUG
include_crypt4gh_log=yes
[CRYPT4GH]
seckey=/tmp/ega_secret_key
[FUSE]
options=allow_other,default_permissions
EOL

chmod 600 $cryptfs_conf
cat "$cryptfs_conf"

EGA_CLEAN_FILES_MOUNTINGPOINT="/clean_files"

LOG="crypt4ghfs_log.txt"
touch "$LOG"

crypt4ghfs -f --conf /tmp/fs.conf "$EGA_CLEAN_FILES_MOUNTINGPOINT" 2>&1 | tee -a $LOG > /dev/null < /dev/null & disown
sleep 1

if [[ $(mountpoint $EGA_CLEAN_FILES_MOUNTINGPOINT) ]]; then
  echo "$EGA_CLEAN_FILES_MOUNTINGPOINT is a mount point"
else
  echo "$EGA_CLEAN_FILES_MOUNTINGPOINT is not a mount point"
  exit 2
fi

echo "list $EGA_CLEAN_FILES_MOUNTINGPOINT"
ls -la $EGA_CLEAN_FILES_MOUNTINGPOINT


