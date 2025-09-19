

***⚠️ This documentation is under construction***

# Open Virtual Research Environment core development (openVRE-core-dev)

OpenVRE (Open Virtual Research Environment) is an open-source, cloud-based platform designed to facilitate the creation, 
management and customization of Virtual Research Environments (VREs). OpenVRE bridges the gap between HPC resources, 
sensitive data infrastructures and analytical tools and workflows, providing a flexible environment that enables secure 
data access, scalable computation and collaboration.

This repository contains the source code for the OpenVRE core development version, which includes the core components of
the platform. Other software pieces as the tools, visualizers and data are specific to the each openVRE deployment.

A production ready version of openVRE can be found in the [openVRE](https://github.com/inab/openVRE) repository. It also
includes a complete documentation of the platform at the respository [wiki](https://github.com/inab/openVRE/wiki).


## Installation

### Quickstart

For a straightforward installation with default configuration, follow the steps below:

```
git clone https://github.com/inab/openVRE-core-dev.git

cd openVRE-core-dev

cp .env.sample .env
cp front_end/openVRE/config/globals.inc.php.sample front_end/openVRE/config/globals.inc.php

docker compose --profile "local_auth" up -d 

```

### Custom Configuration

To customize the installation, you can use the provided configuration files and modify them according to your specific needs.
The following configuration files are available:

- `.env`: Environment variables configuration file.
- `front_end/openVRE/config/globals.inc.php`: Configuration file for the front-end and back-end services (both included 
in the front_end service).
