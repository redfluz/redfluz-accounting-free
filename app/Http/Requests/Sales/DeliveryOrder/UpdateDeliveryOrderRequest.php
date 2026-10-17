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

namespace App\Http\Requests\Sales\DeliveryOrder;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\ShippingStatus;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a delivery order is edited from.
 *
 * A posted delivery order may only change its shipping details; a draft may change
 * everything including the order lines. The rules merge both sets so that a single
 * request covers either state, and the controller decides which fields to act on
 * once it knows the status. A rule inherited and then conditionally removed is a
 * rule nobody can read in one place.
 */
class UpdateDeliveryOrderRequest extends DeliveryOrderRequest
{
    /**
     * Who may edit a delivery order.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'sales_order_id' => ['nullable', 'uuid', 'exists:sales_orders,id'],
            'company_address_id' => ['nullable', 'uuid', 'exists:company_addresses,id'],
            'stock_location_id' => ['nullable', 'uuid', 'exists:stock_locations,id'],
            'delivery_date' => ['nullable', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'courier_company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_date' => ['nullable', 'date'],
            'shipping_status' => ['required', Rule::enum(ShippingStatus::class)],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.sales_order_line_id' => ['nullable', 'uuid', 'exists:sales_order_lines,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
