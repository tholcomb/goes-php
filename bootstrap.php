<?php
/*
 * This file is part of the goes-php project
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Goes;

use Tholcomb\Symple\Core\Symple;

require_once __DIR__ . '/vendor/autoload.php';

umask(0002);
const PROJECT_ROOT = __DIR__;

Symple::registerEnv(PROJECT_ROOT . '/.env');
Symple::boot();