FROM python:3.11-alpine

WORKDIR /app

COPY app.py /app/app.py

EXPOSE 8080

USER 65534:65534

CMD ["python", "/app/app.py"]
