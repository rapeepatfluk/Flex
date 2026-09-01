<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}
require_once __DIR__ . '/../config/config.php';
const MIGRATIONS_TABLE = 'schema_migrations';

function usage(): void {
    echo "FLEXJOB database migration runner\n\n";
    echo "Usage:\n";
    echo "  php database/migrate.php             Apply pending migrations\n";
    echo "  php database/migrate.php --status    Show migration status\n";
    echo "  php database/migrate.php --baseline  Mark the initial schema as applied\n";
}
function migrationFiles(): array {
    $files = glob(__DIR__ . '/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);
    if ($files === []) throw new RuntimeException('No migration files found in database/migrations.');
    $migrations = [];
    foreach ($files as $file) $migrations[basename($file)] = ['path' => $file, 'checksum' => hash_file('sha256', $file)];
    return $migrations;
}
function sqlStatements(string $sql): array {
    $statements = []; $buffer = ''; $quote = null; $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $character = $sql[$i]; $next = $i + 1 < $length ? $sql[$i + 1] : '';
        if ($quote !== null) {
            $buffer .= $character;
            if ($character === '\\' && $i + 1 < $length) { $buffer .= $sql[++$i]; continue; }
            if ($character === $quote) {
                if (($quote === "'" || $quote === '"') && $next === $quote) { $buffer .= $sql[++$i]; continue; }
                $quote = null;
            }
            continue;
        }
        if ($character === "'" || $character === '"') { $quote = $character; $buffer .= $character; continue; }
        if ($character === ';') {
            $statement = trim($buffer);
            if ($statement !== '') $statements[] = $statement;
            $buffer = '';
            continue;
        }
        $buffer .= $character;
    }
    $statement = trim($buffer);
    if ($statement !== '') $statements[] = $statement;
    return $statements;
}
function pdoServer(): PDO {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (!preg_match('/^[A-Za-z0-9_]+$/', DB_NAME)) throw new RuntimeException('Invalid database name configured.');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    return $pdo;
}
function pdoDatabase(): PDO {
    return new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
function createMigrationsTable(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS ' . MIGRATIONS_TABLE . ' (migration VARCHAR(255) NOT NULL PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
}
function appliedMigrations(PDO $pdo): array {
    $rows = $pdo->query('SELECT migration, checksum FROM ' . MIGRATIONS_TABLE)->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows ?: [];
}
function ensureChecksums(array $migrations, array $applied): void {
    foreach ($applied as $name => $checksum) {
        if (isset($migrations[$name]) && !hash_equals($checksum, $migrations[$name]['checksum'])) throw new RuntimeException("Checksum mismatch for applied migration: {$name}");
    }
}
function hasExistingSchema(PDO $pdo): bool {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name=?');
    $statement->execute([DB_NAME, 'users']);
    return (int) $statement->fetchColumn() === 1;
}
$option = $argv[1] ?? '';
if (in_array($option, ['-h', '--help'], true)) { usage(); exit(0); }
if ($option !== '' && !in_array($option, ['--status', '--baseline'], true)) { usage(); exit(1); }

try {
    pdoServer(); $pdo = pdoDatabase(); createMigrationsTable($pdo);
    $migrations = migrationFiles(); $applied = appliedMigrations($pdo); ensureChecksums($migrations, $applied);
    if ($option === '--status') {
        foreach ($migrations as $name => $migration) echo str_pad(isset($applied[$name]) ? 'applied' : 'pending', 8) . " {$name}\n";
        exit(0);
    }
    if ($option === '--baseline') {
        $initial = array_key_first($migrations);
        if (isset($applied[$initial])) { echo "Initial schema migration is already recorded.\n"; exit(0); }
        if (!hasExistingSchema($pdo)) throw new RuntimeException('Cannot baseline: the existing FLEXJOB schema was not found.');
        $insert = $pdo->prepare('INSERT INTO ' . MIGRATIONS_TABLE . ' (migration, checksum) VALUES (?, ?)');
        $insert->execute([$initial, $migrations[$initial]['checksum']]);
        echo "Baselined {$initial}. Run migrate.php again for future migrations.\n";
        exit(0);
    }
    foreach ($migrations as $name => $migration) {
        if (isset($applied[$name])) continue;
        echo "Applying {$name} ... ";
        foreach (sqlStatements((string) file_get_contents($migration['path'])) as $statement) $pdo->exec($statement);
        $insert = $pdo->prepare('INSERT INTO ' . MIGRATIONS_TABLE . ' (migration, checksum) VALUES (?, ?)');
        $insert->execute([$name, $migration['checksum']]);
        echo "done\n";
    }
    echo "Database is up to date.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
