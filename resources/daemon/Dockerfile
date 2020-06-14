# derniere version de python (min 3.6 pour la lib wideq)
FROM python:3

# commande pour build + run:
# docker build -t wideq_image .
# docker run -it --rm --name wideqServerContainer wideq_image

WORKDIR /usr/src/app

# copy only requirements and install python dependencies
COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# copy everything there:
COPY wideqServer.py .

# default port for jeedom
EXPOSE 5025

# launch the user script
CMD [ "python", "./wideqServer.py" ]
