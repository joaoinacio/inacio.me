<?php
namespace Deployer;
require 'recipe/symfony3.php';
require 'vendor/deployer/recipes/npm.php';

// Configuration

set('repository', 'git@github.com:joaoinacio/inacio.me.git');

add('shared_dirs', ['vendor', 'node_modules', 'web/vendor']);
add('shared_files', ['app/config/parameters.yml']);
add('writable_dirs', ['var']);

// Servers

serverList('deploy.yml');

// Tasks

task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_paths',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assets:install',
    'deploy:assetic:dump',
    'deploy:cache:warmup',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

// install npm packages
after('deploy:vendors', 'npm:install');

// deploy assets with bower
task('node:bower:install', function () {
    run('cd {{release_path}} && node_modules/bower/bin/bower install');
})
->desc('Install dependencies with bower')
->addAfter('npm:install');

// restart php-fpm service
task('php-fpm:restart', function () {
    run('sudo service php7.0-fpm restart');
})
->desc('Restart PHP-FPM service')
->addAfter('deploy:symlink');

// forcefully clear cache of old (previous-1) release
task('oldrelease:cleanupcache', function () {
    $releases = get('releases_list');
    if (isset($releases[1])) {
        run("rm -rf {{deploy_path}}/releases/{$releases[1]}/var/cache/*");
    }
})->desc("cleanup cache for old release");
before('cleanup', 'oldrelease:cleanupcache');
