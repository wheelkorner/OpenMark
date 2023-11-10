<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace spec\BitBag\OpenMarketplace\Component\Vendor\Generator;

use BitBag\OpenMarketplace\Component\Vendor\Generator\SlugGenerator;
use BitBag\OpenMarketplace\Component\Vendor\Generator\SlugGeneratorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Repository\VendorRepositoryInterface;
use PhpSpec\ObjectBehavior;

final class SlugGeneratorSpec extends ObjectBehavior
{
    public function let(
        VendorRepositoryInterface $vendorRepository
    ) {
        $this->beConstructedWith(
            $vendorRepository
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SlugGenerator::class);
    }

    public function it_should_implement_interface(): void
    {
        $this->shouldImplement(SlugGeneratorInterface::class);
    }

    public function it_generates_slug(
        VendorRepositoryInterface $vendorRepository
    ): void {
        $vendorRepository->findOneBy(['slug' => 'test-company'])
            ->willReturn(null);

        $this->generateSlug('test company')
            ->shouldReturn('test-company');

        $this->generateSlug('test company');
    }
}
