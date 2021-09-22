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
class Image
{
	public const TYPE_PROCESSED = 0;
	public const TYPE_PROCESSED_TRUNCATED = 1;

	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="bigint")
	 */
	protected ?string $id;

	/** @ORM\ManyToOne(targetEntity="Raw") */
	protected Raw $base;

	/** @ORM\Column(type="smallint") */
	protected int $type;

	/** @ORM\Column(type="integer") */
	protected int $x;

	/** @ORM\Column(type="integer") */
	protected int $y;

	/** @ORM\Column(type="string") */
	protected string $path;

	public function __construct(Raw $base, string $path, int $type, int $x, int $y)
	{
		$this->base = $base;
		$this->type = $type;
		$this->x = $x;
		$this->y = $y;
		$this->path = $path;
	}

	public function getId(): ?string
	{
		return $this->id;
	}

	public function getBase(): Raw
	{
		return $this->base;
	}

	public function getType(): int
	{
		return $this->type;
	}

	public function getX(): int
	{
		return $this->x;
	}

	public function getY(): int
	{
		return $this->y;
	}

	public function getPath(): string
	{
		return $this->path;
	}
}