FROM nexylan/php-dev:7.0

COPY entrypoint.sh /
RUN chmod u+x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

WORKDIR /code

CMD ["tail", "--follow", "--lines=0", "/var/log/dmesg"]
