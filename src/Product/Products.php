<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Product;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * The paid extensions installed here, by product id.
 */
class Products
{
    /**
     * @var array<string, LicensedProductInterface>
     */
    private array $products = [];

    /**
     * @param iterable<LicensedProductInterface> $products
     */
    public function __construct(
        #[AutowireIterator(LicensedProductInterface::TAG)]
        iterable $products,
    ) {
        foreach ($products as $product) {
            $this->products[$product->productKey()] = $product;
        }

        ksort($this->products);
    }

    public function get(string $productKey): LicensedProductInterface|null
    {
        return $this->products[$productKey] ?? null;
    }

    /**
     * @return array<string, LicensedProductInterface>
     */
    public function all(): array
    {
        return $this->products;
    }
}
