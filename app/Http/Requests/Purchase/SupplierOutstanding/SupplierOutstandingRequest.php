<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase\SupplierOutstanding;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

abstract class SupplierOutstandingRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
