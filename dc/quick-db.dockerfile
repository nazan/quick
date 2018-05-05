FROM mongo:3.4.14-jessie

ARG UID
ARG GID

RUN if grep -q "^appuser" /etc/group; then echo "Group already exists."; else groupadd -g $GID appuser; fi
RUN useradd -m -r -u $UID -g appuser appuser

RUN mkdir -p /usr/local/my-setup