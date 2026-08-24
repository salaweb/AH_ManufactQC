import { describe, expect, it } from 'vitest';
import ca from '../../lang/ca.json';
import es from '../../lang/es.json';

function flattenKeys(object, prefix = '') {
    return Object.entries(object).flatMap(([key, value]) => {
        const path = prefix ? `${prefix}.${key}` : key;

        return typeof value === 'object' && value !== null
            ? flattenKeys(value, path)
            : [path];
    });
}

describe('i18n translation parity', () => {
    it('has exactly the same keys in ca.json and es.json', () => {
        const caKeys = flattenKeys(ca).sort();
        const esKeys = flattenKeys(es).sort();

        expect(caKeys).toEqual(esKeys);
    });
});
