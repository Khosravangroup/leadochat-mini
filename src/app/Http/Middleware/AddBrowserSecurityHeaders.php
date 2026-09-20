<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBrowserSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers_enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        if (config('security.csp_report_only.enabled', true)) {
            $this->addReportOnlyContentSecurityPolicy($response);
        }

        if ($request->isSecure() && config('security.hsts.enabled', true)) {
            $maxAge = max(0, (int) config('security.hsts.max_age', 86400));
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}");
        }

        return $response;
    }

    private function addReportOnlyContentSecurityPolicy(Response $response): void
    {
        $directives = config('security.csp_report_only.directives', []);
        $policy = [];

        foreach ($directives as $directive => $sources) {
            if (! is_string($directive) || ! is_array($sources)) {
                continue;
            }

            $policy[] = trim($directive.' '.implode(' ', $sources));
        }

        $reportPath = (string) config('security.csp_report_only.report_path', '/csp-reports');
        $policy[] = 'report-uri '.$reportPath;
        $policy[] = 'report-to csp-endpoint';

        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', $policy));

        $appUrl = rtrim((string) config('app.url'), '/');
        $reportUrl = $appUrl.$reportPath;

        if (filter_var($reportUrl, FILTER_VALIDATE_URL)) {
            $response->headers->set('Reporting-Endpoints', 'csp-endpoint="'.$reportUrl.'"');
        }
    }
}
