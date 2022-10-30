<?php
declare(strict_types=1);

namespace Coveragify;

use Carbon\Carbon;

class Coveragifyable extends \php_user_filter
{
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        $baesdBucket = stream_bucket_new(
            fopen('php://memory', 'r'),
            ''
        );

        while ($bucket = stream_bucket_make_writeable($in)) {
            $baesdBucket->data .= $bucket->data;
        }

        $baesdBucket->data = $this->transform($baesdBucket->data);
        $consumed = $baesdBucket->datalen = strlen($baesdBucket->data);

        stream_bucket_append($out, $baesdBucket);
        return \PSFS_PASS_ON;
    }

    public function transform(string $code): string
    {
        $code = Coveragify::inspect($code)
            ->getCoveragifyableCode();
        if ($code === '') {
            return '';
        }
        return "<?php\n\n" . $code;
    }
}