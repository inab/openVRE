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

## Configuration

First thing, is to create and configure your own  `.env` file:

``` bash
cd openVRE
cp .env.sample .env
```

Edit the new `.env` file and adapt it to your own environment.

Then, do the same for the `globals.inc.php` file. Default values should work for a first setup:

``` bash
cp front_end/openVRE/config/globals.inc.php.sample front_end/openVRE/config/globals.inc.php
```

For advanced system administration, such as SGE fine-tuning, Keycloak integration, or Vault setup, see [Admin-Specific Configuration](https://github.com/inab/openVRE/wiki/Developing-and-Administering-OpenVRE)

## Start the services

Validate the `docker-compose.yml` file before creating and starting them with the following command: 

``` bash
docker-compose --profile "local_auth" up -d 
```

and check the status of the resulting containers:

```
docker ps
```
