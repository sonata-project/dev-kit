set :deploy_to, '/var/www/dev-kit'

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
