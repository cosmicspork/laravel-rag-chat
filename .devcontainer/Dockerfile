FROM php:8.3

# Install packages
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
  && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    git \
    gnupg \
    libpq-dev \
    libmcrypt-dev \
    libxml2-dev \
    libzip-dev \
    lsb-release \
    openssl \
    software-properties-common \
    unzip \
    wget \
    zip \
    zsh \
  && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pcntl xml zip

# Install PECL extensions
RUN pecl install mcrypt \
  && docker-php-ext-enable mcrypt

# Install Postgres
RUN docker-php-ext-install pdo pdo_pgsql \
  && docker-php-ext-enable pdo pdo_pgsql

# Install Redis
# RUN pecl install --onlyreqdeps --force redis \
#   && rm -rf /tmp/pear \
#   && docker-php-ext-enable redis

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install node
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
  && apt-get install -y nodejs \
  && npm install -g npm

# Install Azure CLI
RUN curl -sL https://aka.ms/InstallAzureCLIDeb | bash

# Set up non-root user
ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN groupadd --gid $USER_GID $USERNAME \
  && useradd --uid $USER_UID --gid $USER_GID -m $USERNAME \
  && apt-get update \
  && apt-get install -y sudo \
  && echo $USERNAME ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/$USERNAME \
  && chmod 0440 /etc/sudoers.d/$USERNAME
RUN chsh -s /usr/bin/zsh vscode

# Install user tools
USER $USERNAME
RUN composer global require laravel/installer friendsofphp/php-cs-fixer
ENV PATH="$PATH:/home/$USERNAME/.config/composer/vendor/bin"
RUN az bicep install \
  && az bicep upgrade
ENV PATH="$PATH:/home/$USERNAME/.azure/bin"

# Make it comfy
RUN sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" "" --unattended
RUN curl -sS https://starship.rs/install.sh | sh -s -- --yes
RUN echo 'eval "$(starship init zsh)"' >> /home/$USERNAME/.zshrc

WORKDIR /workspace
