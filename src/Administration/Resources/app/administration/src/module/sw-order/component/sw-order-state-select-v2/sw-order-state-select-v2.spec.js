import { shallowMount } from '@vue/test-utils';
import swOrderStateSelectV2 from 'src/module/sw-order/component/sw-order-state-select-v2';

Shopware.Component.register('sw-order-state-select-v2', swOrderStateSelectV2);

describe('src/module/sw-order/component/sw-order-state-select-v2', () => {
    async function createWrapper() {
        return shallowMount(await Shopware.Component.build('sw-order-state-select-v2'), {
            stubs: {
                'sw-single-select': {
                    template: `
                        <input class='sw-single-select' :value="value" @input="$emit('input', $event.target.value)" />`,
                    props: ['value'],
                },
            },
            propsData: {
                stateType: 'order'
            },
        });
    }

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();
        expect(wrapper.vm).toBeTruthy();
    });

    it('should disabled single select if transition options props are empty', async () => {
        const wrapper = await createWrapper();
        const singleSelect = wrapper.find('.sw-single-select');

        expect(singleSelect.attributes('disabled')).toBeTruthy();
    });

    it('should enable single select if transition options props has value', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            transitionOptions: [
                {
                    disabled: false,
                    id: 'do_pay',
                    name: 'In progress',
                    stateName: 'in_progress',
                }
            ]
        });

        const singleSelect = wrapper.find('.sw-single-select');
        expect(singleSelect.attributes('disabled')).toBeUndefined();
    });

    it('should emit state-select event', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            transitionOptions: [
                {
                    disabled: false,
                    id: 'do_pay',
                    name: 'In progress',
                    stateName: 'in_progress',
                }
            ]
        });

        const singleSelect = wrapper.find('.sw-single-select');

        await singleSelect.setValue('in_progress');
        await singleSelect.trigger('input');

        expect(wrapper.emitted('state-select')[0]).toEqual(['order', 'in_progress']);
    });

    it('should show placeholder correctly', async () => {
        const wrapper = await createWrapper();
        const singleSelect = wrapper.find('.sw-single-select');

        expect(singleSelect.attributes('placeholder'))
            .toEqual('sw-order.stateCard.labelSelectStatePlaceholder');

        await wrapper.setProps({
            placeholder: 'Open',
        });

        expect(singleSelect.attributes('placeholder')).toEqual('Open');
    });
});
