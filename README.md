Open Virtual Research Environment (openVRE)
=========

### :information_source: Please, visit the [Wiki Pages](https://github.com/inab/openVRE/wiki) to learn more on openVRE

___
openVRE is a cloud-based working environment that allow to rapidly build your own computational platform. It offers:
- a user-friendly web-based interface that integrates a number of pluggable resources:
	- analysis tools or pipelines,
	- interfaces to external data repositories,
	- visualizaters
- an scalable backend for cloud computing compatible with OCCI middlewares like OpenNebula or OpenStack.


### Flavors

Several research projects have adopted openVRE as the framework for rapidly prototying their computational platforms:

| Project     | Title | URL | Repository |
| ----------- | ----- | ----| -----------|
| MuG         | Virtual Research Enviroment for Multiscale Complex Genomics  | https://vre.multiscalegenomics.eu | [Code](https://github.com/Multiscale-Genomics/VRE) |
| OpenEBench  | ELIXIR Benchmarking Platform | https://openebench.bsc.es/vre | [Code](https://github.com/inab/openEBench_vre) |
| euCanSHare  | Computational environment for cardiovascular research | https://vre.eucanshare.bsc.es/ | [Code](https://github.com/euCanSHare/vre/) |
| IPC         | Individualized Paediatric Cancer Analysis Platform | https://vre.ipc-project.bsc.es/ |  [Code](https://gitlab.bsc.es/inb/ipc/openvre) |
| euCanImage  | Cancer imaging analysis platform enhanced by Artificial Intelligence| https://vre.eucanimage.eu/ |[Code](https://gitlab.bsc.es/inb/eucanimage/vre) |
| Disc4All    | Computational simulations in translational medicine, applied to intervertebral disc degeneration.|https://vre.disc4all.eu/ |[Code](https://gitlab.bsc.es/inb/eucanimage/vre) |




### Components

This repository contains the code of the openVRE core, the central web server managing and distributing the executions to plugged-in elements. However, a fully functional infrastructure include other elements depicted here:

![arch](https://github.com/inab/openVRE/raw/master/public/assets/layouts/layout/img/wiki/architecture_overview.png)


### Code repository structure

openVRE cores is a server-side web application mainly written in PHP modules, as well as some Java Script based libraries.  Following, a short description of the content of this repository. 

- composer.json: 3rd party software requirements
- [config](./config) : configuration files 
	- [settings](./config/globals.inc.php.sample) sample for the global application settings
	- [bootstrap](./config/bootstrap.php) bootstrap VRE application
- [install](./install) : installation instructions and data 
	- [install](./install/README.md) instructions
	- [database](./install/database) skeleton with structural collections
	- [data](./install/data) datasets and sample schemas
- [public](./public) : web application elements under the web server root directory
	- **web pages**
	- [home](./public/home) pages for 'Homepage' section
	- [workspace](./public/workspace) pages for 'User Workspace' section
	- [getdata](./public/getdata) pages for 'Get Data' section
	- [launch](./public/launch) pages for 'Run Tool / Visualizers' section
	- [help](./public/help) pages for 'Help' section
	- [helpdesk](./public/helpdesk) pages for 'Helpdesk' section
	- [admin](./public/admin) pages for 'Admin' section
	- [user](./public/user) pages for 'User Profile' section
	- [cookies](./public/cookies) notification
	- **libraries**
	- [applib](./public/applib) : pages' backend
	- [assets](./public/assets) : pages' client scripts
	- [phplib](./public/phplib) : VRE libraries and classes
	- [htmlib](./public/htmlib) : html templates
	- **content**
	- [tools](./public/tools) : web form, assets and summart page for integrated tools
	- [visualizers](./public/visualizers) : visualizers code
- [scripts](./scripts) : maintainance scripts
