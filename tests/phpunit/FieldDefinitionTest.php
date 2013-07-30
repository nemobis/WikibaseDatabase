<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\FieldDefinition;

/**
 * @covers Wikibase\Database\FieldDefinition
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseDatabaseTest
 *
 * @group Wikibase
 * @group WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FieldDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function instanceProvider() {
		$instances = array();

		$instances[] = new FieldDefinition(
			'names', FieldDefinition::TYPE_TEXT
		);

		$instances[] = new FieldDefinition(
			'numbers', FieldDefinition::TYPE_FLOAT
		);

		$instances[] = new FieldDefinition(
			'stuffs', FieldDefinition::TYPE_INTEGER, false, 42, FieldDefinition::ATTRIB_UNSIGNED
		);

		$instances[] = new FieldDefinition(
			'stuffs', FieldDefinition::TYPE_INTEGER, true, null, null
		);

		$instances[] = new FieldDefinition(
			'stuffs', FieldDefinition::TYPE_INTEGER, true, null, null, true
		);

		$argLists = array();

		foreach ( $instances as $instance ) {
			$argLists[] = array( $instance );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param FieldDefinition $field
	 */
	public function testReturnValueOfGetName( FieldDefinition $field ) {
		$this->assertInternalType( 'string', $field->getName() );

		$newField = new FieldDefinition( $field->getName(), $field->getType() );

		$this->assertEquals(
			$field->getName(),
			$newField->getName(),
			'The FieldDefinition name is set and obtained correctly'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param FieldDefinition $field
	 */
	public function testReturnValueOfGetType( FieldDefinition $field ) {
		$this->assertInternalType( 'string', $field->getType() );

		$newField = new FieldDefinition( $field->getName(), $field->getType() );

		$this->assertEquals(
			$field->getType(),
			$newField->getType(),
			'The FieldDefinition type is set and obtained correctly'
		);
	}

}
