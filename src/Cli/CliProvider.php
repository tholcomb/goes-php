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

use Pimple\Container;
use Tholcomb\Goes\AbstractProvider;
use Tholcomb\Symple\Console\ConsoleProvider;
use Tholcomb\Symple\Logger\LoggerProvider;

class CliProvider extends AbstractProvider
{
	public function register(Container $c)
	{
		parent::register($c);

		$c->register(new ConsoleProvider('goes-php'));

		$c['maxTries'] = (int)$_SERVER['GOES_MAX_DOWNLOAD_ATTEMPTS'];
		$c['daysToKeep'] = (int)$_SERVER['GOES_DAYS_TO_KEEP'];
		ConsoleProvider::addCommand($c, CronCommand::class, function ($c) {
			return new CronCommand($c['imageCreator'], $c['db'], $c['maxTries'], $c['daysToKeep']);
		});
		ConsoleProvider::addCommand($c, UserCommand::class, function ($c) {
			return new UserCommand($c['userService']);
		});
		ConsoleProvider::addCommand($c, InstallCommand::class, function ($c) {
			return new InstallCommand($c['db.path'], $c['db.proxy_dir'], $c['image.dir']);
		});
	}
}