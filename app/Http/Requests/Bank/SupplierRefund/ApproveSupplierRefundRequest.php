<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Http\Requests\Bank\SupplierRefund;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * Approving one supplier refund, which takes nothing from the form.
 *
 * The document number arrives in the route rather than the body, so there is
 * nothing here to validate. The request exists so that the permission to approve
 * is stated in the same place as the permission to raise and to edit, rather than
 * being the one action left behind in the controller because it had no form.
 *
 * That matters more than the empty rules do. An approval is the action that posts
 * to the ledgers, so it is the last one that should be the one checked by hand.
 */
class ApproveSupplierRefundRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * Who may approve a supplier refund.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a plain
     * false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SUPPLIER_REFUNDS, FileAction::APPROVE);
    }

    /**
     * Nothing to check: the document number is in the route, not the form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
