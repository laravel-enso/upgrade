<?php

namespace LaravelEnso\Upgrade\Services\DBAL;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Facades\Config;

class Connection
{
    private DBALConnection $connection;

    public function __construct()
    {
        $db = Config::get('database.default');
        $connection = Config::get("database.connections.{$db}");

        $connectionParams = [
            'dbname' => $connection['database'],
            'user' => $connection['username'],
            'password' => $connection['password'],
            'host' => $connection['host'],
            'driver' => "pdo_{$connection['driver']}",
        ];

        $this->connection = DriverManager::getConnection($connectionParams);
    }

    public function schemaManager(): AbstractSchemaManager
    {
        return $this->connection->createSchemaManager();
    }

    public function introspectTable(string $table): Table
    {
        return $this->schemaManager()->introspectTable($table);
    }

    public function column(string $table, string $column): Column
    {
        return $this->introspectTable($table)->getColumn($column);
    }
}
