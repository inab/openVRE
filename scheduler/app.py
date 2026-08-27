import json
import os
import ssl
import urllib.error
import urllib.parse
import urllib.request
from http.server import BaseHTTPRequestHandler, HTTPServer


TOKEN_PATH = "/var/run/secrets/kubernetes.io/serviceaccount/token"
CA_PATH = "/var/run/secrets/kubernetes.io/serviceaccount/ca.crt"
API_HOST = os.environ.get("KUBERNETES_SERVICE_HOST", "kubernetes.default.svc")
API_PORT = os.environ.get("KUBERNETES_SERVICE_PORT", "443")
DEFAULT_NAMESPACE = os.environ.get("DEFAULT_NAMESPACE", "default")
SCHEDULER_AUTH_TOKEN = os.environ.get("SCHEDULER_AUTH_TOKEN", "")

with open(TOKEN_PATH, "r", encoding="utf-8") as f:
    SA_TOKEN = f.read().strip()

SSL_CTX = ssl.create_default_context(cafile=CA_PATH)


def k8s_request(method, path, body=None, content_type="application/json"):
    url = f"https://{API_HOST}:{API_PORT}{path}"
    data = body.encode("utf-8") if body is not None else None
    req = urllib.request.Request(url, method=method, data=data)
    req.add_header("Authorization", f"Bearer {SA_TOKEN}")
    if body is not None:
        req.add_header("Content-Type", content_type)
    try:
        with urllib.request.urlopen(req, context=SSL_CTX, timeout=30) as resp:
            raw = resp.read().decode("utf-8")
            return resp.getcode(), raw
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8") if e.fp else str(e)
        return e.code, raw


class Handler(BaseHTTPRequestHandler):
    def _authorized(self):
        if SCHEDULER_AUTH_TOKEN == "":
            return False
        auth = self.headers.get("Authorization", "")
        return auth == f"Bearer {SCHEDULER_AUTH_TOKEN}"

    def _json(self, code, payload):
        out = json.dumps(payload).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(out)))
        self.end_headers()
        self.wfile.write(out)

    def _read_json(self):
        length = int(self.headers.get("Content-Length", "0"))
        if length == 0:
            return {}
        raw = self.rfile.read(length).decode("utf-8")
        return json.loads(raw)

    def do_GET(self):
        if self.path == "/healthz":
            return self._json(200, {"ok": True})
        if not self._authorized():
            return self._json(401, {"ok": False, "error": "unauthorized"})
        if self.path.startswith("/jobs/"):
            name_q = self.path[len("/jobs/"):]
            name, _, query = name_q.partition("?")
            params = urllib.parse.parse_qs(query)
            ns = params.get("namespace", [DEFAULT_NAMESPACE])[0]
            code, raw = k8s_request("GET", f"/apis/batch/v1/namespaces/{ns}/jobs/{name}")
            if code == 404:
                return self._json(200, {"ok": True, "exists": False, "job": ""})
            if code >= 300:
                return self._json(500, {"ok": False, "error": raw})
            return self._json(200, {"ok": True, "exists": True, "job": raw})
        return self._json(404, {"ok": False, "error": "not found"})

    def do_POST(self):
        if not self._authorized():
            return self._json(401, {"ok": False, "error": "unauthorized"})
        if self.path != "/jobs":
            return self._json(404, {"ok": False, "error": "not found"})
        try:
            body = self._read_json()
            ns = body.get("namespace") or DEFAULT_NAMESPACE
            manifest = body.get("manifest")
            if not manifest:
                return self._json(400, {"ok": False, "error": "manifest is required"})
            code, raw = k8s_request(
                "POST",
                f"/apis/batch/v1/namespaces/{ns}/jobs",
                body=manifest,
                content_type="application/yaml",
            )
            if code >= 300:
                return self._json(500, {"ok": False, "error": raw})
            return self._json(200, {"ok": True, "stdout": raw, "stderr": ""})
        except Exception as e:
            return self._json(500, {"ok": False, "error": str(e)})

    def do_DELETE(self):
        if not self._authorized():
            return self._json(401, {"ok": False, "error": "unauthorized"})
        if not self.path.startswith("/jobs/"):
            return self._json(404, {"ok": False, "error": "not found"})
        name_q = self.path[len("/jobs/"):]
        name, _, query = name_q.partition("?")
        params = urllib.parse.parse_qs(query)
        ns = params.get("namespace", [DEFAULT_NAMESPACE])[0]
        delete_opts = '{"apiVersion":"batch/v1","kind":"DeleteOptions","propagationPolicy":"Background"}'
        code, raw = k8s_request(
            "DELETE",
            f"/apis/batch/v1/namespaces/{ns}/jobs/{name}",
            body=delete_opts,
            content_type="application/json",
        )
        if code == 404:
            return self._json(
                200,
                {"ok": True, "stdout": f"job.batch/{name} already deleted", "stderr": ""},
            )
        if code >= 300:
            return self._json(500, {"ok": False, "error": raw})
        return self._json(200, {"ok": True, "stdout": raw, "stderr": ""})

    def log_message(self, fmt, *args):
        return


if __name__ == "__main__":
    server = HTTPServer(("0.0.0.0", 8080), Handler)
    server.serve_forever()
