<?php

$configPath = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';

$command = $argv[1];

if($command === 'enable') {
    copy(__DIR__.'/xdebug.ini', $configPath);
}
elseif ($command === 'disable') {
    @unlink($configPath);
}
elseif ($command === 'status') {
    $enabled = in_array('xdebug', get_loaded_extensions(),true);
    [$status,$color] = $enabled ? ['enabled',"\e[32m"] : ['disabled',"\e[31m"];
}
else {
    echo "Unknown command: $command\n";
    exit(1);
}