<?php

$config = include 'templates/project/.php-cs-fixer.dist.php';

$finder->in(__DIR__);

return $config->setFinder($finder);
