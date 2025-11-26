<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Tenant;

return [

    'tenant_model' => Tenant::class,

    'id_generator' => Stancl\Tenancy\UUIDGenerator::class, // usa ULID ou UUID bonito

    /*
    |---------------------------------------------------------------------------
    | Domínios centrais (a tua app principal)
    |---------------------------------------------------------------------------
    */
    'central_domains' => [
        'localhost',
        '127.0.0.1',
        'gestao.test',           // altera aqui para o teu domínio local
        'gestao.local',
        // em produção será: app.tuadominio.pt
    ],

    /*
    |---------------------------------------------------------------------------
    | Bootstrappers – o que fica tenant-aware
    |---------------------------------------------------------------------------
    */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],

    /*
    |---------------------------------------------------------------------------
    | Base de dados – Single Database (mais simples e recomendado para ti)
    |---------------------------------------------------------------------------
    */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,

        // Usamos apenas prefixo + tenant_id na mesma base de dados (mais fácil e rápido)
        'prefix' => 'tenant_',
        'suffix' => '',

        'managers' => [
            'mysql'  => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'pgsql'  => Stancl\Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Cache, Filesystem, Redis
    |---------------------------------------------------------------------------
    */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => ['local', 'public'],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant_',
        'prefixed_connections' => [],
    ],

    /*
    |---------------------------------------------------------------------------
    | Features extras (ativamos só o que precisas agora)
    |---------------------------------------------------------------------------
    */
    'features' => [
        // Stancl\Tenancy\Features\ViteBundler::class,
        // Stancl\Tenancy\Features\TenantConfig::class,
    ],

    'routes' => true,

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'TenantDatabaseSeeder',
    ],

];