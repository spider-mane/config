FROM ubuntu:22.04 as app

LABEL maintainer="Chris Williams"

EXPOSE 9000

COPY --from=spidermane/trunk * /trunk

ENV DEBIAN_FRONTEND=noninteractive
ENV APP=/app

VOLUME [ "$APP" ]
WORKDIR "$APP"

ARG TZ="UTC"
ENV TZ="$TZ"
RUN /trunk/setups/timezone

ARG LANG="en_US.UTF-8"
ENV LANG="$LANG"
RUN /trunk/setups/language

ENV TRUNK_USER=config
RUN /trunk/setups/user

ARG PHP_VERSION="8.0"
# ENV COMPOSER_HOME=/usr/lib/composer
RUN /trunk/provisions/php --version "$PHP_VERSION" --dev true

RUN /trunk/setups/entrypoint
ENTRYPOINT [ "docker-entrypoint" ]

RUN /trunk/setups/healthcheck
HEALTHCHECK CMD [ "docker-healthcheck" ] --interval=1s --timeout=1s

RUN /trunk/actions/cleanup

### Build with interactive features ###
FROM app as app-interactive

COPY --from=spidermane/trunk * /trunk

ARG UNMINIMIZE=false
RUN /trunk/actions/unminimize "$UNMINIMIZE"

ARG LOCAL_REPOSITORY=/studio
RUN /trunk/provisions/studio --path "$LOCAL_REPOSITORY"

ARG GIT_USER
ARG GIT_EMAIL
ARG GIT_DEFAULT_BRANCH=master
ARG GIT_INSTALL_GCM=false
RUN /trunk/provisions/git --user "$GIT_USER" --email "$GIT_EMAIL" --default-branch "$GIT_DEFAULT_BRANCH" --install-gcm "$GIT_INSTALL_GCM"

ENV ZSH=/usr/lib/omz
RUN /trunk/bundles/shell

RUN /trunk/provisions/github

HEALTHCHECK CMD [ "docker-healthcheck" ] --interval=3s --timeout=3s --start-period=10s

RUN /trunk/actions/cleanup
