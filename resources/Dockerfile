FROM python:3.10-alpine

WORKDIR /app

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY srv.py .

VOLUME /app

ENTRYPOINT [ "python", "./srv.py" ]

CMD [ "python", "./srv.py" ]

# docker build -t wideqserver .
# docker run --rm -v C:/Users/LENOVO/Documents/GitHub/docker-jeedom/jeedom/plugins/lgthinq/resources:/app \
# -p 5025:5025 --name wideqserver --network docker-jeedom_default wideqserver --port 5025 --verbose
# docker run --rm -p 5025:5025 --name wideqserver --network docker-jeedom_default wideqserver --port 5025 --verbose
