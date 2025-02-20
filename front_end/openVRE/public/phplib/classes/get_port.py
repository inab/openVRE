# Import necessary modules
import socket
import errno
import sys

# Set of reserved ports to keep track of used ports

# Function to get an open port within a specified range
def check_port(host, port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(1)
        result = sock.connect_ex((host,port))
        return result == 0

def get_port(host, start_port, end_port):

    for port in range(start_port, end_port +1):
        try: 
            if not check_port(host, port):
                #print(f"Port {port} is free and available.")
                return port
        except socket.error as error:
            if error.errno != errno.EADDRINUSE:
                raise
    raise Exception('Cannot find port in the speficied range.')


if __name__ == "__main__":
    HOST_IP = sys.argv[1]
    START_PORT = int(sys.argv[2])
    END_PORT = int(sys.argv[3])

    try: 
        available_port = get_port(HOST_IP, START_PORT, END_PORT)
        print (available_port)
    except Exception as e:
        print (f"Error: {e}")
