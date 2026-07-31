<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Shared routing constants for the group-scoped URL space (`/group/{uuid}/...`).
 */
final class GroupRoute
{
    /**
     * Route requirement for the group id. Case-insensitive hex because group
     * ids are stored/emitted in uppercase (Symfony's Requirement::UUID is
     * lowercase-only and would 404 on them).
     */
    public const UUID = '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}';
}
