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

return [
    'title' => 'User Management',
    'subtitle' => 'Manage system administrators and member permissions.',
    'profile' => 'Profile',
    'interface_theme' => 'Interface theme',
    'interface_theme_hint' => 'Choose how the application looks for your account. Your selection takes effect after saving.',
    'themes' => [
        'redfluz' => 'Redfluz',
        'cerulean' => 'Cerulean',
        'slate' => 'Slate',
        'ember' => 'Ember',
        'verdant' => 'Verdant',
        'pinkpanther' => 'Pink Panther (default)',
        'ruby' => 'Ruby Red',
        'paper' => 'Paper Admin',
        'dark' => 'Dark',
    ],
    'read_only_email' => 'mail address cannot be changed.',
    'change_password' => 'Change Password',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm Password',
    'default_password_notice' => 'Default password: :password. The user must replace it after signing in, then sign in again.',
    'password_change_required' => 'You must choose a password different from the temporary password before continuing. You will then be signed out.',
    'password_changed_relogin' => 'Password changed successfully. Sign in again using your new password.',
    'employee' => [
        'create_profile' => 'Create employee profile',
        'create_profile_hint' => 'Enable HR features for this user, including salary, claims, leave and employee payments.',
        'profile_linked' => 'Employee profile :code is linked. It cannot be detached here because it may contain HR and payment history.',
    ],
    'table' => [
        'column_role' => 'Role',
        'column_department' => 'Department',
        'column_organization' => 'Organisation Position',
        'column_tenant' => 'Tenant',
        'column_name' => 'Full Name',
        'column_email' => 'Email Address',
        'column_password' => 'Password',
        'column_locale' => 'Locale',
        'column_picture' => 'Picture',
        'column_email_verified_at' => 'Email Verified',
        'column_remember_token' => 'Remember Token',
    ],
    'message' => [
        'profile_updated' => 'Profile updated successfully.',
    ],
];
