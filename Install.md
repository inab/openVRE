## Installation guide

### Pre-requisites

 Docker Engine - Community
 Version:           26.1.0

 Docker Compose version v2.26.1


### Clone repository

For the installation, the following commands are to be run cli:

```
git clone -b hpc_access https://github.com/inab/dockerized_vre.git
```

### Setup configuration files

First thing, is to create and configure your own  `.env` file:
```
cd dockerized_vre
cp .env.sample .env
```

Edit the new `.env` file and adapt it to your own environment. At the moment, the default values would work in most of the systems, just make sure to setup the hostname depending on the installation environment. Examples:
- FQDN_HOST:
    - For local development: `$FQDN_HOST=localhost`
    - If you have a domain: `$FQDN_HOST=myapp.example.com`
    - If using a WSL or internal IP for access: `$FQDN_HOST=192.168.x.x`
- UID: Identifier of the host user running the containers (`id`)
- GID: Identifier of the host group running the containers (non-privileged users should belong to `docker`group)
- DOCKER_GROUP: Identifier of the `Docker` group. 

The *frontend* component uses its own set of configuration files. Make sure to create and update the default values according to your needs:

```
cd front_end/openVRE/config

cp globals.inc.php.sample globals.inc.php
cp mail.conf.sample mail.conf
cp mongo.conf.sample mongo.conf 
cp oauth2.conf.sample oauth2.conf
cp oauth2_admin.conf.sample oauth2_admin.conf

```


## Build containers

Return to the `dockerized_vre` folder and check the `docker-compose.yml` file before building the containers. Two docker images are going to be build according to it:  `sgecore` and `front_end`. The task could take a while...
```
cd dockerized_vre/
docker compose build
```
Check the new images:
```
docker images
```

## Start the services

Validate the `docker-compose.yml` file before creating the containers 
Create and start the containers 
```
docker compose up -d
```
Check containers
docker ps -a
```

## Apply manual configuration:

### sgecore username:

Before initialiting the configuration for the SGE to recognized jobs sent from the front_end, if the user is not sure of the hostname for the front_end docker, please use the command to retrieve it and use it. 
```
docker inspect front_end | grep -i Hostname 
```

Change minimal UID in SGE master configuration to allow job submission from web apps:

```
docker exec -it sgecore /bin/bash
qconf -as ${FRONT_END_HOSTNAME}

qconf -mconf # change UID from 1000 to 33)
```

### sgecore docker usage permission

```
groupmod -g 120 docker
usermod -aG docker application


chown root:docker /var/run/docker.sock
chmod 660 /var/run/docker.sock

```

## Keycloak Configuration

Check match user and secret with keycloak config.
Keycloak to front-end should be allowes via iptables in some systems, so run the command locally on the machine:

```
sudo iptables -I INPUT -s {keycloak internal IP} -p tcp --dport 8080 -j ACCEPT
```

The Keycloak configuration is following the oauth2.conf, where the User and the Secret should be stored.
If the secret is unknown or uncertain, access the Admin console to access and retrieve the Secret and store it in the oauth2.conf file, so the Keycloak server would be accessible through the VRE.

#### How to do it: 

1. Once the docker is up, access through the web-page at the link: *http(s)://{$FQDN_HOST}/auth/admin* ;

2. Access the Admin console with the Admin credentials stored in the oauth2_admin.conf;

3. Open the *Clients* section;

4. Open the *open-vre* Client ID section;

5. Go over the *Credentials* section, where you would find the Client Id and the Secret;

6. On your own VRE configuration file, *openVRE/config/oauth2.conf*, update the file with the credentials aforementioned. 

The access to the Realm is complete, you should be able to access and register new user on your local Keycloak server. 
Before closing the session, check the Vault configuration, since it needs a KeyCloak Client to be set up.



## Vault Configuration


### Keycloak Configuration for HashiCorp Vault Integration

This guide explains how to configure a Keycloak client to enable interaction between HashiCorp Vault and Keycloak for authentication and authorization using JWT tokens.

#### Step 1: Configure Your Keycloak Client

1. **Log in to the Keycloak admin console** and navigate to your realm.

2. **Locate your existing client**, in this case is the *open-vre* one. 

3. In the client settings, configure the following:

   - **Root URL**:
     ```
     https://$FQDN_HOST/
     ```
     Replace `$FQDN_HOST` with your fully qualified domain name (e.g., `vre.disc4all.eu`).

   - **Valid Redirect URIs**:
     ```
     https://$FQDN_HOST/*
     ```
     Additionally:
     ```
     http://$FQDN_HOST/ui/vault/auth/oidc/oidc/callback
     ```

     > Ensure `$FQDN_HOST` is replaced with the correct host name for your deployment (e.g., `vre.disc4all.eu`).

4. **Save the changes** to the client configuration to ensure the URIs are authorized by Keycloak.

#### Step 2: Create a New Client for Vault

To enable Vault to authenticate and authorize users via Keycloak, create a new dedicated Keycloak client for Vault.

1. **Go to the Clients section** in the Keycloak admin console.

2. **Click on the "Create" button** (on the right side of the clients table) to create a new client.

3. **Set the Client ID** to: *open-vre-vault*, with the same root Url as *open-vre* client. 

4. **Configure the following for the new client**:

- **Root URL**:
  ```
  https://$FQDN_HOST/
  ```

- **Valid Redirect URIs**:
  ```
  https://$FQDN_HOST/*
  ```
  Additionally:
  ```
  http://$FQDN_HOST/ui/vault/auth/oidc/oidc/callback
  ```

> Replace `$FQDN_HOST` with your domain (e.g., `vre.disc4all.eu`).

5. **Save the new client configuration**.


With the above configuration, Vault will be able to interact with Keycloak for OpenID Connect (OIDC) authentication, once it is configured manually on the Vault.
Before interacting with the Vault Server container, for the next configuration step, is necessary to retrieve the *JWKS validating public key*, directly from the Keycloak Realm.
Accessing the Admin Keycloak Interface through these steps :

1. Access the Vault-Server info using this command: 
```
curl http://$FDQN_HOST/auth/realms/open-vre/protocol/openid-connect/certs
```
;

2. Copy the results so to copy the *n* and the *e* values from the response array;

3. Redirect in the *vault/* dir;

4. Substitute the vaules you had saved in the *pem.py* script;

5. Launch the *pem.py* script: 

```
python3 pem.py >> public-key.pem
mv public-key.pem config/
```

6. Make sure that the key was saved in the *vault/config/* dir.


### Vault manual unseal

First time Vault up, access the containers in *interactive* mode, to execute the init and save elsewhere the 'Unseal keys' just generated:

```
docker exec -ti vault-server vault operator init 

```
On every Vault restart, use the following command to unseal the vault using 3 out of the 5 Unseal Keys generated during the init. 

```
docker exec -ti vault-server vault operator unseal SECRET_KEY1
docker exec -ti vault-server vault operator unseal SECRET_KEY2
docker exec -ti vault-server vault operator unseal SECRET_KEY3
```
### Vault manual setup

Considering an external JWT Authorization Token service as a middle identification layer to access the Vault and its secrets, it has to be properly registered.
Here are the command to follow to instatiate a JWT Authorization service for Keycloak: 

```
docker exec -ti vault-server /bin/sh
vault login # with ${Intial Root Token}

vault auth enable jwt
vault auth enable oidc

#Policy 
cd vault/config
vault policy write jwt-role-demo jwt-role-demo.hcl
vault policy write oidc-role-myrole oidc-role-myrole-policy.hcl

#Role
vault write auth/oidc/role/myrole allowed_redirect_uris="[http://$HOSTNAME/ui/vault/auth/oidc/oidc/callback, http://localhost:8250/oidc/callback]" user_claim="sub" #Hostname can coincide with $FQDN_HOST
vault write auth/jwt/role/demo bound_audiences="account" allowed_redirect_uris="http://localhost:8250/oidc/callback" user_claim="sub" policies=jwt-role-demo role_type=jwt ttl=1h
vault write auth/jwt/role/demo role_type="jwt"
#vault write auth/jwt/role/demo bound_audiences="account"

#Configuration
#The public key can be retrieved directly from the Keycloak Realm (from the JWKS endpoint)
vault write auth/jwt/config default_role=demo bound_issuer="https://$KEYCLOAK_REALM" jwt_validation_pubkeys=@public-key.pem bound_audiences="account"

#Secrets
vault secrets enable -path=secret/mysecret kv-v2


```

## Troubleshotting

When connecting to an external Keycloak service, make sure that both systems have the right date and time. 

```
#Updating timezone
sudo timedatectl set-timezone Europe/Madrid
```
