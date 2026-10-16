FROM python:3.11-slim
RUN pip install --no-cache-dir selenium==4.25.0 requests==2.32.3
WORKDIR /app
COPY admin_bot.py .
CMD ["python", "-u", "admin_bot.py"]
