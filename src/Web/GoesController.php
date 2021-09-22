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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Tholcomb\Goes\UserException;
use Tholcomb\Goes\UserService;

class GoesController
{
	public function __construct(
		private UserService $user,
		private LoggerInterface $log,
	) {
	}

	#[Route(path: '/latest.jpg', methods: ['GET', 'HEAD'])]
	public function getLatestImage(Request $req): Response
	{
		// Check for Api-Key. Return 403 if not present.
		if ($req->headers->has('Api-Key')) {
			$apiKey = $req->headers->get('Api-Key');
		} elseif ($req->query->has('apiKey')) {
			$apiKey = $req->query->get('apiKey');
		} else {
			$this->log->warning('Received request without Api-Key');
			throw new AccessDeniedHttpException();
		}

		// Find user. Return 403 if unable to locate.
		try {
			$user = $this->user->getUser(strtolower($apiKey));
		} catch (UserException $e) {
			$this->log->warning(sprintf('Could not find user for Api-Key "%s"', $e->getApiKey()));
			throw new AccessDeniedHttpException();
		}

		// Check for new image. Save returned image if request isn't HEAD.
		$image = $this->user->getNewImageForUser($user, $req->getMethod() === 'GET');
		if ($image === null) {
			return new Response(null, 201); // Return no content if no new image to save bandwidth.
		}

		return new BinaryFileResponse($image->getPath(), 200, [
			'Content-Type' => 'image/jpeg',
		], false);
	}
}