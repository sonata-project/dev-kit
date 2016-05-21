lock '~> 3.5'

set :application, 'sonata-dev-kit'
set :repo_url, 'git@github.com:sonata-project/dev-kit.git'

set :linked_files, fetch(:linked_files, []).push('.env')

# Default value for linked_dirs is []
# set :linked_dirs, fetch(:linked_dirs, []).push('log', 'tmp/pids', 'tmp/cache', 'tmp/sockets', 'public/system')

set :composer_install_flags, '--no-interaction --quiet --optimize-autoloader'

server ENV['DEPLOY_SERVER'], user: ENV['DEPLOY_USER'], roles: %w{web app db}
