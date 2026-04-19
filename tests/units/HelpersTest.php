<?php

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Helpers\Column;
use LaravelEnso\Upgrade\Helpers\Table;
use LaravelEnso\Upgrade\Services\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('upgrade_helper_children', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->decimal('price', 8, 2)->nullable();
            $table->integer('quantity');
        });
    }

    #[Test]
    public function inspects_table_existence_and_columns_with_schema(): void
    {
        $this->assertTrue(Table::exists('upgrade_helper_children'));
        $this->assertTrue(Table::hasColumn('upgrade_helper_children', 'name'));
    }

    #[Test]
    public function inspects_indexes_and_foreign_keys_through_the_dbal_connection(): void
    {
        $foreignKey = Mockery::mock(ForeignKeyConstraint::class);
        $table = Mockery::mock(DoctrineTable::class);
        $schemaManager = Mockery::mock(AbstractSchemaManager::class);

        $schemaManager->shouldReceive('listTableIndexes')
            ->once()
            ->with('upgrade_helper_children')
            ->andReturn(['upgrade_helper_children_name_index' => (object) []]);

        $table->shouldReceive('hasForeignKey')
            ->once()
            ->with('upgrade_helper_children_name_foreign')
            ->andReturn(true);

        $table->shouldReceive('getForeignKey')
            ->once()
            ->with('upgrade_helper_children_name_foreign')
            ->andReturn($foreignKey);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('schemaManager')
            ->andReturn($schemaManager);
        $connection->shouldReceive('introspectTable')
            ->andReturn($table);

        $this->app->instance(Connection::class, $connection);

        $this->assertTrue(Table::hasIndex('upgrade_helper_children', 'upgrade_helper_children_name_index'));
        $this->assertTrue(Table::hasForeignKey('upgrade_helper_children', 'upgrade_helper_children_name_foreign'));
        $this->assertSame($foreignKey, Table::foreignKey('upgrade_helper_children', 'upgrade_helper_children_name_foreign'));
    }

    #[Test]
    public function inspects_column_types_and_dbal_metadata(): void
    {
        $column = Mockery::mock(DoctrineColumn::class);
        $table = Mockery::mock(DoctrineTable::class);
        $connection = Mockery::mock(Connection::class);

        $table->shouldReceive('getColumn')
            ->times(5)
            ->with('price')
            ->andReturn($column);

        $column->shouldReceive('getNotnull')->once()->andReturn(false);
        $column->shouldReceive('getPrecision')->once()->andReturn(8);
        $column->shouldReceive('getScale')->once()->andReturn(2);
        $column->shouldReceive('getLength')->once()->andReturn(50);
        $column->shouldReceive('getUnsigned')->once()->andReturn(false);

        $connection->shouldReceive('introspectTable')
            ->times(5)
            ->with('upgrade_helper_children')
            ->andReturn($table);

        $this->app->instance(Connection::class, $connection);

        $this->assertTrue(Column::isNullable('upgrade_helper_children', 'price'));
        $this->assertSame(8, Column::getPrecision('upgrade_helper_children', 'price'));
        $this->assertSame(2, Column::getScale('upgrade_helper_children', 'price'));
        $this->assertSame(50, Column::getLength('upgrade_helper_children', 'price'));
        $this->assertTrue(Column::isSigned('upgrade_helper_children', 'price'));
    }
}
