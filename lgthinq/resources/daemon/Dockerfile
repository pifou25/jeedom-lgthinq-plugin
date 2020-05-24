FROM arm32v7/python:3

# docker run -it --rm --name my-running-script
#   -v "$PWD":/usr/src/myapp -w /usr/src/myapp
#   arm32v7/python:3 python your-daemon-or-script.py

WORKDIR /usr/src/app

# copy only requirements and install python dependencies
COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# copy everything there
COPY . .

# flask default port
EXPOSE 5000

# launch the user script
CMD [ "python", "./wideqServer.py" ]
