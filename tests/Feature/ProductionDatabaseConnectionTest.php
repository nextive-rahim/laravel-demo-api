<?php

use Pdo\Mysql;

/**
 * Re-evaluates config/database.php against a given set of environment variables.
 *
 * @param  array<string, string>  $environment
 * @return array<string, mixed>
 */
function resolveProductionConnection(array $environment = []): array
{
    $original = $_SERVER;

    foreach ($environment as $key => $value) {
        $_SERVER[$key] = $value;
    }

    try {
        return (require config_path('database.php'))['connections']['mysql_production'];
    } finally {
        $_SERVER = $original;
    }
}

it('exposes a mysql_production connection driven by PROD_DB_* variables', function (): void {
    $connection = resolveProductionConnection([
        'PROD_DB_HOST' => 'mysql-24d09e1f-laravel-learning.h.aivencloud.com',
        'PROD_DB_PORT' => '17286',
        'PROD_DB_DATABASE' => 'defaultdb',
        'PROD_DB_USERNAME' => 'avnadmin',
        'PROD_DB_PASSWORD' => 'secret',
    ]);

    expect($connection['driver'])->toBe('mysql')
        ->and($connection['host'])->toBe('mysql-24d09e1f-laravel-learning.h.aivencloud.com')
        ->and($connection['port'])->toBe('17286')
        ->and($connection['database'])->toBe('defaultdb')
        ->and($connection['username'])->toBe('avnadmin')
        ->and($connection['password'])->toBe('secret');
});

it('never lets the production connection become the default', function (): void {
    expect(config('database.default'))->not->toBe('mysql_production');
});

it('omits SSL options entirely when no CA certificate is configured', function (): void {
    expect(resolveProductionConnection()['options'])->toBe([]);
});

it('verifies the server certificate once a CA certificate is configured', function (): void {
    $options = resolveProductionConnection([
        'PROD_DB_SSL_CA' => '/tmp/aiven-ca.pem',
    ])['options'];

    expect($options[Mysql::ATTR_SSL_CA])->toBe('/tmp/aiven-ca.pem')
        ->and($options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT])->toBeTrue();
});
