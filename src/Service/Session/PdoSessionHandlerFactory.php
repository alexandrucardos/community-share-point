<?php

declare(strict_types=1);

namespace App\Service\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * Builds the database session handler for the configured DATABASE_URL.
 *
 * Column names are the handler's defaults (`sessions` / `sess_id` /
 * `sess_data` / `sess_lifetime` / `sess_time`), matching the sessions table
 * migration.
 *
 * The default transactional lock is kept for server databases (MySQL,
 * PostgreSQL), where it serializes concurrent requests sharing a session.
 * SQLite instead locks the whole database file while the session transaction
 * is open, which makes any other write in the same request (the request log,
 * for example) fail with "database is locked", so it runs without a lock.
 */
final class PdoSessionHandlerFactory
{
    public function __construct(
        private readonly string $databaseUrl,
    ) {
    }

    public function create(): PdoSessionHandler
    {
        $options = [];

        if ($this->isSqlite()) {
            $options['lock_mode'] = PdoSessionHandler::LOCK_NONE;
        }

        return new PdoSessionHandler($this->databaseUrl, $options);
    }

    private function isSqlite(): bool
    {
        // Matches DSNs (`sqlite:/path`) and URLs (`sqlite:///path`,
        // `pdo-sqlite:///path`); parse_url() rejects the triple-slash form.
        return 1 === preg_match('#^(?:pdo[_-])?sqlite3?:#i', $this->databaseUrl);
    }
}
