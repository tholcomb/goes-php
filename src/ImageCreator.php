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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Process;

class ImageCreator
{
	private const IMAGE_URL = 'https://cdn.star.nesdis.noaa.gov/GOES16/ABI/FD/GEOCOLOR/%s_GOES16-ABI-FD-GEOCOLOR-1808x1808.jpg';
	private const PIXEL_FILE = PROJECT_ROOT . '/pixel.jpg';
	private const COMPOSITE_MODE = \Imagick::COMPOSITE_COPY;

	private const TIME_DATA_HEIGHT = 32;
	private const TIME_DATA_START = 1777;

	private const NOAA_LOGO_START = 1618;
	private const NOAA_LOGO_SIZE = [154, 160];

	private string $imageDirectory;
	private int $jpegQuality;

	public function __construct(string $imageDirectory, int $jpegQuality)
	{
		$this->imageDirectory = rtrim($imageDirectory, '/') . '/';

		if ($jpegQuality < 1 || $jpegQuality > 100) {
			throw new \InvalidArgumentException('JPEG quality must be between 1 and 100');
		}
		$this->jpegQuality = $jpegQuality;
	}

	/** @throws \RuntimeException */
	public function downloadImage(\DateTimeInterface $date): \SplFileInfo
	{
		if (!preg_match('/^\d0$/', $date->format('i'))) {
			throw new \InvalidArgumentException('Time must be on the 10s');
		}

		// Get date in NOAA's format.
		$dateString = $date->format('Y')
			. str_pad((int)$date->format('z') + 1, 3, '0', STR_PAD_LEFT) // NOAA's days are 1-indexed
			. $date->format('H')
			. $date->format('i');
		$url = sprintf(self::IMAGE_URL, $dateString);
		$basename = basename($url);
		$downloadPath = $this->imageDirectory . $basename;

		// Return image if it already exists.
		if (file_exists($downloadPath)) {
			return new \SplFileInfo($downloadPath);
		}

		// Attempt to download image.
		$client = new Client();
		try {
			$response = $client->get($url);
		} catch (GuzzleException $e) {
			throw new \RuntimeException('Could not download file', 0, $e);
		}
		if ($response->getStatusCode() !== 200) {
			throw new \RuntimeException(sprintf('Got bad status on download: %d', $response->getStatusCode()));
		}

		// Save image.
		file_put_contents($downloadPath, $response->getBody());

		return new \SplFileInfo($downloadPath);
	}

	/** @throws \ImagickException */
	public function generateImage(\SplFileInfo $file, int $x, int $y, bool $truncateTimeData = true): \SplFileInfo
	{
		// Create base image with black background.
		$base = new \Imagick(self::PIXEL_FILE);
		$base->scaleImage($x, $y);

		$goes = new \Imagick($file->getPathname());
		if ($truncateTimeData === true) {
			// Crop the time data.
			$goes->cropImage($goes->getImageWidth(), self::TIME_DATA_START, 0, 0);
		} else {
			// Invert the time data to be white text on black background.
			$timeData = clone $goes;
			$timeData->cropImage($goes->getImageWidth(), self::TIME_DATA_HEIGHT, 0, self::TIME_DATA_START);
			$timeData->negateImage(true);
			$goes->compositeImage($timeData, self::COMPOSITE_MODE, 0, self::TIME_DATA_START);
		}
		$this->fillNoaaLogo($goes);

		// Resize and position image.
		$compX = $compY = 0;
		if ($x > $y) {
			$goes->scaleImage(0, $y);
			$compX = ($base->getImageWidth() - $goes->getImageWidth()) / 2;
		} else {
			$goes->scaleImage($x, 0);
			$compY = ($base->getImageHeight() - $goes->getImageHeight()) / 2;
		}
		$base->compositeImage($goes, self::COMPOSITE_MODE, $compX, $compY);

		// Save image.
		$fileName = preg_replace(
			'/(.*)\.jpg$/',
			sprintf('\1_%d_%d_%d.jpg', $x, $y, (int)$truncateTimeData),
			$file->getPathname()
		);
		file_put_contents($fileName, $base->getImageBlob());
		$spl = new \SplFileInfo($fileName);

		// Compress/optimize image using jpegoptim (optional).
		$this->compress($spl);

		return $spl;
	}

	/** @throws \ImagickException */
	private function fillNoaaLogo(\Imagick $imagick): void
	{
		$noaaFill = new \Imagick(self::PIXEL_FILE);
		[$noaaX, $noaaY] = self::NOAA_LOGO_SIZE;
		$noaaFill->scaleImage($noaaX, $noaaY);

		$imagick->compositeImage($noaaFill, self::COMPOSITE_MODE, 0, self::NOAA_LOGO_START);
	}

	private function compress(\SplFileInfo $file): void
	{
		$binExists = (new Process(['which', 'jpegoptim']))->run() === 0;
		if (!$binExists) {
			return;
		}
		$process = new Process([
			'jpegoptim',
			'-s',
			'-m' . $this->jpegQuality,
			$file->getBasename(),
		], $file->getPath());
		$process->run();
	}
}