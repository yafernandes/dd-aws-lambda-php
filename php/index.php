<?php

declare(strict_types=1);

use Bref\Context\Context;
use Aws\XRay\XRayClient;
use Gazzlehttp\Client;

require __DIR__ . '/vendor/autoload.php';

function ddHex2Int($hex, $truncate63bits) {
    $int = '0';

    if ($truncate63bits) {
        if (strlen($hex) === 16) {
            $fc = substr($hex, 0, 1);
            $hex = (0x7 & hexdec($fc)).substr($hex, 1);
        }
    }

    $len = strlen($hex);
    for ($i = 1; $i <= $len; $i++) {
       $int = bcadd($int, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
    }

    return $int;
}

return function ($event, Context $context) {

    $DD_HEADERS = array();
    $trace = explode(';', $context->getTraceId());

    if (array_key_exists('x-datadog-trace-id', $event['headers'])) {
        $XRAY_SEGMENT['name'] = 'datadog-metadata';
        $XRAY_SEGMENT['type'] = 'subsegment';
        $XRAY_SEGMENT['trace_id'] = explode('=', $trace[0])[1];
        $XRAY_SEGMENT['parent_id'] = explode('=', $trace[1])[1];
        $XRAY_SEGMENT['id'] = strtolower(sprintf('%016X', mt_rand(0, 9223372036854775807)));
        $XRAY_SEGMENT['start_time'] = time();
        $XRAY_SEGMENT['end_time'] = time();
        $XRAY_SEGMENT['metadata']['datadog']['trace']['trace-id'] = $event['headers']['x-datadog-trace-id'];
        $XRAY_SEGMENT['metadata']['datadog']['trace']['parent-id'] = $event['headers']['x-datadog-parent-id'];
        $XRAY_SEGMENT['metadata']['datadog']['trace']['sampling-priority'] = $event['headers']['x-datadog-sampling-priority'];

        $client = new Aws\XRay\XRayClient(['version' => 'latest', 'region' => $_ENV['AWS_REGION']]);
        $result = $client->putTraceSegments(['TraceSegmentDocuments' => [json_encode($XRAY_SEGMENT)]]);

        $DD_HEADERS['x-datadog-trace-id'] = $event['headers']['x-datadog-trace-id'];
        $DD_HEADERS['x-datadog-parent-id'] = ddHex2Int(explode('=', $trace[1])[1], false);
        $DD_HEADERS['x-sampling-priority'] = $event['headers']['x-datadog-sampling-priority'];
    } else {
        $DD_HEADERS['x-datadog-trace-id'] = ddHex2Int(substr(explode('-', explode('=', $trace[0])[1])[2], -16), true);
        $DD_HEADERS['x-datadog-parent-id'] = ddHex2Int(explode('=', $trace[1])[1], false);
        $DD_HEADERS['x-sampling-priority'] = 2;
    }

    $client = new GuzzleHttp\Client();
    $res = $client->request('GET', 'http://lab.azure.pipsquack.ca:8081/tier3', [
        'headers' => $DD_HEADERS
    ]);

    $RESPONSE['statusCode'] = $res->getStatusCode();
    $RESPONSE['body'] = (string) $res->getBody();

    return $RESPONSE;
};
