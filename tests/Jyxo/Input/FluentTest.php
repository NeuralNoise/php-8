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

namespace Jyxo\Input;

/**
 * Test of \Jyxo\Input\Fluent and chained classes.
 *
 * @copyright Copyright (c) 2005-2011 Jyxo, s.r.o.
 * @license https://github.com/jyxo/php/blob/master/license.txt
 * @author Jakub Tománek
 */
class FluentTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Tests basic work.
	 */
	public function testBasicFluent()
	{
		$input = new Fluent();

		$input
			->check(' 42 ', 'answer')
				->filter('trim')
				->validate('isInt');

		$this->assertTrue($input->isValid());
		$this->assertEquals(array('answer' => '42'), $input->getValues());
	}

	/**
	 * Tests default value.
	 */
	public function testDefaultValue()
	{
		$input = new Fluent();

		$input
			->check('', 'message')
				->validate('notEmpty')
				->defaultValue('default');

		$this->assertTrue($input->isValid());
		$this->assertEquals('default', $input->message);

		$this->expectException(\BadMethodCallException::class);
		$input = new Fluent();
		$input->defaultValue('default');
	}

	/**
	 * Tests walking through an array.
	 */
	public function testWalk()
	{
		$current = array(
			'aBcDe',
			'Jakub',
			'ŽLUŤOUČKÝ kůň PĚL ďábelské ÓDY'
		);
		$expected = array(
			'abcde',
			'jakub',
			'žluťoučký kůň pěl ďábelské ódy'
		);

		$input1 = new Fluent();
		$input1
			->check($current, 'data')
				->filter('lowerCase')
				->validate('isArray');
		$input2 = new Fluent();
		$input2
			->check($current, 'data')
				->walk()
					->filter('lowercase')
					->validate('stringLengthGreaterThan', '', 4);

		$this->assertTrue($input1->isValid());
		$this->assertTrue($input2->isValid());
		$this->assertEquals($expected, $input1->data);
		$this->assertEquals($expected, $input2->data);
	}

	/**
	 * Tests superglobal arrays.
	 */
	public function testSuperglobals()
	{
		$_REQUEST['data'] = $_POST['data'] = 'string';
		$_REQUEST['jyxo'] = $_GET['jyxo'] = '1';

		$input = new Fluent();
		$input
			->post('data')
				->validate('stringLengthGreaterThan', 'short', 5)
				->validate('stringLengthLessThan', 'long', 7)
			->query('jyxo')
				->validate('isInt')
			->request('jyxo')
				->validate('lessThan', 'big', 100);

		$this->assertTrue($input->isValid());
		$this->assertNull($input->validateAll());
	}

	/**
	 * Tests invalid input data.
	 */
	public function testInvalid()
	{
		$input = new Fluent();

		$input
			->check('foo', 'foo')
				->validate('isInt', 'not int');

		$this->assertFalse($input->isValid());
		$this->assertEquals(array('not int'), $input->getErrors());

		$input = new Fluent();
		$input
			->check('bar', 'bar')
				->validate('isInt', 'not int');

		$this->assertFalse($input->isValid(true));
		$this->assertEquals(array('bar' => array('not int')), $input->getErrors());

		$input = new Fluent();
		$input
			->check('foo', 'foo')
			->all()
				->validate('isInt');

		$this->assertFalse($input->isValid());

		$this->expectException(\Jyxo\Input\Exception::class);
		$input->getValue('bar');
	}

	/**
	 * Tests validation failure in the middle of a string.
	 */
	public function testInvalidWalk()
	{
		$current = array(
			42,
			0,
			'nulák'
		);

		$input = new Fluent();
		$input
			->check($current, 'data')
				->walk(false)
					->validate('isInt');

		$this->assertFalse($input->isValid());

		try {
			$input->validateAll();
			$this->fail('Expected exception \Jyxo\Input\Validator\Exception.');
		} catch (\PHPUnit_Framework_AssertionFailedError $e) {
			throw $e;
		} catch (\Exception $e) {
			// Correctly thrown exception
			$this->assertInstanceOf('\Jyxo\Input\Validator\Exception', $e);
		}

	}

	/**
	 * Tests conditional validation.
	 */
	public function testConditional()
	{
		$good = array(
			43 => '42',		// Condition fulfilled, validates
			20 => '42.23',		// Condition not fulfilled, no validation - true is returned
			20 => 'example',
			20 => array(),
			20 => true,
			20 => false,
		);
		// Condition fulfilled, but validation fails
		$bad = array(
			30 => '42',
			'test' => '42',
			1.23 => '42',
			true => 42
		);

		// Complex value test
		foreach (array(true => $good, false => $bad) as $result => $values) {
			foreach ($values as $lessThan => $value) {
				$input = new Fluent();
				$input
					->check($value, 'answer')
						->condition('isInt')
						->validate('lessThan', 'error', $lessThan);
				$this->assertEquals(
					(bool) $result,
					$input->isValid(),
					sprintf('Test of value %s should be %s but is %s.', $value, var_export((bool) $result, true), var_export(!$result, true))
				);
			}
		}

		// Deep chain test
		$input = new Fluent();
		$input
			->check(42, 'answer')
				->validate('notEmpty', 'error')
				->condition('isInt')
					->validate('lessThan', 'error', 100);
		$this->assertTrue($input->isValid());

		// Not an active variable
		$this->expectException(\BadMethodCallException::class);
		$input = new Fluent();
		$input->condition('isInt');
	}

	/**
	 * Tests chain closing.
	 */
	public function testClose()
	{
		$input = new Fluent();
		$input
			->check(42, 'anwer')
				->validate('notEmpty', 'error')
				->condition('isInt')
					->validate('lessThan', 'error', 100)
					->close()
				->validate('isInt');
		$this->assertTrue($input->isValid());

		// But it's not a ZIP code...
		$input->validate('isZipCode');
		$this->assertFalse($input->isValid());
	}

	/**
	 * Tests adding an invalid filter.
	 */
	public function testAddInvalidFilter()
	{
		$this->expectException(\Jyxo\Input\Exception::class);
		$input = new Fluent();
		$input->filter('foo');
	}

	/**
	 * Tests adding an invalid condition.
	 */
	public function testAddInvalidCondition()
	{
		$this->expectException(\Jyxo\Input\Exception::class);
		$input = new Fluent();
		$input->condition('foo');
	}

	/**
	 * Tests adding an invalid validator.
	 */
	public function testAddInvalidValidator()
	{
		$this->expectException(\Jyxo\Input\Exception::class);
		$input = new Fluent();
		$input->validate('foo');
	}
}
