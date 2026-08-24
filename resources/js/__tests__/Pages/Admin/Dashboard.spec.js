import { describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { i18n } from '../../../i18n';

const apiGet = vi.fn();

vi.mock('../../../api', () => ({
    api: {
        get: (...args) => apiGet(...args),
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { post: vi.fn() },
    Link: { template: '<a><slot /></a>' },
}));

const { default: Dashboard } = await import('../../../Pages/Admin/Dashboard.vue');

function dashboardResponse() {
    return {
        stats: {
            total_equipment: 10,
            checked_equipment: 4,
            completion_percentage: 40,
            total_defects: 3,
        },
        defects_by_type: [{ tipo: 'visual', count: 2 }],
        responsibilities: [{ responsibility: 'producció', count: 2 }],
        trends: [{ section: 'QUALITAT', total_answers: 10, defect_answers: 3, defect_rate: 30 }],
        recent_photos: [],
    };
}

describe('Admin Dashboard page', () => {
    it('renders the KPI stat cards with values from the API', async () => {
        apiGet.mockImplementation((url) =>
            Promise.resolve(url.startsWith('/api/projects') ? [] : dashboardResponse()),
        );

        const wrapper = mount(Dashboard, { global: { plugins: [i18n] } });
        await flushPromises();

        expect(wrapper.text()).toContain('10');
        expect(wrapper.text()).toContain('4');
        expect(wrapper.text()).toContain('40%');
        expect(wrapper.text()).toContain('3');
    });

    it('renders the defects-by-type chart with the fetched categories', async () => {
        apiGet.mockImplementation((url) =>
            Promise.resolve(url.startsWith('/api/projects') ? [] : dashboardResponse()),
        );

        const wrapper = mount(Dashboard, { global: { plugins: [i18n] } });
        await flushPromises();

        expect(wrapper.text()).toContain('visual');
    });
});
