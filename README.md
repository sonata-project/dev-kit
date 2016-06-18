# Sonata project development kit

This repository contains all common documentation and tools for all Sonata projects.

This one **must** be the **only** reference for how to contribute on those projects.

## Docker setup

A Docker setup for dev env is available.

First, run:

```bash
./configure
```

Then, run:

```bash
docker-compose up
```

And your dev-kit web app is ready to use at [http://localhost:8000/](http://localhost:8000/).

Optionally, you can override the Docker Compose configuration with the `docker-compose.override.yml` file.
See `docker-composer.override.yml.dist` as an example.
