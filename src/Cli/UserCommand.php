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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Tholcomb\Goes\Model\User;
use Tholcomb\Goes\UserService;
use Tholcomb\Symple\Console\Commands\AbstractCommand;

class UserCommand extends AbstractCommand
{
	protected const NAME = 'goes:user';

	public function __construct(
		private UserService $user,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument('action', InputArgument::REQUIRED, "'list', 'add', or 'remove'");
		$this->addArgument('key', InputArgument::OPTIONAL, 'Required for remove only');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		switch ($input->getArgument('action')) {
			case 'list':
				$this->listUsers($output);
				break;
			case 'add':
				return $this->addUser($input, $output);
			case 'remove':
				return $this->removeUser($input, $output);
			default:
				$output->writeln('Invalid action');
				return 1;
		}

		return 0;
	}

	private function listUsers(OutputInterface $output): void
	{
		$table = new Table($output);
		$table->setHeaders(['key', 'showTime', 'x', 'y']);
		$users = $this->user->getAllUsers();
		foreach ($users as $u) {
			$table->addRow([
				$u->getApiKey(),
				$u->includeTimeData() ? 'Y' : 'N',
				$u->getScreenX(),
				$u->getScreenY(),
			]);
		}
		$table->render();
	}

	private function addUser(InputInterface $input, OutputInterface $output): int
	{
		$helper = $this->getHelper('question');

		// Collect API Key. Does not need to be secure!!!
		$q = new Question('API Key: ');
		$q->setValidator(function ($answer) {
			if ($this->user->isKeyUnique($answer)) {
				return $answer;
			}
			throw new \Exception('Key already exists');
		});
		$key = $helper->ask($input, $output, $q);

		// Ask whether to truncate time info on image
		$q = new ConfirmationQuestion('Include time data? [y/n] ', false);
		$timeData = $helper->ask($input, $output, $q);

		// Collect screen size
		// TODO: Move choices/sizes elsewhere
		$q = new ChoiceQuestion('Select screen size', [
			'1080p',
			'iPhone 12 Mini',
			'Other',
		]);
		$q->setAutocompleterCallback(null); // Prevent autocomplete
		$size = $helper->ask($input, $output, $q);
		switch ($size) {
			case '1080p':
				[$x, $y] = [1920, 1080];
				break;
			case 'iPhone 12 Mini':
				[$x, $y] = [1080, 2340];
				break;
			default:
				$q = new Question('X: ');
				$x = (int)$helper->ask($input, $output, $q);
				$q = new Question('Y: ');
				$y = (int)$helper->ask($input, $output, $q);
		}

		// Create the user
		$this->user->createUser($key, $x, $y, $timeData);
		$output->writeln($this->getSuccessBlock("User '$key' created"));

		// Ask for another and recurse if yes
		$q = new ConfirmationQuestion('Add another user? [y/n] ', false);
		$again = $helper->ask($input, $output, $q);
		if ($again) {
			$this->addUser($input, $output);
		}

		return 0;
	}

	private function removeUser(InputInterface $input, OutputInterface $output): int
	{
		$key = $input->getArgument('key');
		if ($key === null) {
			throw new \InvalidArgumentException('Key is missing');
		}
		$this->user->removeUser($key);
		$output->writeln($this->getSuccessBlock("User '$key' removed"));

		return 0;
	}

	private function getSuccessBlock(string $msg): string
	{
		$formatter = $this->getHelper('formatter');

		return "\n" . $formatter->formatBlock($msg, 'fg=black;bg=green', true) . "\n";
	}
}