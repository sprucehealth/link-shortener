<?php
// open database connection
// this link will be used for the rest of the page. this is implemented via a function that can be called to establish the db as needed
function get_my_db() {
	static $db;

	if (!$db) {
		require "../../private/dblogin.inc";
	}

	return $db;
}
$db = get_my_db();

// set database connection character set to UTF-8
$db->set_charset("utf8");

// use this helper class to prep a string for db insertion with quotes and escaping
// in query, instead of saying "field=$var", say "field=$quote->$var"
class quotefordb {
	function __get($value)
	{
		$db = get_my_db();
		return "'".$db->real_escape_string($value)."'";
	}
}

// create a quote helper var for use later on page that includes this document
$quote = new quotefordb;

// create a variable to hold debug information to be output later
$debugvar = "";

// function that accepts a URL query string (e.g., "?foo=bar&cat=dog")
// and returns an array of its various parameters
// note: this function is useful because the native php $_GET array and parse_str() function both handle potential repeat parameters suboptimally (by overwriting earlier values)
function proper_parse_str($str) {
	# result array
	$arr = array();

	# split on outer delimiter
	$pairs = explode('&', $str);

	# loop through each pair
	foreach ($pairs as $i) {
		# split into name and value
		# note: these will still be URL-encoded
		list($name, $value) = explode('=', $i, 2);

		# store the value in an array, using name as the key
		if (strlen($name) > 0) {
			$arr[$name][] = $value;
		}

		/* old way of doing the value storage (single values stored as scalars instead of everything being stored as arrays)
		# if name already exists
		if (isset($arr[$name])) {
			# stick multiple values into an array
			# if array already exists for this parameter, simply append the new value
			if (is_array($arr[$name])) {
				$arr[$name][] = $value;
			}
			# else, create an array for this parameter and replace the existing scalar entry with it
			else {
				$arr[$name] = array($arr[$name], $value);
			}
		}
		# otherwise, simply stick it in a scalar
		else {
			$arr[$name] = $value;
		}
		*/
	}

	# return result array
 	return $arr;
}

/*
 * Spruce attribution reporting for l.sprucehealth.com.
 *
 * Wired into the click-resolution flow so each click on a pre-blessed link
 * lands a row in the Spruce `attributions` Looker model.
 *
 * Spec: https://gist.github.com/sibljon/bfd93706bd215806458f89686527bb2a
 */

const SPRUCE_ATTRIBUTION_ENDPOINT       = 'https://msg-api.sprucehealth.com/graphql';
const SPRUCE_ATTRIBUTION_USER_AGENT     = 'l-sprucehealth-com/1.0';
const SPRUCE_ATTRIBUTION_TIMEOUT_SECONDS = 5;

const SPRUCE_ATTRIBUTION_QUERY = <<<'GQL'
mutation AssociateAttribution($input: AssociateAttributionInput!) {
  associateAttribution(input: $input) {
    success
    errorCode
    errorMessage
  }
}
GQL;

/*
 * POST associateAttribution to msg-api, then relay any Set-Cookie response
 * headers back to the user (so the backend's `did` cookie minting works).
 *
 * Failures are logged and swallowed. Never throws. Never lets an attribution
 * problem affect the user-facing redirect.
 *
 * Call this BEFORE you call `header('Location: ...')` if the user has no
 * `did` cookie inbound (so we can relay the freshly-minted Set-Cookie).
 * Call it AFTER `fastcgi_finish_request()` if the user already has `did`
 * (fire-and-forget, no Set-Cookie needed).
 *
 * @param string $requestUrl   Full URL the user hit, e.g. "https://l.sprucehealth.com/x123?utm_source=newsletter".
 * @param string $cookieHeader Raw inbound Cookie header ($_SERVER['HTTP_COOKIE'] ?? '').
 */
function spruceReportAttribution(string $requestUrl, string $cookieHeader): void {
    try {
        $parsed = parse_url($requestUrl);
        if (!is_array($parsed) || empty($parsed['host'])) {
            error_log('spruceReportAttribution: unparseable requestUrl');
            return;
        }
        $hostname  = $parsed['host'];
        $pathname  = $parsed['path'] ?? '/';
        $scheme    = $parsed['scheme'] ?? 'https';
        $urlValue  = $scheme . '://' . $hostname . $pathname;
        $rawQuery  = $parsed['query'] ?? '';

        // Build values: synthetic `url` first, then one entry per inbound query
        // param. Split the raw query string ourselves rather than using
        // parse_str(), which collapses repeated keys (the spec requires us to
        // emit one entry per occurrence).
        $values = [['key' => 'url', 'value' => $urlValue]];
        if ($rawQuery !== '') {
            foreach (explode('&', $rawQuery) as $pair) {
                if ($pair === '') {
                    continue;
                }
                $eq = strpos($pair, '=');
                if ($eq === false) {
                    $values[] = ['key' => urldecode($pair), 'value' => ''];
                } else {
                    $values[] = [
                        'key'   => urldecode(substr($pair, 0, $eq)),
                        'value' => urldecode(substr($pair, $eq + 1)),
                    ];
                }
            }
        }

        // Pass the user's real IP through as `ip_address`. Without this, the
        // backend would record l.sprucehealth.com's NAT egress IP (msg-api sees
        // us as the immediate client, not the user). The associateAttribution
        // resolver skips its own server-side `ip_address` append when the caller
        // already supplied one, so there's no duplicate row in the attributions
        // table. See sprucehealth/backend#15769 for the resolver behavior.
        $visitorIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $values[] = ['key' => 'ip_address', 'value' => $visitorIp];

        // JSON_INVALID_UTF8_SUBSTITUTE replaces invalid UTF-8 byte sequences
        // (e.g. a query value of "%FF" decoded to raw byte 0xFF) with U+FFFD
        // rather than failing the whole encode and dropping the row.
        $body = json_encode([
            'operationName' => 'associateAttribution',
            'query'         => SPRUCE_ATTRIBUTION_QUERY,
            'variables'     => [
                'input' => [
                    'values'        => $values,
                    'origin'        => $hostname,
                    'originDetails' => $pathname,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($body === false) {
            error_log('spruceReportAttribution: json_encode failed: ' . json_last_error_msg());
            return;
        }

		// Get the visitor's real user agent
        $visitorUA = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $headers = [
            'Content-Type: application/json',
            // Identifying our script but appending the real user agent of the visitor
            // 'User-Agent: ' . SPRUCE_ATTRIBUTION_USER_AGENT . ' (Relay; ' . $visitorUA . ')',
            // Actually let's just record only the real user agent of the visitor
            'User-Agent: ' . $visitorUA,
        ];

        if ($cookieHeader !== '') {
            $headers[] = 'Cookie: ' . $cookieHeader;
        }

        $ch = curl_init(SPRUCE_ATTRIBUTION_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,  // include response headers in output so we can read Set-Cookie
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => SPRUCE_ATTRIBUTION_TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => SPRUCE_ATTRIBUTION_TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response   = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlErr    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log(sprintf(
                'spruceReportAttribution: transport error origin=%s details=%s err=%s',
                $hostname, $pathname, $curlErr
            ));
            return;
        }

        $rawHeaders = (string) substr($response, 0, $headerSize);
        $rawBody    = (string) substr($response, $headerSize);

        if ($httpStatus < 200 || $httpStatus >= 300) {
            error_log(sprintf(
                'spruceReportAttribution: http %d origin=%s details=%s body=%s',
                $httpStatus, $hostname, $pathname, substr($rawBody, 0, 500)
            ));
            // Don't relay Set-Cookie on a non-2xx response — those headers may
            // be from an error path we don't trust.
            return;
        }

        // Relay Set-Cookie response headers back to the user, but only if
        // we haven't already sent the user-facing response. In fire-and-forget
        // mode (after fastcgi_finish_request), headers_sent() will be true and
        // these calls are silently no-ops.
        if (!headers_sent()) {
            foreach (preg_split('/\r?\n/', $rawHeaders) as $line) {
                if (stripos($line, 'set-cookie:') === 0) {
                    header($line, false);
                }
            }
        }

        // Log GraphQL-layer failures (HTTP 200 with errors[] or success=false).
        $json = json_decode($rawBody, true);
        if (is_array($json)) {
            if (!empty($json['errors'])) {
                error_log(sprintf(
                    'spruceReportAttribution: graphql errors origin=%s details=%s errors=%s',
                    $hostname, $pathname, json_encode($json['errors'], JSON_UNESCAPED_SLASHES)
                ));
                return;
            }
            $payload = $json['data']['associateAttribution'] ?? null;
            if (is_array($payload) && ($payload['success'] ?? null) !== true) {
                error_log(sprintf(
                    'spruceReportAttribution: success=false origin=%s details=%s code=%s message=%s',
                    $hostname, $pathname,
                    $payload['errorCode']    ?? '',
                    $payload['errorMessage'] ?? ''
                ));
            }
        }
    } catch (\Throwable $t) {
        // Never let attribution problems affect the redirect.
        error_log('spruceReportAttribution: exception ' . $t->getMessage());
    }
}
?>
