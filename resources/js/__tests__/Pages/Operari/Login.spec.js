import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '../../../i18n';

const postMock = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial) => ({
        ...initial,
        errors: {},
        processing: false,
        post: postMock,
    }),
}));

const { default: Login } = await import('../../../Pages/Operari/Login.vue');

describe('Operari Login page', () => {
    it('renders a username field and a password field', () => {
        const wrapper = mount(Login, { global: { plugins: [i18n] } });

        expect(wrapper.find('input[type=password]').exists()).toBe(true);
        expect(wrapper.findAll('input')).toHaveLength(2);
    });

    it('submits to /operari/login when the form is submitted', async () => {
        postMock.mockClear();
        const wrapper = mount(Login, { global: { plugins: [i18n] } });

        await wrapper.find('form').trigger('submit');

        expect(postMock).toHaveBeenCalledWith('/operari/login');
    });
});
