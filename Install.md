## OpenVRE Development Setup Guide

## Pre-requisites

- **Docker Engine - Community** (Version: 26.1.0)
- **Docker Compose** (Version: v2.26.1)

You will need to have Docker Compose installed on your system.
You can find instructions [here](https://docs.docker.com/compose/install/).

## Cloning the Repository

Clone the OpenVRE core development repository using the following command:

```bash
 git clone https://github.com/inab/openVRE.git 
```

Navigate into the cloned directory:

```bash
cd openVRE
```

## Setup configuration files 

First thing, is to create and configure your own  `.env` file:
```
cd openVRE
cp .env.sample .env
```

Edit the new `.env` file and adapt it to your own environment. At the moment, the default values would work in most of
the systems, just make sure to setup the hostname depending on the installation environment. Examples:
- `FQDN_HOST`:
    - For local development: `$FQDN_HOST=localhost`
- `UID`: Identifier of the host user running the containers (`id`)
- `GID`: Identifier of the host group running the containers (non-privileged users should belong to `docker`group)
- `DOCKER_GROUP`: Identifier of the `Docker` group. 
- `KEYCLOAK_SECRET`: The secret for the Keycloak client.

### Custom Configuration

To customize the installation, you can use the provided configuration files and modify them according to your specific needs.
The following configuration files are available:

- `.env`: Environment variables configuration file.
- `front_end/openVRE/config/globals.inc.php`: Configuration file for the front-end and back-end services (both included 
in the front_end service).
- `docker-compose.yml`: Docker Compose configuration file.


## Import container images

#### Option 1: pull images
You can user already build images from [GitHub Container Registry](https://github.com/mapoferri?tab=packages). 

#### Option 2: build images 
Return to the `openVRE` folder and check the `docker-compose.yml` file before building the containers. 

```
cd openVRE/
docker compose build
```
Check the new images:
```
docker images
```

## Start the services

Validate the `docker-compose.yml` file before creating and starting them with the following command: 
```
docker-compose --profile "local_auth" up -d 
```
and check the status of the resulting containers
```
docker ps -a
```
> For advanced system administration, such as SGE fine-tuning, Keycloak integration, or Vault setup, see [Admin-Specific Configuration](https://github.com/inab/openVRE/wiki/Admin-Guide)

