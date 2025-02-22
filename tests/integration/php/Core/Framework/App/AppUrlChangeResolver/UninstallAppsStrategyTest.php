<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Framework\App\AppUrlChangeResolver;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\App\AppEntity;
use Shopware\Core\Framework\App\AppUrlChangeResolver\UninstallAppsStrategy;
use Shopware\Core\Framework\App\Event\AppDeactivatedEvent;
use Shopware\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Shopware\Core\Framework\App\ShopId\ShopIdProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Theme\ThemeAppLifecycleHandler;
use Shopware\Tests\Integration\Core\Framework\App\AppSystemTestBehaviour;

/**
 * @internal
 */
class UninstallAppsStrategyTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EnvTestBehaviour;
    use AppSystemTestBehaviour;

    private SystemConfigService $systemConfigService;

    private ShopIdProvider $shopIdProvider;

    private Context $context;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->shopIdProvider = $this->getContainer()->get(ShopIdProvider::class);
        $this->context = Context::createDefaultContext();
    }

    public function testGetName(): void
    {
        $uninstallAppsResolver = $this->getContainer()->get(UninstallAppsStrategy::class);

        static::assertEquals(
            UninstallAppsStrategy::STRATEGY_NAME,
            $uninstallAppsResolver->getName()
        );
        static::assertIsString($uninstallAppsResolver->getDescription());
    }

    public function testItReRegistersInstalledApps(): void
    {
        $appDir = __DIR__ . '/../Manifest/_fixtures/test';
        $this->loadAppsFromDir($appDir);

        $app = $this->getInstalledApp($this->context);
        static::assertNotNull($app);

        $shopId = $this->changeAppUrl();

        $themeLifecycleHandler = null;
        if (class_exists(ThemeAppLifecycleHandler::class)) {
            $themeLifecycleHandler = $this->createMock(ThemeAppLifecycleHandler::class);
            $themeLifecycleHandler->expects(static::once())
                ->method('handleUninstall')
                ->with(
                    static::callback(fn (AppDeactivatedEvent $event) => $event->getApp()->getName() === $app->getName())
                );
        }

        $uninstallAppsResolver = new UninstallAppsStrategy(
            $this->getContainer()->get('app.repository'),
            $this->systemConfigService,
            $themeLifecycleHandler
        );

        $uninstallAppsResolver->resolve($this->context);

        static::assertNotEquals($shopId, $this->shopIdProvider->getShopId());

        static::assertNull($this->getInstalledApp($this->context));
    }

    private function changeAppUrl(): string
    {
        $shopId = $this->shopIdProvider->getShopId();

        // create AppUrlChange
        $this->setEnvVars(['APP_URL' => 'https://test.new']);
        $wasThrown = false;

        try {
            $this->shopIdProvider->getShopId();
        } catch (AppUrlChangeDetectedException) {
            $wasThrown = true;
        }
        static::assertTrue($wasThrown);

        return $shopId;
    }

    private function getInstalledApp(Context $context): ?AppEntity
    {
        /** @var EntityRepository $appRepo */
        $appRepo = $this->getContainer()->get('app.repository');

        $criteria = new Criteria();
        $criteria->addAssociation('integration');
        $apps = $appRepo->search($criteria, $context);

        return $apps->first();
    }
}
