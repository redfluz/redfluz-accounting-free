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

namespace App\Enums;

/**
 * What a given upload control is allowed to accept.
 *
 * The application only ever accepts documents and images. It never accepts
 * archives or executables, because an uploaded file is stored verbatim and a zip
 * or a binary defeats the point of an allowlist: it can carry anything at all,
 * and the stored name would then disagree with the name that was validated.
 *
 * Naming the accepted set here rather than repeating an extension list in every
 * controller keeps the validation rule and the file picker's accept attribute
 * derived from one source, so they cannot drift apart.
 */
enum UploadType: string
{
    /** Image uploads only: company logos, product images, profile pictures. */
    case IMAGES = 'images';

    /** Document uploads only: purchase orders, invoices, journal attachments. */
    case DOCUMENTS = 'documents';

    /** Documents and images: company files, goods receipt attachments. */
    case DOCUMENT_AND_IMAGES = 'document_and_images';

    /**
     * The image extensions. jpg and jpeg are both listed because the two spellings
     * are used interchangeably and neither is a prefix of the other.
     *
     * @var list<string>
     */
    private const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * The document extensions. Office and PDF formats only.
     *
     * @var list<string>
     */
    private const array DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * The extensions this upload type accepts.
     *
     * @return list<string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::IMAGES => self::IMAGE_EXTENSIONS,
            self::DOCUMENTS => self::DOCUMENT_EXTENSIONS,
            self::DOCUMENT_AND_IMAGES => [...self::DOCUMENT_EXTENSIONS, ...self::IMAGE_EXTENSIONS],
        };
    }

    /**
     * Whether this type accepts image files.
     */
    public function acceptsImages(): bool
    {
        return $this !== self::DOCUMENTS;
    }

    /**
     * Whether this type accepts document files.
     */
    public function acceptsDocuments(): bool
    {
        return $this !== self::IMAGES;
    }

    /**
     * The validation rule for this type.
     *
     * extensions: inspects the uploaded file name. The mimes: rule it replaced
     * ignored the name and guessed the type from the content instead, so an
     * executable whose bytes happened to read as text was accepted and stored.
     */
    public function rule(): string
    {
        return 'extensions:'.implode(',', $this->extensions());
    }

    /**
     * The accept attribute for a file input of this type.
     *
     * Derived from the same list as the rule so the picker never advertises a
     * format the server will refuse.
     */
    public function accept(): string
    {
        return implode(',', array_map(
            static fn (string $extension): string => '.'.$extension,
            $this->extensions()
        ));
    }
}
