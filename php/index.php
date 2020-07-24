<?php

declare(strict_types=1);

use Bref\Context\Context;
use Aws\XRay\XRayClient;
use Gazzlehttp\Client;

require __DIR__ . '/vendor/autoload.php';

function ddHex2Int($hex, $truncate63bits)
{
    $int = '0';

    if ($truncate63bits) {
        if (strlen($hex) === 16) {
            $fc = substr($hex, 0, 1);
            $hex = (0x7 & hexdec($fc)) . substr($hex, 1);
        }
    }

    $len = strlen($hex);
    for ($i = 1; $i <= $len; $i++) {
        $int = bcadd($int, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
    }

    return $int;
}

return function ($event, Context $context) {

    $headers = array();
    $trace = explode(';', $context->getTraceId());

    if (array_key_exists('x-datadog-trace-id', $event['headers'])) {
        $xraySegment = [
            'name' => 'datadog-metadata',
            'type' => 'subsegment',
            'trace_id' => explode('=', $trace[0])[1],
            'parent_id' => explode('=', $trace[1])[1],
            'id' => strtolower(sprintf('%016X', mt_rand(0, 9223372036854775807))),
            'start_time' => time(),
            'end_time' => time(),
            'metadata' => [
                'datadog' => [
                    'trace' => [
                        'trace-id' => $event['headers']['x-datadog-trace-id'],
                        'parent-id' => $event['headers']['x-datadog-parent-id'],
                        'sampling-priority' => $event['headers']['x-datadog-sampling-priority']
                    ]
                ]
            ]
        ];

        $client = new Aws\XRay\XRayClient(['version' => 'latest', 'region' => $_ENV['AWS_REGION']]);
        $result = $client->putTraceSegments(['TraceSegmentDocuments' => [json_encode($xraySegment)]]);

        $headers = [
            'x-datadog-trace-id' => $event['headers']['x-datadog-trace-id'],
            'x-datadog-parent-id' => ddHex2Int(explode('=', $trace[1])[1], false),
            'x-sampling-priority' => $event['headers']['x-datadog-sampling-priority']
        ];
    } else {
        $headers = [
            'x-datadog-trace-id' => ddHex2Int(substr(explode('-', explode('=', $trace[0])[1])[2], -16), true),
            'x-datadog-parent-id' => ddHex2Int(explode('=', $trace[1])[1], false),
            'x-sampling-priority' => 2
        ];
    }

    $logEntry = [
        'dd' => [
            'trace_id' => $headers['x-datadog-trace-id']
        ],
        'msg' => "Hello from our PHP Lambda custom runtime!"
    ];

    print(json_encode($logEntry));

    $client = new GuzzleHttp\Client();
    $res = $client->request('GET', 'http://lab.azure.pipsquack.ca:8081/tier3', [
        'headers' => $headers
    ]);

    $res = $client->request('GET', 'http://home.pipsquack.ca:10095/hello', [
        'headers' => $headers
    ]);

    return [
        'statusCode' => $res->getStatusCode(),
        'body' => (string) $res->getBody()
    ];
};
