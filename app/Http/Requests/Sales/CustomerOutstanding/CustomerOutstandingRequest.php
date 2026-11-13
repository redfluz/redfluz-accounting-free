<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales\CustomerOutstanding;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

abstract class CustomerOutstandingRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
