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
class Raw
{
	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="bigint")
	 */
	protected ?string $id;

	/** @ORM\Column(type="datetime") */
	protected \DateTimeInterface $date;

	/** @ORM\Column(type="string", nullable=true) */
	protected ?string $path = null;

	/** @ORM\Column(type="integer") */
	protected int $tries = 0;

	/** @ORM\Column(type="datetime", nullable=true) */
	protected ?\DateTimeInterface $lastAttempt;

	public function __construct(\DateTimeInterface $date)
	{
		$this->date = $date;
	}

	public function getId(): ?string
	{
		return $this->id;
	}

	public function getDate(): \DateTimeInterface
	{
		return $this->date;
	}

	public function setPath(string $path): void
	{
		$this->path = $path;
	}

	public function getPath(): ?string
	{
		return $this->path;
	}

	public function getTries(): int
	{
		return $this->tries;
	}

	public function getLastAttempt(): ?\DateTimeInterface
	{
		return $this->lastAttempt;
	}

	public function incrementTries(): void
	{
		$this->tries++;
		$this->lastAttempt = new \DateTime();
	}
}