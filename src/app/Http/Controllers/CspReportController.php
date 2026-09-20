<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use JsonException;

class CspReportController extends Controller
{
    public function store(Request $request): Response
    {
        $rawPayload = $request->getContent();
        $maxBytes = max(1024, (int) config('security.csp_report_only.max_report_bytes', 65536));

        if (strlen($rawPayload) > $maxBytes) {
            return response('', 413);
        }

        try {
            $decoded = json_decode($rawPayload, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response('', 400);
        }

        if (! is_array($decoded)) {
            return response('', 400);
        }

        $reports = array_is_list($decoded) ? $decoded : [$decoded];
        $maxBatchSize = max(1, min(100, (int) config('security.csp_report_only.max_batch_size', 20)));

        foreach (array_slice($reports, 0, $maxBatchSize) as $report) {
            $context = $this->normalizeReport($report);

            if ($context !== null) {
                Log::warning('Content Security Policy violation reported.', $context);
            }
        }

        return response()->noContent();
    }

    private function normalizeReport(mixed $report): ?array
    {
        if (! is_array($report)) {
            return null;
        }

        $body = $report['csp-report'] ?? $report['body'] ?? null;

        if (! is_array($body)) {
            return null;
        }

        $documentUri = $this->firstString($body, ['document-uri', 'documentURL'])
            ?? $this->firstString($report, ['url']);
        $effectiveDirective = $this->firstString($body, ['effective-directive', 'effectiveDirective']);
        $blockedUri = $this->firstString($body, ['blocked-uri', 'blockedURL']);

        if ($documentUri === null && $effectiveDirective === null && $blockedUri === null) {
            return null;
        }

        return array_filter([
            'document_uri' => $this->sanitizeUri($documentUri),
            'blocked_uri' => $this->sanitizeUri($blockedUri),
            'effective_directive' => $this->sanitizeText($effectiveDirective),
            'violated_directive' => $this->sanitizeText(
                $this->firstString($body, ['violated-directive', 'violatedDirective'])
            ),
            'disposition' => $this->sanitizeText($this->firstString($body, ['disposition'])),
            'source_file' => $this->sanitizeUri($this->firstString($body, ['source-file', 'sourceFile'])),
            'status_code' => $this->firstInteger($body, ['status-code', 'statusCode']),
            'line_number' => $this->firstInteger($body, ['line-number', 'lineNumber']),
            'column_number' => $this->firstInteger($body, ['column-number', 'columnNumber']),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function sanitizeUri(?string $value): ?string
    {
        $value = $this->sanitizeText($value, 500);

        if ($value === null) {
            return null;
        }

        $parts = parse_url($value);

        if (
            is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';
            $path = (string) ($parts['path'] ?? '');

            return mb_substr(strtolower($parts['scheme']).'://'.$parts['host'].$port.$path, 0, 500);
        }

        return mb_substr(strtok($value, '?#') ?: $value, 0, 500);
    }

    private function sanitizeText(?string $value, int $limit = 200): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($value)) ?? '';

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                return $payload[$key];
            }
        }

        return null;
    }

    private function firstInteger(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (int) $payload[$key];
            }
        }

        return null;
    }
}
