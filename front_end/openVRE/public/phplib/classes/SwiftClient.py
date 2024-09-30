import swiftclient



class SwiftClientExample:
    def __init__(self, auth_url, username, password, project_name, user_domain_name, project_domain_name):
        self.auth_url = auth_url
        self.username = username
        self.password = password
        self.project_name = project_name
        self.user_domain_name = user_domain_name
        self.project_domain_name = project_domain_name
        self.conn = None

    def authenticate(self):
        self.conn = swiftclient.Connection(
            authurl=self.auth_url,
            user=self.username,
            key=self.password
        )

    def list_containers(self):
        if self.conn is None:
            return "Not authenticated."

        containers = self.conn.get_account()[1]
        return [container["name"] for container in containers]

    def download_object(self, container_name, object_name):
        if self.conn is None:
            return "Not authenticated."

        try:
            response, content = self.conn.get_object(container_name, object_name)
            with open(object_name, "wb") as f:
                f.write(content)
            return f"Object '{object_name}' downloaded and saved successfully."
        except swiftclient.exceptions.ClientException as e:
            return f"Failed to download object '{object_name}': {e}"

    def close_connection(self):
        if self.conn is not None:
            self.conn.close()



if __name__ == "__main__":

    auth_url="https://ncloud.bsc.es:5000/v3"
    username="bsc23829"
    password="Maio23!"
    project_name="bsc22Disc4All"
    user_domain_name="bsc-compute"
    project_domain_name="bsc22Disc4All"
    swift_client = SwiftClientExample(auth_url, username, password, project_name, user_domain_name, project_domain_name)
    swift_client.authenticate()

    swift_client.list_containers()

    swift_client.close_connection()
