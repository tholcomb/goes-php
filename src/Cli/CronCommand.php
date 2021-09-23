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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tholcomb\Goes\ImageCreator;
use Tholcomb\Goes\Model\Image;
use Tholcomb\Goes\Model\Raw;
use Tholcomb\Goes\Model\User;
use Tholcomb\Symple\Console\Commands\AbstractCommand;

class CronCommand extends AbstractCommand
{
	protected const NAME = 'goes:cron';

	public function __construct(
		private ImageCreator $creator,
		private EntityManagerInterface $em,
		private int $maxTries,
		private int $daysToKeep,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->cleanupImages();
		$this->createCurrentImage();

		// Try downloading new images
		$pending = $this->getPendingDownloads();
		foreach ($pending as $p) {
			$p->incrementTries();
			try {
				$image = $this->creator->downloadImage($p->getDate());
			} catch (\Exception $e) {
				// Failed to download. Expected.
				$output->writeln($e->getMessage(), OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}
			// Downloaded successfully. Update path.
			$p->setPath($image->getPathname());
		}
		$this->em->flush();

		/** @var User[] $users */
		$users = $this->em->getRepository(User::class)->findAll();

		// Create images for users
		foreach ($pending as $p) {
			if ($p->getPath() === null) {
				continue;
			}
			$deDupArr = [];
			$image = new \SplFileInfo($p->getPath());
			foreach ($users as $u) {
				// Prevent duplication of efforts
				$key = $u->getScreenX() . $u->getScreenY() . (int)$u->includeTimeData();
				if (in_array($key, $deDupArr)) {
					continue;
				}

				$x = $u->getScreenX();
				$y = $u->getScreenY();
				$type = $u->includeTimeData() ? Image::TYPE_PROCESSED : Image::TYPE_PROCESSED_TRUNCATED;
				$processed = $this->creator->generateImage($image, $x, $y, !$u->includeTimeData());
				$entity = new Image($p, $processed->getPathname(), $type, $x, $y);
				$this->em->persist($entity);
				$deDupArr[] = $key;
			}
		}
		$this->em->flush();

		return 0;
	}

	private function cleanupImages(): void
	{
		// Get current time and subtract to get cutoff time
		$date = new \DateTime('now', new \DateTimeZone('UTC'));
		$date->sub(new \DateInterval(sprintf('P%dD', $this->daysToKeep)));

		$dql = <<<dql
SELECT r FROM Tholcomb\Goes\Model\Raw r
WHERE r.date <= :date
dql;
		/** @var Raw[] $res */
		$res = $this->em->createQuery($dql)
			->setParameter('date', $date)
			->getResult();

		// Create query to find children
		$imgDql = <<<dql
SELECT i FROM Tholcomb\Goes\Model\Image i
WHERE i.base = :raw
dql;
		$imgQuery = $this->em->createQuery($imgDql);

		// Collect paths from source and children and remove records from DB
		$filesToRm = [];
		foreach ($res as $r) {
			/** @var Image[] $images */
			$images = $imgQuery->setParameter('raw', $r)->getResult();
			foreach ($images as $img) {
				$filesToRm[] = $img->getPath();
				$this->em->remove($img);
			}
			$filesToRm[] = $r->getPath();
			$this->em->remove($r);
		}
		$this->em->flush();

		// Delete files after DB is updated successfully
		foreach ($filesToRm as $file) {
			if (empty($file)) {
				continue;
			}
			unlink($file);
		}
	}

	private function createCurrentImage(): void
	{
		// Get current time and round down to nearest 10. Imagery is only released on the 10s.
		$date = new \DateTime('now', new \DateTimeZone('UTC'));
		$date->setTime($date->format('H'), floor($date->format('i') / 10) . '0');

		// Check if already exists
		$dql = <<<dql
SELECT r FROM Tholcomb\Goes\Model\Raw r
WHERE r.date = :date
dql;
		$exists = $this->em->createQuery($dql)
			->setMaxResults(1)
			->setParameter('date', $date)
			->getOneOrNullResult();
		if ($exists !== null) {
			return;
		}

		// Create new entry
		$raw = new Raw($date);
		$this->em->persist($raw);
		$this->em->flush();
	}

	/** @return Raw[] */
	private function getPendingDownloads(): iterable
	{
		$dql = <<<dql
SELECT r FROM Tholcomb\Goes\Model\Raw r
WHERE r.tries <= :maxTries AND r.path IS NULL
dql;

		return $this->em->createQuery($dql)
			->setParameter('maxTries', $this->maxTries)
			->getResult();
	}
}