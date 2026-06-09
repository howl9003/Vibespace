# Archspace — reproducible build of the 2004-05 C++ game engine on a modern base.
# Validated step-by-step on ubuntu:24.04 / g++ 13.3.
#
# Single-image deployment: MariaDB + the game server (+ web tier) run together,
# matching the engine's pervasive localhost assumptions. The entrypoint wires
# everything up at first boot. For a split DB, see docker/README.md.
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive TZ=UTC

# ---- toolchain + runtime deps --------------------------------------------
# libpth-dev = GNU Portable Threads (the engine's coroutine/IO layer) — packaged,
# so no source build needed. libmariadb-dev = MySQL/MariaDB C client.
RUN apt-get update && apt-get install -y --no-install-recommends \
        g++ gcc make cmake pkg-config flex bison gettext \
        libpth-dev libmariadb-dev \
        mariadb-server mariadb-client \
        nginx php-fpm php-mysqli php-cli fcgiwrap \
        ca-certificates tzdata procps iproute2 \
    && rm -rf /var/lib/apt/lists/* \
    # Compat: the engine includes <mysql.h>; MariaDB headers live in /usr/include/mariadb
    && ln -sfn /usr/include/mariadb /usr/include/mysql

# ---- build the engine -----------------------------------------------------
WORKDIR /build
COPY archspace_source/archspace /build/archspace
COPY archspace_source/archspace.tar.gz /build/archspace.tar.gz
COPY docker/setup-runtime.sh /usr/local/bin/setup-runtime.sh
COPY docker/setup-web.sh     /usr/local/bin/setup-web.sh
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh
COPY docker/nginx.conf       /etc/nginx/sites-available/archspace
COPY docker/as-cgi           /build/as-cgi
COPY web /build/web
RUN chmod +x /usr/local/bin/setup-runtime.sh /usr/local/bin/setup-web.sh /usr/local/bin/entrypoint.sh

# Build the CGI adapter (replaces mod_as): bridges *.as HTTP requests to the
# game server's binary protocol on 12350.
RUN if [ -f /build/as-cgi/Makefile ]; then \
        make -C /build/as-cgi && cp /build/as-cgi/as-cgi /usr/local/bin/as-cgi; \
    fi

# set_platform symlinks the Linux Makefiles; build libs then the game server.
RUN cd /build/archspace/src \
    && sh set_platform linux \
    && (cd libs && make) \
    && (cd apps/archspace && make archspace) \
    && test -x apps/archspace/archspace \
    && cp apps/archspace/archspace /usr/sbin/archspace

# ---- MariaDB sql_mode: non-strict, matching the original 2004 MySQL --------
# (the engine relies on silent truncation of over-long generated names)
RUN printf '[mariadbd]\nsql_mode = NO_ENGINE_SUBSTITUTION\nbind-address = 127.0.0.1\n' \
        > /etc/mysql/mariadb.conf.d/99-archspace.cnf

ENV ARCHSPACE_SRC=/build/archspace \
    DB_HOST=127.0.0.1 DB_USER=root DB_PASS=comconq1 DB_NAME=Archspace

EXPOSE 80
VOLUME ["/var/lib/mysql", "/var/archspace/data", "/var/log/archspace"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
