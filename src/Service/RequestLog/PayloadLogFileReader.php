<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Reads the monolog `request_payload` channel log files
 * (var/log/payload-YYYY-MM-DD.log) and parses them into RequestLogEntry DTOs.
 */
final class PayloadLogFileReader
{
    private const LOG_FILE_PATTERN = 'payload-*.log';

    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        private readonly string $logsDir,
    ) {
    }

    /**
     * @return RequestLogEntry[] newest entries first
     */
    public function readAll(): array
    {
        $entries = [];

        foreach (glob($this->logsDir.'/'.self::LOG_FILE_PATTERN) ?: [] as $file) {
            $handle = fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }

            try {
                while (($line = fgets($handle)) !== false) {
                    $entry = $this->parseLine($line);
                    if ($entry !== null) {
                        $entries[] = $entry;
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        usort($entries, static fn (RequestLogEntry $a, RequestLogEntry $b): int => $b->timestamp <=> $a->timestamp);

        return $entries;
    }

    private function parseLine(string $line): ?RequestLogEntry
    {
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            return null;
        }

        $pattern = '/^\[(?<ts>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+)[+-]\d{2}:\d{2}\] '
            . '\w+\.INFO: (?<message>Request( payload)?\.|Response\.) (?<json>\{.*\}) \[\]$/';

        if (preg_match($pattern, $line, $m) !== 1) {
            return null;
        }

        $data = json_decode($m['json'], true);
        if (!is_array($data)) {
            return null;
        }

        $isResponse = str_starts_with($m['message'], 'Response');

        try {
            return new RequestLogEntry(
                timestamp: new \DateTimeImmutable($m['ts']),
                type: $isResponse ? 'response' : 'request',
                method: (string) ($data['method'] ?? ''),
                route: isset($data['route']) && is_string($data['route']) ? $data['route'] : null,
                uri: (string) ($data['uri'] ?? ''),
                ip: isset($data['ip']) && is_string($data['ip']) ? $data['ip'] : null,
                payload: is_array($data['payload'] ?? null) ? $data['payload'] : [],
                status: isset($data['status']) && is_int($data['status']) ? $data['status'] : null,
                contentType: isset($data['content_type']) && is_string($data['content_type']) ? $data['content_type'] : null,
                contentLength: isset($data['content_length']) && is_int($data['content_length']) ? $data['content_length'] : null,
                durationMs: isset($data['duration_ms']) && is_int($data['duration_ms']) ? $data['duration_ms'] : null,
            );
        } catch (\Exception) {
            return null;
        }
    }
}
