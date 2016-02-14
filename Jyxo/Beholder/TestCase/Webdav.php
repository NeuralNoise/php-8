<?php

/**
 * Jyxo PHP Library
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * https://github.com/jyxo/php/blob/master/license.txt
 */

namespace Jyxo\Beholder\TestCase;

/**
 * Tests WebDAV availability.
 *
 * @category Jyxo
 * @package Jyxo\Beholder
 * @copyright Copyright (c) 2005-2011 Jyxo, s.r.o.
 * @license https://github.com/jyxo/php/blob/master/license.txt
 * @author Jaroslav Hanslík
 */
class Webdav extends \Jyxo\Beholder\TestCase
{
	/**
	 * Server hostname.
	 *
	 * @var string
	 */
	private $server;

	/**
	 * Tested directory.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Connection options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param string $description Test description
	 * @param string $server Server hostname
	 * @param string $dir Tested directory
	 * @param array $options Connection options
	 */
	public function __construct($description, $server, $dir = '', array $options = array())
	{
		parent::__construct($description);

		$this->server = (string) $server;
		$this->dir = (string) $dir;
		$this->options = $options;
	}

	/**
	 * Performs the test.
	 *
	 * @return \Jyxo\Beholder\Result
	 */
	public function run()
	{
		// The \Jyxo\Webdav\Client class is required
		if (!class_exists('\Jyxo\Webdav\Client')) {
			return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::NOT_APPLICABLE, 'Class \Jyxo\Webdav\Client missing');
		}

		$random = md5(uniqid(time(), true));
		$dir = trim($this->dir, '/');
		if (!empty($dir)) {
			$dir = '/' . $dir;
		}
		$path = $dir . '/beholder-' . $random . '.txt';
		$content = $random;

		// Status label
		$serverUrl = $this->server;
		if (!preg_match('~^https?://~', $this->server)) {
			$serverUrl = 'http://' . $serverUrl;
		}
		$parsed = parse_url($serverUrl);
		$host = $parsed['host'];
		$port = !empty($parsed['port']) ? $parsed['port'] : 80;
		$description = (false !== filter_var($host, FILTER_VALIDATE_IP) ? gethostbyaddr($host) : $host) . ':' . $port . $dir;

		try {
			$webdav = new \Jyxo\Webdav\Client(array($serverUrl));
			foreach ($this->options as $name => $value) {
				$webdav->setRequestOption($name, $value);
			}

			// Writing
			$webdav->put($path, $content);

			// Exists
			if (!$webdav->exists($path)) {
				return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Exists error %s', $description));
			}

			// Reading
			$readContent = $webdav->get($path);
			if (strlen($readContent) !== strlen($content)) {
				return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Read error %s', $description));
			}

			// Deleting
			$webdav->unlink($path);
		} catch (\Jyxo\Webdav\FileNotCreatedException $e) {
			return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Write error %s', $description));
		} catch (\Jyxo\Webdav\FileNotExistException $e) {
			return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Read error %s', $description));
		} catch (\Jyxo\Webdav\FileNotDeletedException $e) {
			return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Delete error %s', $description));
		} catch (\Jyxo\Webdav\Exception $e) {
			return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::FAILURE, sprintf('Error %s', $description));
		}

		// OK
		return new \Jyxo\Beholder\Result(\Jyxo\Beholder\Result::SUCCESS, $description);
	}
}
