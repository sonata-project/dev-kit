set :deploy_to, '/var/www/sullivansenechal.com/sonata-dev-kit/production'

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
