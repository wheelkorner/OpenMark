<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Vendor;

use BitBag\OpenMarketplace\Component\Vendor\Repository\TaxonRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonContext implements TaxonContextInterface
{
    private TaxonRepositoryInterface $taxonRepository;

    public function __construct(TaxonRepositoryInterface $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
    }

    public function getForVendorPage(?string $slug, string $locale): ?TaxonInterface
    {
        return $this->taxonRepository->findForVendorPage($slug, $locale);
    }
}
