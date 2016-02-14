<?php declare(strict_types = 1);

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

namespace Jyxo\Mail\Email\Attachment;

/**
 * \Jyxo\Mail\Email\Attachment\StringAttachment class test.
 *
 * @see \Jyxo\Mail\Email\Attachment\String
 * @copyright Copyright (c) 2005-2011 Jyxo, s.r.o.
 * @license https://github.com/jyxo/php/blob/master/license.txt
 * @author Jaroslav Hanslík
 */
class StringAttachmentTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Runs the test.
	 */
	public function test()
	{
		$content = file_get_contents(DIR_FILES . '/mail/logo.gif');
		$name = 'logo.gif';
		$mimeType = 'image/gif';

		$attachment = new StringAttachment($content, $name, $mimeType);
		$this->assertEquals($content, $attachment->getContent());
		$this->assertEquals($name, $attachment->getName());
		$this->assertEquals($mimeType, $attachment->getMimeType());
		$this->assertEquals(\Jyxo\Mail\Email\Attachment::DISPOSITION_ATTACHMENT, $attachment->getDisposition());
		$this->assertFalse($attachment->isInline());
		$this->assertEquals('', $attachment->getCid());
		$this->assertEquals('', $attachment->getEncoding());

		// It is possible to set an encoding
		$reflection = new \ReflectionClass(\Jyxo\Mail\Encoding::class);
		foreach ($reflection->getConstants() as $encoding) {
			$attachment->setEncoding($encoding);
			$this->assertEquals($encoding, $attachment->getEncoding());
		}

		// Incompatible encoding
		try {
			$attachment->setEncoding('dummy-encoding');
			$this->fail(sprintf('Expected exception %s.', \InvalidArgumentException::class));
		} catch (\PHPUnit_Framework_AssertionFailedError $e) {
			throw $e;
		} catch (\Exception $e) {
			// Correctly thrown exception
			$this->assertInstanceOf(\InvalidArgumentException::class, $e);
		}
	}
}
