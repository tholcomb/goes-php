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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tholcomb\Symple\Core\Symple;
use Tholcomb\Symple\Logger\LoggerProvider;

abstract class AbstractProvider implements ServiceProviderInterface
{
	public function register(Container $c)
	{
		$c->register(new LoggerProvider(), ['logger.path' => self::projectPath($_SERVER['GOES_LOG'])]);

		$c['image.dir'] = self::projectPath($_SERVER['GOES_IMAGES']);
		$c['image.quality'] = (int)$_SERVER['GOES_JPEG_QUALITY'];
		$c['imageCreator'] = function ($c) {
			return new ImageCreator($c['image.dir'], $c['image.quality']);
		};

		$c['userService'] = function ($c) {
			return new UserService($c['db']);
		};

		$c['db.path'] = self::projectPath($_SERVER['GOES_DB_PATH']);
		$c['db.proxy_dir'] = self::projectPath($_SERVER['GOES_CACHE']);
		$c['db'] = function ($c) {
			$conn = [
				'driver' => 'pdo_sqlite',
				'path' => $c['db.path'],
			];
			$config = Setup::createAnnotationMetadataConfiguration(
				paths: [__DIR__ . '/Model'],
				isDevMode: Symple::isDebug(),
				proxyDir: $c['db.proxy_dir'],
				useSimpleAnnotationReader: false,
			);

			return EntityManager::create($conn, $config);
		};
	}

	protected static function projectPath(string $path): string
	{
		if (str_starts_with($path, '/')) {
			return $path;
		} else {
			return PROJECT_ROOT . '/' . $path;
		}
	}
}