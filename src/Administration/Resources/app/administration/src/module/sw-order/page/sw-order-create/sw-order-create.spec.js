import { shallowMount } from '@vue/test-utils';
import swOrderCreate from 'src/module/sw-order/page/sw-order-create';
import 'src/app/component/base/sw-modal';
import 'src/app/component/base/sw-button-process';
import 'src/app/component/base/sw-button';
import 'src/app/component/structure/sw-page';

/**
 * @package customer-order
 */

const remindPaymentMock = jest.fn(() => {
    return Promise.resolve();
});

Shopware.Component.register('sw-order-create', swOrderCreate);

describe('src/module/sw-order/page/sw-order-create', () => {
    let wrapper;
    let stubs;

    async function createWrapper() {
        return shallowMount(await Shopware.Component.build('sw-order-create'), {
            stubs,
            provide: {
                repositoryFactory: {
                    create: () => ({
                        get: () => Promise.resolve(
                            {
                                translated: {
                                    distinguishableName: 'Cash on Delivery',
                                },
                            }
                        )
                    })
                },
                shortcutService: {
                    startEventListener: () => {},
                    stopEventListener: () => {}
                }
            },
            mocks: {
                $route: {
                    meta: {
                        $module: {
                            routes: {
                                detail: {
                                    children: {
                                        base: {},
                                        other: {}
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    beforeAll(async () => {
        stubs = {
            'router-view': true,
            'sw-icon': true,
            'sw-loader': true,
            'sw-app-actions': true,
            'sw-notification-center': true,
            'sw-help-center': true,
            'sw-search-bar': true,
            'sw-language-switch': true,
            'sw-card-view': true,
            'sw-tabs': true,
            'sw-tabs-item': true,
            'sw-page': await Shopware.Component.build('sw-page'),
            'sw-button': await Shopware.Component.build('sw-button'),
            'sw-button-process': await Shopware.Component.build('sw-button-process'),
            'sw-modal': await Shopware.Component.build('sw-modal')
        };
    });

    beforeEach(async () => {
        wrapper = await createWrapper();

        Shopware.State.unregisterModule('swOrder');
        Shopware.State.registerModule('swOrder', {
            namespaced: true,
            state() {
                return {
                    defaultSalesChannel: null,
                    cart: {
                        token: 'CART-TOKEN',
                        lineItems: [{}]
                    },
                    customer: {},
                    promotionCodes: []
                };
            },
            getters: {
                invalidPromotionCodes() {
                    return [];
                }
            },
            actions: {
                saveOrder() {
                    return {
                        data: {
                            id: Shopware.Utils.createId(),
                            transactions: [
                                {
                                    paymentMethodId: Shopware.Utils.createId()
                                }
                            ]
                        }
                    };
                },
                createCart() {
                    return {
                        token: null,
                        lineItems: []
                    };
                },
                remindPayment: remindPaymentMock
            }
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('should be a Vue.js component', async () => {
        expect(wrapper.vm).toBeTruthy();
    });

    it('should open remind payment modal on save order', async () => {
        await wrapper.find('.sw-button-process').trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.showRemindPaymentModal).toBeTruthy();

        const modal = wrapper.find('.sw-modal');
        expect(modal.isVisible).toBeTruthy();
    });

    it('should be able to close remind payment modal', async () => {
        await wrapper.find('.sw-button-process').trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.showRemindPaymentModal).toBeTruthy();

        const modal = wrapper.find('.sw-modal');
        expect(modal.isVisible).toBeTruthy();

        await modal.find('.sw-modal__footer .sw-button').trigger('click');

        expect(wrapper.vm.isSaveSuccessful).toBeTruthy();
        expect(wrapper.vm.showRemindPaymentModal).not.toBeTruthy();
    });

    it('should remind payment on primary modal action', async () => {
        await wrapper.find('.sw-button-process').trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.showRemindPaymentModal).toBeTruthy();

        const modal = wrapper.find('.sw-modal');
        expect(modal.isVisible).toBeTruthy();

        await modal.find('.sw-modal__footer .sw-button--primary').trigger('click');

        expect(remindPaymentMock).toHaveBeenCalledTimes(1);

        await wrapper.vm.$nextTick();
        expect(wrapper.vm.isSaveSuccessful).toBeTruthy();
        expect(wrapper.vm.showRemindPaymentModal).not.toBeTruthy();
    });
});
