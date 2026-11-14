<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

declare(strict_types=1);

namespace App\Doctor\Diagnostics;

use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Message;

/**
 * Whether PHP will accept the uploads the product form advertises.
 *
 * The product form accepts up to ten images of 8000 KB each. PHP rejects a file
 * larger than upload_max_filesize before Laravel ever sees it, and rejects the
 * whole request when it exceeds post_max_size. A stock PHP install is usually
 * configured well below both figures, which surfaces as "failed to upload" or a
 * 419 page instead of a validation message.
 *
 * The numbers here mirror the rules in the product form requests. They are not
 * imported from there because a diagnostic has to be able to run on a broken
 * install, but both places must agree.
 */
class UploadLimitsAllowUploads extends Diagnostic
{
    /**
     * The largest single image the product form accepts, in kilobytes.
     */
    private const int MAX_IMAGE_KB = 8000;

    /**
     * The most images the product form accepts in one submission.
     */
    private const int MAX_IMAGES = 10;

    /**
     * The human-readable name shown in the Doctor report.
     */
    public string $name = 'Upload limits allow product images';

    /**
     * The Doctor group this diagnostic belongs to.
     */
    public string $group = 'environment';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'configured' => 'PHP accepts the uploads the product form advertises.',

            'insufficient' => Message::make(
                summary: 'PHP upload limits (upload_max_filesize {upload_max_filesize}, post_max_size {post_max_size}) are below what the product form accepts.',
                remediation: 'Set upload_max_filesize = 8M and post_max_size = 100M in php.ini, then restart the web server (or PHP-FPM).',
            ),
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        $perImageBytes = self::MAX_IMAGE_KB * 1024;
        $totalBytes = $perImageBytes * self::MAX_IMAGES;

        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');

        $uploadBytes = $this->toBytes($uploadMaxFilesize);
        $postBytes = $this->toBytes($postMaxSize);

        // A value of zero means "no limit" for both directives.
        $uploadAcceptsOne = $uploadBytes === 0 || $uploadBytes >= $perImageBytes;
        $postAcceptsAll = $postBytes === 0 || $postBytes >= $totalBytes;

        if ($uploadAcceptsOne && $postAcceptsAll) {
            return $this->pass('configured');
        }

        return $this->warn('insufficient', [
            'upload_max_filesize' => $this->display($uploadMaxFilesize),
            'post_max_size' => $this->display($postMaxSize),
        ])->withDetails(sprintf(
            'A single image may be %d KB; one submission may carry %d images (about %s in total).',
            self::MAX_IMAGE_KB,
            self::MAX_IMAGES,
            $this->humanBytes($totalBytes),
        ));
    }

    /**
     * Convert a php.ini size value to bytes.
     *
     * @param  string|false  $value  The raw value from ini_get().
     * @return int The size in bytes, or zero when the directive has no limit.
     */
    private function toBytes(string|false $value): int
    {
        if ($value === false) {
            return 0;
        }

        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Present a raw ini value in a report-friendly form.
     *
     * @param  string|false  $value  The raw value from ini_get().
     */
    private function display(string|false $value): string
    {
        if ($value === false || trim($value) === '') {
            return 'unknown';
        }

        return $value === '0' ? 'unlimited' : $value;
    }

    /**
     * Present a byte count in megabytes.
     */
    private function humanBytes(int $bytes): string
    {
        return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1), '0'), '.').' MB';
    }
}
