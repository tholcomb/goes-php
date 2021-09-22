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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Tholcomb\Symple\Console\Commands\AbstractCommand;
use const Tholcomb\Goes\PROJECT_ROOT;

class InstallCommand extends AbstractCommand
{
	protected const NAME = 'goes:install';

	public function __construct(
		private string $dbPath,
		private string $proxyDir,
		private string $imageDir,
	) {
		parent::__construct();
	}

	/** @throws */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// Prevent double run
		if (file_exists(PROJECT_ROOT . '/var')) {
			throw new \Exception('Install script already ran');
		}

		// Create directories
		$dirPermissions = 02775; // setgid bit set, so files inherit group ownership
		mkdir(PROJECT_ROOT . '/var', $dirPermissions);
		mkdir($this->proxyDir, $dirPermissions, true);
		mkdir($this->imageDir, $dirPermissions, true);

		// Create new sqlite DB
		$dbRes = Process::fromShellCommandline('echo ".save $DB_PATH" | sqlite3')
			->run(null, ['DB_PATH' => $this->dbPath]);
		if ($dbRes !== 0) {
			throw new \Exception('Could not create database');
		}
		chmod($this->dbPath, 0664);

		// Create DB schema
		$schemaRes = (new Process([PHP_BINARY, 'vendor/bin/doctrine', 'orm:schema-tool:create']))->run();
		if ($schemaRes !== 0) {
			throw new \Exception('Could not create schema');
		}

		// Notify
		$msg = 'Installation complete! Don\'t forget to setup CRON and add users!';
		$block = $this->getHelper('formatter')->formatBlock($msg, 'fg=black;bg=green', true);
		$output->writeln("\n{$block}\n");

		// Offer to add user
		$question = new ConfirmationQuestion('Add user now? [y/n] ', false);
		$addUser = $this->getHelper('question')->ask($input, $output, $question);
		if ($addUser) {
			$args = ['action' => 'add'];
			$userInput = new ArrayInput($args);
			$this->getApplication()->find('goes:user')->run($userInput, $output);
		}

		return 0;
	}
}