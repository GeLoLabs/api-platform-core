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

namespace ApiPlatform\Tests\Symfony\Security;

use ApiPlatform\Symfony\Security\ResourceAccessChecker;
use ApiPlatform\Tests\Fixtures\Serializable;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceAccessCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getGranted
     */
    public function testIsGranted(bool $granted): void
    {
        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn($granted)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->willImplement(Serializable::class);
        $token = $tokenProphecy->reveal();
        $tokenProphecy->getUser()->shouldBeCalled();

        $tokenProphecy->getRoleNames()->willReturn([])->shouldBeCalled();

        $tokenStorageProphecy->getToken()->willReturn($token);

        $checker = new ResourceAccessChecker($expressionLanguageProphecy->reveal(), $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $this->assertEquals($granted, $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")'));
    }

    public function getGranted(): array
    {
        return [[true], [false]];
    }

    public function testSecurityComponentNotAvailable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "symfony/security" library must be installed to use the "security" attribute.');

        $checker = new ResourceAccessChecker($this->prophesize(ExpressionLanguage::class)->reveal());
        $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")');
    }

    public function testExpressionLanguageNotInstalled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "symfony/expression-language" library must be installed to use the "security" attribute.');

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($this->prophesize(TokenInterface::class)->willImplement(Serializable::class)->reveal());

        $checker = new ResourceAccessChecker(null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")');
    }

    public function testNotBehindAFirewall(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The current token must be set to use the "security" attribute (is the URL behind a firewall?).');

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $checker = new ResourceAccessChecker(null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")');
    }

    public function testWithoutAuthenticationTokenAndExceptionOnNoTokenIsFalse(): void
    {
        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $tokenStorageProphecy->getToken()->willReturn(null);

        $checker = new ResourceAccessChecker($expressionLanguageProphecy->reveal(), $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal(), $authorizationCheckerProphecy->reveal(), false);
        self::assertTrue($checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")'));
    }
}
