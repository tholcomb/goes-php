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

use Doctrine\ORM\EntityManagerInterface;
use Tholcomb\Goes\Model\Image;
use Tholcomb\Goes\Model\User;

class UserService
{
	public function __construct(
		private EntityManagerInterface $em,
	) {
	}

	/** @throws UserException */
	public function getUser(string $apiKey): User
	{
		$user = $this->em->getRepository(User::class)->find($apiKey);
		if (!$user instanceof User) {
			throw new UserException('Could not find user', $apiKey);
		}

		return $user;
	}

	/** @returns User[] */
	public function getAllUsers(): iterable
	{
		return $this->em->getRepository(User::class)->findAll();
	}

	public function createUser(string $apiKey, int $x, int $y, bool $showTimeData = false): User
	{
		if (!$this->isKeyUnique($apiKey)) {
			throw new \InvalidArgumentException(sprintf('ApiKey already exists: "%s"', $apiKey));
		}
		$user = new User($apiKey, $x, $y);
		if ($showTimeData) {
			$user->setShowTimeData(true);
		}
		$this->em->persist($user);
		$this->em->flush();

		return $user;
	}

	public function isKeyUnique(string $apiKey): bool
	{
		try {
			$user = $this->getUser($apiKey);
		} catch (UserException $e) {
			return true;
		}

		return false;
	}

	/** @throws UserException */
	public function removeUser(string $apiKey): void
	{
		$user = $this->getUser($apiKey);
		$this->em->remove($user);
		$this->em->flush();
	}

	public function getNewImageForUser(User $user, bool $save = false): ?Image
	{
		$dql = <<<dql
SELECT i FROM Tholcomb\Goes\Model\Image i
	JOIN i.base b
WHERE i.type = :type AND i.x = :x AND i.y = :y
ORDER BY b.date DESC
dql;
		try {
			$image = $this->em->createQuery($dql)->setMaxResults(1)->setParameters([
				'type' => $user->includeTimeData() ? Image::TYPE_PROCESSED : Image::TYPE_PROCESSED_TRUNCATED,
				'x' => $user->getScreenX(),
				'y' => $user->getScreenY(),
			])->getSingleResult();
		} catch (\Exception $e) {
			return null;
		}
		if ($image === $user->getLastImage()) {
			return null;
		} elseif ($save !== true) {
			return $image;
		}

		$user->setLastImage($image);
		$this->em->flush();

		return $image;
	}
}