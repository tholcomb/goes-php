<?php
/*
 * This file is part of the goes-php project
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Goes\Cli;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Pimple\Container;

require_once __DIR__ . '/bootstrap.php';

$c = new Container();
$c->register(new CliProvider());

return ConsoleRunner::createHelperSet($c['db']);