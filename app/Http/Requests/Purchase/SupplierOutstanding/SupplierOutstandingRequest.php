<?php

declare(strict_types=1);

namespace app\Http\Requests\Purchase\SupplierOutstanding;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

abstract class SupplierOutstandingRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
