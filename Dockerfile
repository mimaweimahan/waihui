# 使用 PHP 7.4 基础镜像
FROM php:7.4-fpm

# 安装必要扩展
RUN apt-get update && apt-get install -y \
    libzip-dev zip libssl-dev zlib1g-dev \
    && docker-php-ext-install mysqli \
    && pecl install redis \
    && docker-php-ext-enable redis

# 设置工作目录为 /var/task
WORKDIR /var/task

# 复制所有代码到容器内的 /var/task
COPY . /var/task

# 将工作目录切换到 public
WORKDIR /var/task/public

# 启用 PHP 内置服务器 (测试用)
CMD ["php", "-S", "0.0.0.0:8080"]

