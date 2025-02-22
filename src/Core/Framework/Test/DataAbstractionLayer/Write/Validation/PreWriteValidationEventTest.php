<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\DataAbstractionLayer\Write\Validation;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Test\IdsCollection;

/**
 * @internal
 */
class PreWriteValidationEventTest extends TestCase
{
    /**
     * @dataProvider getPrimaryKeysProvider
     */
    public function testGetPrimaryKeys(PreWriteValidationEvent $event, array $assertions): void
    {
        foreach ($assertions as $entity => $ids) {
            static::assertEquals($ids, $event->getPrimaryKeys($entity), \sprintf('Primary keys for entity %s not match', $entity));
        }
    }

    /**
     * @dataProvider getPrimaryKeysProvider
     */
    public function testGetDeletedPrimaryKeysProvider(PreWriteValidationEvent $event, array $assertions): void
    {
        foreach ($assertions as $entity => $ids) {
            static::assertEquals($ids, $event->getPrimaryKeys($entity), \sprintf('Primary keys for entity %s not match', $entity));
        }
    }

    /**
     * @dataProvider getDeletedPrimaryKeysProvider
     */
    public function testGetDeletedPrimaryKeys(PreWriteValidationEvent $event, array $assertions): void
    {
        foreach ($assertions as $entity => $ids) {
            static::assertEquals($ids, $event->getDeletedPrimaryKeys($entity), \sprintf('Deleted primary keys for entity %s not match', $entity));
        }
    }

    public static function getDeletedPrimaryKeysProvider(): \Generator
    {
        $context = WriteContext::createFromContext(Context::createDefaultContext());

        $ids = new IdsCollection();

        yield 'Test single delete' => [
            new PreWriteValidationEvent($context, [self::delete('product', ['id' => $ids->get('p1')])]),
            [
                'product' => [['id' => $ids->get('p1')]],
            ],
        ];

        yield 'Test multi insert' => [
            new PreWriteValidationEvent($context, [
                self::insert('product', ['id' => $ids->get('p1')]),
                self::delete('product', ['id' => $ids->get('p2')]),
                self::delete('product', ['id' => $ids->get('p3')]),

                self::insert('category', ['id' => $ids->get('c1')]),
                self::delete('category', ['id' => $ids->get('c2')]),
                self::delete('category', ['id' => $ids->get('c3')]),

                self::delete('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c1')]),
                self::insert('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c2')]),
                self::delete('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c3')]),

                self::insert('foo', ['id' => $ids->get('f1')]),
                self::delete('foo', ['id' => $ids->get('f2')]),
                self::insert('foo', ['id' => $ids->get('f3')]),
            ]),
            [
                'product' => [
                    ['id' => $ids->get('p2')],
                    ['id' => $ids->get('p3')],
                ],
                'category' => [
                    ['id' => $ids->get('c2')],
                    ['id' => $ids->get('c3')],
                ],
                'translation' => [
                    ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c1')],
                    ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c3')],
                ],
                'foo' => [
                    ['id' => $ids->get('f2')],
                ],
                'not-found' => [],
            ],
        ];
    }

    public static function getPrimaryKeysProvider(): \Generator
    {
        $context = WriteContext::createFromContext(Context::createDefaultContext());

        $ids = new IdsCollection();

        yield 'Test single insert' => [
            new PreWriteValidationEvent($context, [self::insert('product', ['id' => $ids->get('p1')])]),
            [
                'product' => [['id' => $ids->get('p1')]],
            ],
        ];
        yield 'Test multi insert' => [
            new PreWriteValidationEvent($context, [
                self::insert('product', ['id' => $ids->get('p1')]),
                self::insert('product', ['id' => $ids->get('p2')]),
                self::insert('product', ['id' => $ids->get('p3')]),

                self::insert('category', ['id' => $ids->get('c1')]),
                self::insert('category', ['id' => $ids->get('c2')]),
                self::insert('category', ['id' => $ids->get('c3')]),

                self::insert('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c1')]),
                self::insert('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c2')]),
                self::insert('translation', ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c3')]),

                self::delete('foo', ['id' => $ids->get('f1')]),
                self::delete('foo', ['id' => $ids->get('f2')]),
                self::delete('foo', ['id' => $ids->get('f3')]),
            ]),
            [
                'product' => [
                    ['id' => $ids->get('p1')],
                    ['id' => $ids->get('p2')],
                    ['id' => $ids->get('p3')],
                ],
                'foo' => [
                    ['id' => $ids->get('f1')],
                    ['id' => $ids->get('f2')],
                    ['id' => $ids->get('f3')],
                ],
                'category' => [
                    ['id' => $ids->get('c1')],
                    ['id' => $ids->get('c2')],
                    ['id' => $ids->get('c3')],
                ],
                'translation' => [
                    ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c1')],
                    ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c2')],
                    ['language_id' => Defaults::LANGUAGE_SYSTEM, 'id' => $ids->get('c3')],
                ],
                'not-found' => [],
            ],
        ];
    }

    private static function insert(string $entity, array $primaryKey): InsertCommand
    {
        $definition = self::getDefinition($entity);

        $existence = new EntityExistence('', [], false, false, false, []);

        return new InsertCommand($definition, [], $primaryKey, $existence, '');
    }

    private static function delete(string $entity, array $primaryKey): DeleteCommand
    {
        $definition = self::getDefinition($entity);

        return new DeleteCommand($definition, $primaryKey, new EntityExistence('', [], false, false, false, []));
    }

    private static function getDefinition(string $entity): EntityDefinition
    {
        $definition = new class() extends EntityDefinition {
            public string $entityName;

            public function getEntityName(): string
            {
                return $this->entityName;
            }

            protected function defineFields(): FieldCollection
            {
                return new FieldCollection([]);
            }
        };

        $definition->entityName = $entity;

        return $definition;
    }
}
