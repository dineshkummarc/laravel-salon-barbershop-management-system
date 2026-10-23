<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use ImranDevBd\AiCatalog\Services\UrlReader;

/** Harden the package URL reader before using owner-supplied URLs. */
class SafeCatalogReader extends UrlReader
{
    public function fetch(string $url): string
    {
        $p = parse_url($url);
        $host = strtolower($p['host'] ?? '');
        $allowed = config('salon.import_hosts', []);
        if (($p['scheme'] ?? '') !== 'https' || isset($p['user']) || isset($p['pass']) || isset($p['port']) || ! in_array($host, $allowed, true) || filter_var($host, FILTER_VALIDATE_IP)) {
            $this->reject();
        }
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $ips = [];
        foreach ($records ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip) {
                $ips[] = $ip;
            }
        }
        if (! $ips) {
            $this->reject();
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) {
                $this->reject();
            }
        }
        $ip = $ips[0];
        $resolved = str_contains($ip, ':') ? '['.$ip.']' : $ip;
        $response = Http::timeout(12)->connectTimeout(5)->withOptions(['allow_redirects' => false, 'stream' => true, 'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$resolved]]])->withHeaders(['User-Agent' => 'GroomOS-Catalog/1.0', 'Accept' => 'text/html'])->get($url);
        if (! $response->successful() || ! str_contains(strtolower($response->header('Content-Type')), 'text/html')) {
            throw ValidationException::withMessages(['source' => 'Source must be a successful HTML page. Redirects are not followed.']);
        }
        $body = $response->toPsrResponse()->getBody();
        $html = $body->read(1000001);
        $body->close();
        if (strlen($html) > 1000000) {
            throw ValidationException::withMessages(['source' => 'Source exceeds 1 MB.']);
        }
        $html = preg_replace('/<(script|style|noscript)\b[^>]*>.*?<\/\1>/is', ' ', $html);

        return mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 0, 8000);
    }

    private function reject(): never
    {
        throw ValidationException::withMessages(['source' => 'Only explicitly allowlisted public HTTPS hosts are accepted. Configure SALON_IMPORT_HOSTS. Local addresses, credentials, custom ports, and redirects are blocked.']);
    }
}
