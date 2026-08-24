import { describe, expect, it, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import { i18n, STORAGE_KEY } from '../../i18n';

describe('LanguageSelector', () => {
    beforeEach(() => {
        localStorage.clear();
        i18n.global.locale.value = 'ca';
    });

    it('renders both language options', () => {
        const wrapper = mount(LanguageSelector, { global: { plugins: [i18n] } });

        expect(wrapper.text()).toContain('Català');
        expect(wrapper.text()).toContain('Castellà');
    });

    it('switches the active locale and persists it to localStorage', async () => {
        const wrapper = mount(LanguageSelector, { global: { plugins: [i18n] } });

        const buttons = wrapper.findAll('button');
        await buttons[1].trigger('click');

        expect(i18n.global.locale.value).toBe('es');
        expect(localStorage.getItem(STORAGE_KEY)).toBe('es');
    });
});
