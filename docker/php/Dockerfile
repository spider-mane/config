FROM ubuntu:22.04 as app

LABEL maintainer="Chris Williams"

ENV DEBIAN_FRONTEND=noninteractive

ENV APP=/var/www/html
WORKDIR $APP

ARG TZ=UTC
ENV TZ="${TZ}"
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install basic requirements
RUN apt-get update \
  && apt-get install -y gnupg gosu curl wget ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils

# Install and setup PHP
ARG PHP_VERSION=8.0
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' \
  | gpg --dearmor \
  | tee /usr/share/keyrings/ppa_ondrej_php.gpg > /dev/null \
  && echo "deb [signed-by=/usr/share/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" \
  > /etc/apt/sources.list.d/ppa_ondrej_php.list \
  && curl -o /usr/local/bin/php-get https://gist.githubusercontent.com/spider-mane/db9cb0c880df0f6618f738961038d2f5/raw \
  && chmod +x /usr/local/bin/php-get \
  && apt-get update \
  && php-get install "$PHP_VERSION" cli dev pgsql sqlite3 gd curl imap mysql mbstring xml zip bcmath soap intl readline ldap msgpack \
  igbinary redis swoole memcached pcov xdebug imagick \
  && setcap "cap_net_bind_service=+ep" "$(which php$PHP_VERSION)" \
  && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Cleanup
RUN apt-get -y autoremove \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY docker/php/etc/ etc/php/$PHP_VERSION/cli/conf.d/

COPY docker/php/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT [ "docker-entrypoint.sh" ]

### Build with interactive features ###
FROM app as app-interactive

# Maybe unminimize
ARG UNMINIMIZE
RUN if [ $UNMINIMIZE = true ]; then yes | unminimize; fi

# Install basic requirements
ARG LANG=en_US.UTF-8
ENV LANG="${LANG}"
RUN apt-get update \
  && apt-get install -y locales pass ripgrep \
  && locale-gen "$LANG"

# Install Zsh
RUN apt-get install -y zsh \
  && bash -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" \
  && ZSH_CUSTOM=$HOME/.oh-my-zsh/custom \
  && git clone --depth=1 https://github.com/romkatv/powerlevel10k.git $ZSH_CUSTOM/themes/powerlevel10k \
  && git clone --depth=1 https://github.com/zdharma-continuum/fast-syntax-highlighting.git $ZSH_CUSTOM/plugins/fast-syntax-highlighting

# Install Bat
RUN BAT_VERSION=0.22.1 \
  && curl --output-dir /tmp -LO https://github.com/sharkdp/bat/releases/download/v$BAT_VERSION/bat_${BAT_VERSION}_amd64.deb \
  && dpkg -i /tmp/bat_${BAT_VERSION}_amd64.deb \
  && git clone --depth=1 https://github.com/eth-p/bat-extras.git /opt/bat-extras \
  && /opt/bat-extras/build.sh --install --minify=none

# Update and enhance Git
RUN curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xe1dd270288b4e6030699e45fa1715d88e1df1f24' \
  | gpg --dearmor \
  | tee /usr/share/keyrings/git-core.gpg > /dev/null \
  && echo "deb [signed-by=/usr/share/keyrings/git-core.gpg] https://ppa.launchpadcontent.net/git-core/ppa/ubuntu jammy main" \
  > /etc/apt/sources.list.d/git-core.list \
  && apt-get update \
  && apt-get install -y git git-extras \
  && GCM_VERSION=2.0.886 \
  && curl --output-dir /tmp -LO https://github.com/GitCredentialManager/git-credential-manager/releases/download/v$GCM_VERSION/gcm-linux_amd64.$GCM_VERSION.deb \
  && dpkg -i /tmp/gcm-linux_amd64.$GCM_VERSION.deb \
  && git-credential-manager configure \
  && DELTA_VERSION=0.15.1 \
  && curl --output-dir /tmp -LO https://github.com/dandavison/delta/releases/download/$DELTA_VERSION/git-delta_${DELTA_VERSION}_amd64.deb \
  && dpkg -i /tmp/git-delta_${DELTA_VERSION}_amd64.deb

# Install Github CLI
RUN curl -sSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
  | gpg --dearmor \
  | tee /usr/share/keyrings/github-cli.gpg > /dev/null \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/github-cli.gpg] https://cli.github.com/packages stable main" \
  > /etc/apt/sources.list.d/github-cli.list \
  && apt update \
  && apt install gh -y

# Install global Composer packages
RUN composer global config --no-plugins allow-plugins.franzl/studio true \
  && composer global require franzl/studio

# Configure Git
ARG GIT_DEFAULT_BRANCH=master
ARG GIT_USER
ARG GIT_EMAIL
RUN git config --global --add safe.directory "$APP" \
  && git config --global user.name "$GIT_USER" \
  && git config --global user.email "$GIT_EMAIL" \
  && git config --global credential.credentialStore gpg \
  && git config --global core.pager delta \
  && git config --global interactive.diffFilter "delta --color-only" \
  && git config --global merge.conflictstyle diff3 \
  && git config --global diff.colorMoved default \
  && git config --global git-extras.default-branch "$GIT_DEFAULT_BRANCH" \
  && git config --global delta.navigate true \
  && git config --global delta.features "side-by-side line-numbers decorations"

# Set up credential store
RUN gpg --quick-gen-key --no-tty --pinentry-mode loopback --passphrase '' "$GIT_USER" rsa4096 default never \
  && pass init "$GIT_USER"

# Shell config files
COPY docker/php/~/ /root/

# Cleanup
RUN apt-get -y autoremove \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
