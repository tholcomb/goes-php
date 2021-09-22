<?php
/*
 * This file is part of the goes-php project
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Goes\Web;

use Pimple\Container;
use Tholcomb\Goes\AbstractProvider;
use Tholcomb\Symple\Http\HttpProvider;
use Tholcomb\Symple\Logger\LoggerProvider;

class WebProvider extends AbstractProvider
{
	public function register(Container $c)
	{
		parent::register($c);

		$c->register(new HttpProvider());
		HttpProvider::addController($c, GoesController::class, function ($c) {
			return new GoesController($c['userService'], LoggerProvider::getLogger($c, 'ctrl'));
		});
	}
}