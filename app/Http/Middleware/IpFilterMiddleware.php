<?php

namespace App\Http\Middleware;

use App\Exceptions\SecurityException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpFilterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP filtering is enabled
        if (!config('security.ip_filtering.enabled', false)) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $whitelist = $this->parseIpList(config('security.ip_filtering.whitelist', ''));
        $blacklist = $this->parseIpList(config('security.ip_filtering.blacklist', ''));

        // Check blacklist first
        if (!empty($blacklist) && $this->isIpInList($clientIp, $blacklist)) {
            throw new SecurityException(
                'Access denied: IP address is blacklisted',
                'ip_blacklisted',
                ['ip' => $clientIp]
            );
        }

        // Check whitelist if configured
        if (!empty($whitelist) && !$this->isIpInList($clientIp, $whitelist)) {
            throw new SecurityException(
                'Access denied: IP address not in whitelist',
                'ip_not_whitelisted',
                ['ip' => $clientIp]
            );
        }

        return $next($request);
    }

    /**
     * Parse comma-separated IP list.
     */
    private function parseIpList(string $ipList): array
    {
        if (empty($ipList)) {
            return [];
        }

        return array_map('trim', explode(',', $ipList));
    }

    /**
     * Check if IP is in the given list (supports CIDR notation).
     */
    private function isIpInList(string $ip, array $ipList): bool
    {
        foreach ($ipList as $allowedIp) {
            if ($this->ipMatches($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches the pattern (supports CIDR notation).
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }

        // CIDR notation
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                
                $ipLong = ip2long($ip);
                $subnetLong = ip2long($subnet);
                $maskLong = -1 << (32 - (int)$mask);
                
                return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
            }
        }

        return false;
    }
}