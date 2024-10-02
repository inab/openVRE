## Installation guide

### Pre requisites

 Docker Engine - Community
 Version:           26.1.0

 Docker Compose version v2.26.1


### Installation

For the installation, the following commands are to be run cli:

```
git clone https://gitlab.bsc.es/disc4all/openvre/dockerized_vre_vault_multi_tools.git

```

First thing, is to change the .env file, since at the moment the sample is to be filled out, respectively changing the hostname depending on the installation environment.
Example:

For local development: ```$FQDN_HOST=localhost```
If you have a domain: ```$FQDN_HOST=myapp.example.com```
If using a WSL or internal IP for access: ```$FQDN_HOST=192.168.x.x (IP address)```


Make sure also to filled up/check every configuration file that is in the front_end/openVRE/config directory.


```
globals.inc.php.sample --> globals.inc.php

mail.conf.sample --> mail.conf

mongo.conf.sample --> mongo.conf 

oauth2.conf.sample --> oauth2.conf

oauth2_admin.conf.sample --> oauth2_admin.conf
```


## Build containers


```
docker compose build

docker compose up
```


## Apply manual configuration:

### Vault Configuration


##Vault manual unseal

First time Vault up, execute the init and save elsewhere the 'Unseal keys' just generated:

```
docker exec -ti vault-server vault operator init 

```
On every Vault restart, use the following command to unseal the vault using 3 out of the 5 Unseal Keys generated during the init. 

```
docker exec -ti vault-server vault operator unseal SECRET_KEY1
docker exec -ti vault-server vault operator unseal SECRET_KEY2
docker exec -ti vault-server vault operator unseal SECRET_KEY3
```
##Vault manual setup

Considering an external JWT Authorization Token service as a middle identification layer to access the Vault and its secrets, it has to be properly registered.
Here are the command to follow to instatiate a JWT Authorization service for Keycloak: 

```
vault auth enable jwt

#Policy 
cd vault/config
vault policy write jwt-role-demo jwt-role-demo.hcl
vault policy write oidc-role-myrole oidc-role-myrole-policy.hcl

#Role
vault write auth/oidc/role/myrole allowed_redirect_uris="[http://$HOSTNAME/ui/vault/auth/oidc/oidc/callback, http://localhost:8250/oidc/callback]" user_claim="sub"
vault write auth/jwt/role/demo bound_audiences="account" allowed_redirect_uris="http://localhost:8250/oidc/callback" user_claim="sub" policies=jwt-role-demo role_type=jwt ttl=1h
vault write auth/jwt/role/demo role_type="jwt"
#vault write auth/jwt/role/demo bound_audiences="account"


#Configuration
#The public key can be retrieved directly from the Keycloak Realm (from the JWKS endpoint)
vault write auth/jwt/config default_role=demo bound_issuer="https://$KEYCLOAK_REALM" jwt_validation_pubkeys=@public-key.pem bound_audiences="account"

#Secrets
vault secrets enable -path=secret/mysecret kv-v2


```
### sgecore username:
Change minimal UID in SGE master configuration to allow job submission from web apps:

```
docker exec -it sgecore /bin/bash
qconf -as front_end.dockerized_vre

qconf -mconf # change UID from 1000 to 33)
```

### sgecore docker usage permission

```
groupmod -g 120 docker
usermod -aG docker application


chown root:docker /var/run/docker.sock
chmod 660 /var/run/docker.sock

```

### KeyCloak:
Check match user and secret with keycloak config.
Keycloak to front-end should be allowes via iptables in some systems
```
sudo iptables -I INPUT -s {keycloak internal IP} -p tcp --dport 8080 -j ACCEPT
```



## Troubleshotting

When connecting to an external Keycloak service, make sure that both systems have the right date and time. 

```
#Updating timezone
sudo timedatectl set-timezone Europe/Madrid
```
