<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Service\RequestLog\PayloadLogFileReader;
use App\Service\RequestLog\RequestLogFilter;

$kernel = new Kernel('dev', false);
$kernel->boot();
$container = $kernel->getContainer();

$reader = $container->get(PayloadLogFileReader::class);

$filter = new RequestLogFilter();
$start = microtime(true);
$entries = $reader->read($filter);
$elapsed = microtime(true) - $start;

printf(
    "read: entries=%d total=%d elapsed=%.2fs peak=%.1fMB\n",
    count($entries),
    count($entries),
    $elapsed,
    memory_get_peak_usage(true) / 1048576,
);

foreach ($entries as $index => $entry) {
    $biggest = 0;
    foreach ($entry->payload as $value) {
        if (is_string($value)) {
            $biggest = max($biggest, strlen($value));
        }
    }
    printf(
        "%2d %-8s %-6s %-40s fields=%d biggest_value=%d\n",
        $index,
        $entry->type,
        $entry->method,
        substr($entry->uri, 0, 40),
        count($entry->payload),
        $biggest,
    );
}

$paginator = $container->get('knp_paginator');
$pagination = $paginator->paginate($entries, 1, 200);

$html = $container->get('twig')->render('request_response_log/index.html.twig', [
    'pagination' => $pagination,
    'filter' => $filter,
    'types' => RequestLogFilter::TYPES,
]);

printf("rendered html=%d bytes peak=%.1fMB\n", strlen($html), memory_get_peak_usage(true) / 1048576);
