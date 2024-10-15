# Installation guide

## VRE Tool dockerization & adaptation guide


Since the Virtual Research environment is a dockerized system, also for tools integration a similar dockerization method is followed, so to encapsulate the tools and their dependencies in a container, allowing for easy sharing, version control, and deployment.

This guide walks through the process of Dockerizing a sequence extraction tool and integrating it into a Virtual Research Environment (VRE) framework.

### Prerequisites

Before proceeding, ensure the following are installed:

- **[Docker](https://docs.docker.com/get-docker/)**: Follow this link to install Docker.
- **[Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)**: Follow this link to install Git.


# Step 1: Creating a Dockerfile for your tool

In this example we are going to use a SeqIo tool, a sequence extraction tool using Biopython, designed to filter and extract sequences from a FASTA file based on specified IDs and sequence length.

The Dockerfile sets up the environment by installing dependencies like Biopython and placing the necessary Python script into the container.

**Create the Dockerfile** in your project directory, that defines the environment and the tool configuration. Using as an example the extraction tool mentioned as before: 

```
# Use a lightweight Python image
FROM python:3.9-slim

# Set the working directory inside the container
WORKDIR /home/

# Install Biopython for sequence handling
RUN pip install biopython

# Copy your Python script into the container
COPY seqio_tool/extract_sequences.py /home/seqio_tool/extract_sequences.py

# Make the Python script executable
RUN chmod +x /home/seqio_tool/extract_sequences.py

# Define the entry point for the container
ENTRYPOINT ["python", "/home/seqio_tool/extract_sequences.py"]
```

**Make sure for the ENTRYPOINT to refer directly to the script/software that you would want to launch on the platform.** The VRE framework will use this as the direct command for the wrapper. 
---

**Build the Docker image** once the Dockerfile is set up, with the command:
```
docker build -t my_tool_image .
```

In this tool case, the image would be available on **[Docker Hub](https://hub.docker.com/repository/docker/mapoferri/seqio-tool/general)**
---



 
# Step 2: Wrapping the Tool for openVRE

Within the OpenVRE environment, you will need to integrates the tool with OpenVRE Tool Dockerized framework.
To do that, the VRE will need three elements:
1. A **docker image** for your tool containing the application
2. A **docker image** specifics for the VRE framework, contain the VRE RUNNER wrapper
3. A list of descriptive **metadata** fields annotating the tool (*i.e.* input files rquirments, arguments, description)

The following guide will help you achive that:

## Download the VRE Dockerized tool directory

Clone the **[vre_template_tool_dockerized]**(https://github.com/inab/vre_template_tool_dockerized/) in your system

```
git clone https://github.com/inab/vre_template_tool_dockerized/
cd vre_template_tool_dockerized/template/
```

This is gonna be our working directory from this point on. 

## Set the Dockerized tool 

In the *Dockerfile_template*, you would only needed to modify the FROM command:

```
INTEGRATE NEW TOOL CONTAINER
FROM #YOUR IMAGE NAME HERE 
```

Remember to change the name of the Dockerfile from *Dockerfile_template* to **Dockerfile** to be able to build the image. 

## Prepare the VRE RUNNER script for your application 

The VRE RUNNERs are the adapters which are gonna consumate the job execution files and send it to the VRE server, to submit a new job each the user sends it through the web interface. It will run locally the wrapper application or pipeline (*the ENTRYPOINT for the docker image of your tool*) and generate the outputs. 

Since the new modified **Dockerfile** is gonna create it for you automatically following the **[vre_template_tool]**() format, the only modification you would need to do is to update the **Vre_Tool_template.py**.

```
class myTool(Tool):
    DEFAULT_KEYS = ['execution', 'project', 'description']
    PYTHON_SCRIPT_PATH = "/home/../seqio_tool/extract_sequences.py"
```

The *$PYTHON_SCRIPT_PATH* will point directly to your script **as you saved it in your Dockeri image**. Make sure the path is consistent.

> **Path consistency**
>
> Before running the ultimate VRE Tool dockerized version of your tool, make sure that the path you used in your Dockerfile could be easily called from the **$WORK_DIR** in the *vre_tool_dockerized*
> This path would never change in the VRE Tool Docker, */home/vre_template_tool/*, so make sure to keep it in mind when changing the *$PYTHON_SCRIPT_PATH*.


You would also need to specify in this code the inputs, arguments. The default is one *input_file* and one *argument*. 
This is how the **runToolExecution** section of *VRE_Tool_Template.py* has been modify to adapt to the SeqIO tool dependencies:

```
try:
            # Get input files
            input_file_1 = input_files.get('fasta_file')
            if not os.path.isabs(input_file_1):
                input_file_1 = os.path.normpath(os.path.join(self.parent_dir, input_file_1))

            input_file_2 = input_files.get('ids_file')
            if not os.path.isabs(input_file_2):
                input_file_2 = os.path.normpath(os.path.join(self.parent_dir, input_file_2))

            # TODO: add more input files to use, if it is necessary for you

            # Get arguments
            argument_1 = self.arguments.get('min_lenght')
            if argument_1 is None:
                errstr = "min_lenght must be defined."
                logger.fatal(errstr)
                raise Exception(errstr)

```

Finally, you would need to change the **cmd** command in the same code section,**following your requirments for your script**, who is gonna be called everytime the user would launch a job request.

In the template version:

```
cmd = [
                'bash', '/home/my_demo_pipeline.sh', output_file_path 
            ]
```

In the example SeqIO tool: 


```

cmd = [
                    'python3',
                    self.parent_dir + self.PYTHON_SCRIPT_PATH,  # extract_sequences.py
                    input_file_1,  # fasta file
                    input_file_2, #ids file
                    output_file_path,
                    argument_1 #min_lenght
            ]
```



Remember to change the name of the Dockerfile from *VRE_Tool_Template.py* to **VRE_Tool.py** to be able to build the image.






## Prepare the metadata files

In this step, we will create two JSON files that provide a basic description of the tool. These files will be used for local testing of the integration with the VRE_RUNNER.
You can find them in *template/vre_template_tool/tests/basic_docker* directory.


### Required JSON Files

1. **Run Configuration File (`config.json`)**
   - Contains a list of input files selected by the user for a specific run, including:
     - Values of the arguments
     - List of expected output files

2. **Input Files Metadata File (`in_metadata.json`)**
   - Contains metadata for each input file listed in `config.json`, including:
     - Absolute file path
     - Other relevant metadata information

> For testing the image: 
> If some input files for running the test are provided, make sure to save/move them in the *template/vre_template_tool/tests/basic_docker/volumes/public/* directory, since by default is the one the test_VRE_RUNNER.sh script has as an input.


### Purpose

These JSON files serve as standardized input files for the VRE_RUNNER installed in the Docker environment. In a production setting, these files will be dynamically generated by the VRE server during each execution initiated by the user via the web interface.


## Building the VRE Dockerized Tool Image

For testing purposes, the tool is momentainarly called *demo_tool*, but later on it could be called whatever name version is more fitting. 

In the *vre_template_tool_dockerized/template* dir, run this command:

```
docker build -t demo_tool .
```

## Testing the VRE Dockerized Tool Image

Once the VRE Tool dockerized version of your tool is complete, before integrating it into the VRE Environement, you can test it in the *template/vre_template_tool/tests/basic_docker* directory by running:

```
chmod +x test_VRE_RUNNER.sh
./test_VRE_RUNNER.sh
```

You would find output data in whatever directoy was specified in the *metadata* JSON files.

---

## Conclusion

By following these steps, you’ve successfully Dockerized your tool, integrated it with the OpenVRE environment, and configured the necessary Dockerfiles to run the tool in both local and OpenVRE environments.
```

This version is fully formatted as a **Markdown file** with working hyperlinks and a structured list for easy readability. You can now use this as an installation guide for your project.


```
