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
 * The fields a delivery order is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 */
class StoreDeliveryOrderRequest extends DeliveryOrderRequest
{
    /**
     * Who may raise a delivery order.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_DELIVERY_ORDERS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sales_order_id' => ['required', 'uuid', 'exists:sales_orders,id'],
            'company_address_id' => ['nullable', 'uuid', 'exists:company_addresses,id'],
            'stock_location_id' => ['required', 'uuid', 'exists:stock_locations,id'],
            'delivery_date' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'courier_company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_date' => ['nullable', 'date'],
            'shipping_status' => ['required', Rule::enum(ShippingStatus::class)],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array'],
            'lines.*.sales_order_line_id' => ['required', 'uuid', 'exists:sales_order_lines,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
