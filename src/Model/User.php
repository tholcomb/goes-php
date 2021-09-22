<?php
/*
 * This file is part of the goes-php project
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Goes\Model;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class User
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string")
	 */
	protected string $apiKey;

	/** @ORM\Column(type="integer") */
	protected int $screenX;

	/** @ORM\Column(type="integer") */
	protected int $screenY;

	/** @ORM\Column(type="boolean") */
	protected bool $showTimeData = false;

	/**
	 * @ORM\ManyToOne(targetEntity="Image")
	 * @ORM\JoinColumn(nullable=true)
	 */
	protected ?Image $lastImage;

	public function __construct(string $apiKey, int $screenX, int $screenY)
	{
		$this->apiKey = $apiKey;
		$this->screenX = $screenX;
		$this->screenY = $screenY;
	}

	public function getApiKey(): string
	{
		return $this->apiKey;
	}

	public function getScreenX(): int
	{
		return $this->screenX;
	}

	public function getScreenY(): int
	{
		return $this->screenY;
	}

	public function setScreenSize(int $x, int $y): void
	{
		$this->screenX = $x;
		$this->screenY = $y;
	}

	public function setShowTimeData(bool $show): void
	{
		$this->showTimeData = $show;
	}

	public function includeTimeData(): bool
	{
		return $this->showTimeData;
	}

	public function getLastImage(): ?Image
	{
		return $this->lastImage;
	}

	public function setLastImage(Image $image): void
	{
		$this->lastImage = $image;
	}
}