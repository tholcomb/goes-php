<?php
/*
 * This file is part of the goes-php project
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/********************************************************************************
 * This script demonstrates how to download images from goes-php using PHP & cURL
 ********************************************************************************/

$filePath = $_SERVER['argv'][1] ?? null;

$usage = sprintf("\nUsage: %s <absolute_path>\n\n", basename(__FILE__));
if ($filePath === null) {
	echo $usage;
	echo "Please provide a path to save the file\n";
	exit(2);
} elseif (substr($filePath, 0, 1) !== '/') {
	echo $usage;
	echo "Please provide an absolute file path\n";
	exit(2);
}

$ch = curl_init('https://goes-php.example.com/latest.jpg?apiKey=$API_KEY');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// You can also pass the API Key as a header instead of a GET parameter
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Api-Key: $API_KEY',
]);

$image = curl_exec($ch);
$info = curl_getinfo($ch);
if ($info['http_code'] === 200) { // We got a new image
	file_put_contents($filePath, $image);
	exit(0);
} elseif ($info['http_code'] === 201) { // There wasn't a new image
	exit(1); // Exit with non-zero code to prevent further script execution (see the shell script in this folder)
} else {
	echo "An error occurred on download\n";
	exit(2);
}