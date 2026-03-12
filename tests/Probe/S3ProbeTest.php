<?php

declare(strict_types=1);

use Mathiasgrimm\Netwatch\Probe\S3Probe;

test('name returns s3 uri', function () {
    $probe = new S3Probe(bucket: 'my-bucket', region: 'us-east-1', key: 'k', secret: 's');
    expect($probe->name())->toBe('s3://my-bucket');
});

test('probe with invalid credentials still connects', function () {
    $probe = new S3Probe(
        bucket: 'nonexistent-bucket-xyz-12345',
        region: 'us-east-1',
        key: 'AKIAIOSFODNN7EXAMPLE',
        secret: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        timeout: 5.0,
    );

    $result = $probe->probe();

    expect($result->totalMs)->toBeGreaterThan(0)
        ->and($result->connectMs)->toBeGreaterThan(0);
});

test('probe fails on unreachable endpoint', function () {
    $probe = new S3Probe(
        bucket: 'test',
        region: 'us-east-1',
        key: 'key',
        secret: 'secret',
        endpoint: 'http://192.0.2.1:9999',
        timeout: 0.5,
    );

    $result = $probe->probe();

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('curl error');
});
