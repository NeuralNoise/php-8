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

namespace Jyxo\Time;

/**
 * Test for the \Jyxo\Time\ComposerException class.
 *
 * @see \Jyxo\Time\ComposerException
 * @copyright Copyright (c) 2005-2011 Jyxo, s.r.o.
 * @license https://github.com/jyxo/php/blob/master/license.txt
 * @author Jaroslav Hanslík
 */
class ComposerExceptionTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * The whole test.
	 */
	public function test()
	{
		// All possible codes.
		$reflection = new \ReflectionClass(\Jyxo\Time\ComposerException::class);
		foreach ($reflection->getConstants() as $code) {
			$exception = new ComposerException('Test', $code);
			$this->assertEquals($code, $exception->getCode());
		}

		// Non-existent code
		$exception = new ComposerException('Test', 'dummy-code');
		$this->assertEquals(ComposerException::UNKNOWN, $exception->getCode());
	}
}
