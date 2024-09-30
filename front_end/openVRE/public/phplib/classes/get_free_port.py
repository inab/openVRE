# Import necessary modules
import socket
import errno
import contextlib
import sys

# Set of reserved ports to keep track of used ports
reserved_ports = set()

# Function to get an open port within a specified range
def get_open_port(lowest_port=0, highest_port=None, bind_address='', *socket_args, **socket_kwargs):
    # If highest_port is not specified, set it to lowest_port + 100
    if highest_port is None:
        highest_port = lowest_port + 100
    # Loop through the port range
    while lowest_port < highest_port:
        # If the current port is not in the reserved ports set
        if lowest_port not in reserved_ports:
            try:
                # Attempt to bind a socket to the current port
                with contextlib.closing(socket.socket(*socket_args, **socket_kwargs)) as my_socket:
                    my_socket.bind((bind_address, lowest_port))
                    # Get the actual port number (in case the OS assigned a different one)
                    this_port = my_socket.getsockname()[1]
                    reserved_ports.add(this_port)  # Add the port to the reserved set
                    return this_port  # Return the found port number
            except socket.error as error:
                # If the error is not "Address already in use", raise the exception
                if not error.errno == errno.EADDRINUSE:
                    raise
                assert not lowest_port == 0
                reserved_ports.add(lowest_port)  # Add the port to the reserved set
        lowest_port += 1  # Move to the next port
    # If no open port is found in the specified range, raise an exception
    raise Exception('Could not find open port')

try:
    # Attempt to get an open port in the range 9001 to 9010
    port = get_open_port(lowest_port=9001, highest_port=9010)
    print(port)  # Print the found port number
except Exception as e:
    # If an error occurs, print the error message to stderr and exit with status code 1
    print("Error: {}".format(e), file=sys.stderr)
    sys.exit(1)
