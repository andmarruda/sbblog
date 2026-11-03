<?php

namespace App\Support;

/**
 * Guards outgoing requests to user-provided URLs (webhooks) against SSRF: the host must
 * resolve only to public addresses. Checked when saving and again when sending, since DNS
 * can change in between.
 */
class SafeUrl
{
    public static function isPublic(string $url): bool
    {
        if (config('sbblog.publishing.allow_private_webhooks')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(
            gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
