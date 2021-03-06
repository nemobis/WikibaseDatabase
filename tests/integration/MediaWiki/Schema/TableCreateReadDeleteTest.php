<?php

namespace Wikibase\Database\Tests;

use Wikibase\Database\LazyDBConnectionProvider;
use Wikibase\Database\MediaWiki\MediaWikiQueryInterface;
use Wikibase\Database\MediaWiki\MWTableBuilderBuilder;
use Wikibase\Database\MediaWiki\MWTableDefinitionReaderBuilder;
use Wikibase\Database\Schema\Definitions\FieldDefinition;
use Wikibase\Database\Schema\Definitions\IndexDefinition;
use Wikibase\Database\Schema\Definitions\TableDefinition;
use Wikibase\Database\Schema\Definitions\TypeDefinition;

/**
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseDatabase
 * @group Integration
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class TableCreateReadDeleteTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		parent::tearDown();

		$this->dropTablesIfStillThere();
	}

	/**
	 * Use the tableProvider that is uses for the test and drop any tables that already exist!
	 * This is a cleanup operation to be run before the tests
	 */
	protected function dropTablesIfStillThere() {
		$tableBuilder = $this->newTableBuilder();

		$tabeProvider = $this->tableProvider();
		foreach( $tabeProvider as $provider ){
			/** @var TableDefinition $tableDefinition */
			foreach( $provider as $tableDefinition ){

				$tableName = $tableDefinition->getName();
				if ( $tableBuilder->tableExists( $tableName ) ) {
					$tableBuilder->dropTable( $tableName );
				}

			}
		}
	}

	protected function newTableBuilder() {
		$connectionProvider = new LazyDBConnectionProvider( DB_MASTER );

		$tbBuilder = new MWTableBuilderBuilder();
		return $tbBuilder->setConnection( $connectionProvider )->getTableBuilder();
	}

	protected function newTableReader() {
		$connectionProvider = new LazyDBConnectionProvider( DB_MASTER );

		$trBuilder = new MWTableDefinitionReaderBuilder();
		return $trBuilder
			->setConnection( $connectionProvider )
			->setQueryInterface( $this->newQueryInterface() )
			->getTableDefinitionReader( $this->newQueryInterface() );
	}

	protected function newQueryInterface() {
		$connectionProvider = new LazyDBConnectionProvider( DB_MASTER );

		return new MediaWikiQueryInterface( $connectionProvider );
	}

	public function tableProvider() {
		$tables = array();

		$tables[] = new TableDefinition( 'different_field_types', array(
				new FieldDefinition( 'intfield', new TypeDefinition( TypeDefinition::TYPE_INTEGER ) ),
				new FieldDefinition( 'floatfield', new TypeDefinition( TypeDefinition::TYPE_FLOAT ) ),
				new FieldDefinition( 'textfield', new TypeDefinition( TypeDefinition::TYPE_BLOB ) ),
				new FieldDefinition( 'tinyintfield', new TypeDefinition( TypeDefinition::TYPE_TINYINT ) ),
			)
		);

		$tables[] = new TableDefinition( 'autoinc_field', array(
				new FieldDefinition( 'autoinc', new TypeDefinition( TypeDefinition::TYPE_INTEGER ), FieldDefinition::NOT_NULL, FieldDefinition::NO_DEFAULT, FieldDefinition::AUTOINCREMENT ),
			),
			array(
				new IndexDefinition( 'PRIMARY', array( 'autoinc' ), IndexDefinition::TYPE_PRIMARY ),
			)
		);

		$tables[] = new TableDefinition( 'not_null_fields', array(
				new FieldDefinition( 'intfield', new TypeDefinition( TypeDefinition::TYPE_INTEGER ), FieldDefinition::NOT_NULL, 42 ),
				new FieldDefinition( 'textfield', new TypeDefinition( TypeDefinition::TYPE_BLOB ), FieldDefinition::NOT_NULL ),
			)
		);

		$tables[] = new TableDefinition( 'default_field_values_and_indexes', array(
				new FieldDefinition( 'textfield', new TypeDefinition( TypeDefinition::TYPE_BLOB ), FieldDefinition::NOT_NULL ),
				new FieldDefinition( 'intfield', new TypeDefinition( TypeDefinition::TYPE_INTEGER ), FieldDefinition::NOT_NULL, 3 ),
				new FieldDefinition( 'floatfield', new TypeDefinition( TypeDefinition::TYPE_FLOAT ), FieldDefinition::NULL ),
				new FieldDefinition( 'tinyintfield', new TypeDefinition( TypeDefinition::TYPE_TINYINT ), FieldDefinition::NOT_NULL, 1 ),
			),
			array(
				new IndexDefinition( 'PRIMARY', array( 'intfield' ), IndexDefinition::TYPE_PRIMARY ),
				new IndexDefinition( 'uniqueIndexName', array( 'floatfield' ), IndexDefinition::TYPE_UNIQUE ),
				new IndexDefinition( 'somename', array( 'intfield', 'floatfield' ) ),
			)
		);

		$tables[] = new TableDefinition( 'this_fucking_sqlite_is_weird',
			array(
				new FieldDefinition('row_id',
					new TypeDefinition(
						TypeDefinition::TYPE_INTEGER
					),
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::AUTOINCREMENT
				),

				new FieldDefinition(
					'entity_type',
					new TypeDefinition(
						TypeDefinition::TYPE_VARCHAR,
						8
					),
					FieldDefinition::NOT_NULL
				),

				new FieldDefinition(
					'entity_id',
					new TypeDefinition(
						TypeDefinition::TYPE_VARCHAR,
						16
					),
					FieldDefinition::NOT_NULL
				),

				new FieldDefinition(
					'property_id',
					new TypeDefinition(
						TypeDefinition::TYPE_VARCHAR,
						16
					),
					FieldDefinition::NOT_NULL
				),

				new FieldDefinition(
					'statement_rank',
					new TypeDefinition(
						TypeDefinition::TYPE_TINYINT
					),
					FieldDefinition::NOT_NULL
				),

				new FieldDefinition(
					'value',
					new TypeDefinition( TypeDefinition::TYPE_DECIMAL ),
					FieldDefinition::NOT_NULL
				),
			),
			array(
				new IndexDefinition(
					'PRIMARY',
					array( 'row_id' ),
					IndexDefinition::TYPE_PRIMARY
				),
				new IndexDefinition(
					'entity_id_index',
					array( 'entity_id', ),
					IndexDefinition::TYPE_INDEX
				),

				new IndexDefinition(
					'property_id_index',
					array( 'property_id', ),
					IndexDefinition::TYPE_INDEX
				),

				new IndexDefinition(
					'value_property',
					array(
						'value',
						'property_id',
						'entity_id',
					),
					IndexDefinition::TYPE_UNIQUE
				),

				new IndexDefinition(
					'value',
					array( 'value' )
				),
			)
		);

		$argLists = array();

		foreach ( $tables as $table ) {
			$argLists[] = array( $table );
		}

		return $argLists;
	}

	/**
	 * @dataProvider tableProvider
	 *
	 * @param TableDefinition $table
	 */
	public function testCreateReadDeleteTable( TableDefinition $table ) {
		$tableBuilder = $this->newTableBuilder();

		$this->assertFalse(
			$tableBuilder->tableExists( $table->getName() ),
			'Table should not exist before creation'
		);

		$tableBuilder->createTable( $table );

		$this->assertTrue(
			$tableBuilder->tableExists( $table->getName() ),
			'Table "' . $table->getName() . '" exists after creation'
		);

		$tableReader = $this->newTableReader();
		$this->assertEquals(
			$table,
			$tableReader->readDefinition( $table->getName() )
		);

		$tableBuilder->dropTable( $table->getName() );

		$this->assertFalse(
			$tableBuilder->tableExists( $table->getName() ),
			'Table should not exist after deletion'
		);
	}

}
