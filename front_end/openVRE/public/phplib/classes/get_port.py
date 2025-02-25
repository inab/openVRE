# Import necessary modules
import socket
import sys


def is_port_free(host, port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)  # Allow socket reuse
        try:
            sock.bind((host, port))
            return True 
        except OSError as e:
            if e.errno == socket.errno.EADDRINUSE:
                return False 
            else:
                raise


def find_free_port(host, start_port, end_port):
    for port in range(start_port, end_port + 1):
        if is_port_free(host, port):
            return port
    raise Exception("Cannot find port in the specified range.")


if __name__ == "__main__":
    HOST_IP = sys.argv[1]
    START_PORT = int(sys.argv[2])
    END_PORT = int(sys.argv[3])

    try: 
        available_port = get_port(HOST_IP, START_PORT, END_PORT)
        print (available_port)
    except Exception as e:
        print (f"Error: {e}")
