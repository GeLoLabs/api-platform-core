<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Elasticsearch\Metadata\Document;

use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use PHPUnit\Framework\TestCase;

class DocumentMetadataTest extends TestCase
{
    public function testValueObject(): void
    {
        $documentMetadataOne = new DocumentMetadata('foo', 'bar');

        self::assertEquals('foo', $documentMetadataOne->getIndex());
        self::assertEquals('bar', $documentMetadataOne->getType());

        $documentMetadataTwo = $documentMetadataOne->withIndex('baz');

        self::assertNotSame($documentMetadataTwo, $documentMetadataOne);
        self::assertEquals('baz', $documentMetadataTwo->getIndex());
        self::assertEquals('bar', $documentMetadataTwo->getType());

        $documentMetadataThree = $documentMetadataTwo->withType(DocumentMetadata::DEFAULT_TYPE);

        self::assertNotSame($documentMetadataThree, $documentMetadataOne);
        self::assertNotSame($documentMetadataThree, $documentMetadataTwo);
        self::assertEquals('baz', $documentMetadataThree->getIndex());
        self::assertEquals(DocumentMetadata::DEFAULT_TYPE, $documentMetadataThree->getType());
    }
}
