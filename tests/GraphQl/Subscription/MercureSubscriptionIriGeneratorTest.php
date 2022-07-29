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

namespace ApiPlatform\Tests\GraphQl\Subscription;

use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Routing\RequestContext;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class MercureSubscriptionIriGeneratorTest extends TestCase
{
    private RequestContext $requestContext;
    private Hub $defaultHub;
    private Hub $managedHub;
    private HubRegistry $registry;
    private MercureSubscriptionIriGenerator $mercureSubscriptionIriGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->defaultHub = new Hub('https://demo.mercure.rocks/hub', new StaticTokenProvider('xx'));
        $this->managedHub = new Hub('https://demo.mercure.rocks/managed', new StaticTokenProvider('xx'));

        $this->registry = new HubRegistry($this->defaultHub, ['default' => $this->defaultHub, 'managed' => $this->managedHub]);

        $this->requestContext = new RequestContext('', 'GET', 'example.com');
        $this->mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator($this->requestContext, $this->registry);
    }

    public function testGenerateTopicIriWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', 'example.com'), 'https://example.com/.well-known/mercure');

        $this->assertEquals('http://example.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateDefaultTopicIriWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), 'https://example.com/.well-known/mercure');

        $this->assertEquals('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateMercureUrlWithLegacySignature(): void
    {
        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', 'example.com'), 'https://example.com/.well-known/mercure');

        $this->assertEquals('https://example.com/.well-known/mercure?topic=http://example.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }

    public function testGenerateTopicIri(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertEquals('http://example.com/subscriptions/subscription-id', $this->mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateDefaultTopicIri(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $mercureSubscriptionIriGenerator = new MercureSubscriptionIriGenerator(new RequestContext('', 'GET', '', ''), $this->registry);

        $this->assertEquals('https://api-platform.com/subscriptions/subscription-id', $mercureSubscriptionIriGenerator->generateTopicIri('subscription-id'));
    }

    public function testGenerateMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertEquals("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id'));
    }

    public function testGenerateExplicitDefaultMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertEquals("{$this->defaultHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'default'));
    }

    public function testGenerateNonDefaultMercureUrl(): void
    {
        if (!class_exists(Hub::class)) {
            $this->markTestSkipped();
        }

        $this->assertEquals("{$this->managedHub->getUrl()}?topic=http://example.com/subscriptions/subscription-id", $this->mercureSubscriptionIriGenerator->generateMercureUrl('subscription-id', 'managed'));
    }
}
