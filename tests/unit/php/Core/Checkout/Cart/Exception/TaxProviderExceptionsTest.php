<?php declare(strict_types=1);

namespace unit\php\Core\Checkout\Cart\Exception;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\TaxProviderExceptions;
use Shopware\Core\Framework\ShopwareHttpException;

/**
 * @package checkout
 *
 * @internal
 *
 * @covers \Shopware\Core\Checkout\Cart\Exception\TaxProviderExceptions
 */
class TaxProviderExceptionsTest extends TestCase
{
    public function testException(): void
    {
        $e = new TaxProviderExceptions();

        static::assertInstanceOf(ShopwareHttpException::class, $e);
        static::assertSame('CHECKOUT__TAX_PROVIDER_EXCEPTION', $e->getErrorCode());
        static::assertSame('There was an error while calculating taxes', $e->getMessage());
        static::assertFalse($e->hasExceptions());
        static::assertEmpty($e->getErrorsForTaxProvider('foo'));
    }

    public function testAddException(): void
    {
        $e = new TaxProviderExceptions();

        $e->add('tax_provider', new \Exception('bar'));

        static::assertInstanceOf(ShopwareHttpException::class, $e);
        static::assertTrue($e->hasExceptions());
        static::assertSame('There were 1 errors while fetching taxes from providers: ' . \PHP_EOL . 'Tax provider \'tax_provider\' threw an exception: bar' . \PHP_EOL, $e->getMessage());
        static::assertCount(1, $e->getErrorsForTaxProvider('tax_provider'));
        static::assertCount(0, $e->getErrorsForTaxProvider('foo_provider'));

        $e->add('another_tax_provider', new \Exception('baz'));

        static::assertInstanceOf(ShopwareHttpException::class, $e);
        static::assertTrue($e->hasExceptions());
        static::assertSame('There were 2 errors while fetching taxes from providers: ' . \PHP_EOL . 'Tax provider \'tax_provider\' threw an exception: bar' . \PHP_EOL . 'Tax provider \'another_tax_provider\' threw an exception: baz' . \PHP_EOL, $e->getMessage());
        static::assertCount(1, $e->getErrorsForTaxProvider('tax_provider'));
        static::assertCount(1, $e->getErrorsForTaxProvider('another_tax_provider'));
        static::assertCount(0, $e->getErrorsForTaxProvider('foo_provider'));
    }
}
