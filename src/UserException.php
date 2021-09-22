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

class UserException extends \Exception
{
	protected ?string $apiKey;

	public function __construct($message = "", ?string $apiKey = null, int $code = 0)
	{
		$this->apiKey = $apiKey;
		parent::__construct($message, $code);
	}

	public function getApiKey(): ?string
	{
		return $this->apiKey;
	}
}