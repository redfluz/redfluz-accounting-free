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

namespace app\Enums;

/**
 * How the balance on an order was discussed, shared by the sales and purchase
 * follow-up threads: the same conversation shapes on both sides of the deal.
 */
enum FollowUpType: string
{
    case CALL = 'call';
    case WHATSAPP = 'whatsapp';
    case SMS = 'sms';
    case EMAIL = 'email';
    case MEETING = 'meeting';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CALL => __('common.follow_up_type.call'),
            self::WHATSAPP => __('common.follow_up_type.whatsapp'),
            self::SMS => __('common.follow_up_type.sms'),
            self::EMAIL => __('common.follow_up_type.email'),
            self::MEETING => __('common.follow_up_type.meeting'),
            self::OTHER => __('common.follow_up_type.other'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CALL => 'phone',
            self::WHATSAPP => 'message-circle',
            self::SMS => 'messages-square',
            self::EMAIL => 'mail',
            self::MEETING => 'users',
            self::OTHER => 'ellipsis',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CALL => 'theme-primary',
            self::WHATSAPP => 'theme-success',
            self::SMS => 'theme-accent',
            self::EMAIL => 'theme-secondary',
            self::MEETING => 'theme-warning',
            self::OTHER => 'theme-secondary',
        };
    }
}
