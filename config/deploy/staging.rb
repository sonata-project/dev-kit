set :deploy_to, '/var/www/sullivansenechal.com/sonata-dev-kit/staging'

ask :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }.call
