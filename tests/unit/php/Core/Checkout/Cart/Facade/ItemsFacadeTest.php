<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Checkout\Cart\Facade;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Facade\CartFacadeHelper;
use Shopware\Core\Checkout\Cart\Facade\ContainerFacade;
use Shopware\Core\Checkout\Cart\Facade\ItemFacade;
use Shopware\Core\Checkout\Cart\Facade\ItemsFacade;
use Shopware\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 *
 * @covers \Shopware\Core\Checkout\Cart\Facade\ItemsFacade
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsAddTrait
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsHasTrait
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsRemoveTrait
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsCountTrait
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsGetTrait
 * @covers \Shopware\Core\Checkout\Cart\Facade\Traits\ItemsIteratorTrait
 */
class ItemsFacadeTest extends TestCase
{
    public function testPublicApiAvailable(): void
    {
        $items = new LineItemCollection();

        $stubs = $this->createMock(ScriptPriceStubs::class);
        $helper = $this->createMock(CartFacadeHelper::class);
        $context = $this->createMock(SalesChannelContext::class);

        $facade = new ItemsFacade($items, $stubs, $helper, $context);

        $facade->add(
            $this->item(new LineItem('item-1', 'item', 'reference'))
        );

        static::assertEquals(1, $facade->count());
        static::assertTrue($facade->has('item-1'));
        static::assertInstanceOf(ItemFacade::class, $facade->get('item-1'));
        static::assertIsIterable($facade);

        $facade->remove('item-1');

        static::assertEquals(0, $facade->count());
        static::assertFalse($facade->has('item-1'));
        static::assertNull($facade->get('item-1'));

        $facade->add(
            $this->item((new LineItem('duplicate', 'item', 'reference'))->setStackable(true))
        );
        $facade->add(
            $this->item((new LineItem('duplicate', 'item', 'reference'))->setStackable(true))
        );

        static::assertEquals(1, $facade->count());
        static::assertTrue($facade->has('duplicate'));
        static::assertInstanceOf(ItemFacade::class, $facade->get('duplicate'));
        static::assertEquals(2, $facade->get('duplicate')->getQuantity());

        static::assertTrue(
            $facade->has($this->item(new LineItem('duplicate', 'item', 'reference'))),
            'The item id should be considered when checking for an item'
        );
        static::assertTrue(
            $facade->has($this->item(new LineItem('other-id', 'item', 'reference'))),
            'The item id should be considered when checking for an item'
        );
        static::assertFalse(
            $facade->has($this->item(new LineItem('other-id', 'other-item', 'reference'))),
            'The item type should be considered when checking for an item'
        );

        $facade->remove(
            $this->item(new LineItem('duplicate', 'item', 'reference'))
        );
        static::assertEquals(0, $facade->count(), 'Removing an item by its facade should remove all items with the same id');

        $facade->add(
            $this->item(new LineItem('item-1', LineItem::CONTAINER_LINE_ITEM, 'reference'))
        );

        static::assertEquals(1, $facade->count());
        static::assertTrue($facade->has('item-1'));
        static::assertInstanceOf(ContainerFacade::class, $facade->get('item-1'), 'Container types should be wrapped in a ContainerFacade');

        $facade->add(
            $this->item(new LineItem('item-2', 'item', 'reference'))
        );

        $asserted = 0;
        foreach ($facade as $item) {
            ++$asserted;

            if ($item->getId() === 'item-1') {
                static::assertInstanceOf(ContainerFacade::class, $item);
            } else {
                static::assertInstanceOf(ItemFacade::class, $item);
            }
        }
        static::assertEquals(2, $asserted);
    }

    private function item(LineItem $item): ItemFacade
    {
        $stubs = $this->createMock(ScriptPriceStubs::class);
        $helper = $this->createMock(CartFacadeHelper::class);
        $context = $this->createMock(SalesChannelContext::class);

        return new ItemFacade($item, $stubs, $helper, $context);
    }
}
