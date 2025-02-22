/**
 * @package admin
 */

import jsonApiParserService from 'src/core/service/jsonapi-parser.service';

describe('core/service/jsonapi-parser.service.js', () => {
    it('should reject when we are providing an array, number, undefined or null', async () => {
        const arrayParser = jsonApiParserService([1, 2, 3]);
        expect(arrayParser).toBe(null);

        const nestedArrayParser = jsonApiParserService([
            { id: 42, name: 'foo' },
            { id: 92, name: 'bar' }
        ]);
        expect(nestedArrayParser).toBe(null);

        const numberParser = jsonApiParserService(42);
        expect(numberParser).toBe(null);

        const negativeNumberParser = jsonApiParserService(-3);
        expect(negativeNumberParser).toBe(null);

        const undefinedParser = jsonApiParserService(undefined);
        expect(undefinedParser).toBe(null);

        const nullParser = jsonApiParserService(null);
        expect(nullParser).toBe(null);
    });

    it('should not parse a malformed JSON string', async () => {
        const brokenJsonParser = jsonApiParserService('{foo:"bar"}');
        expect(brokenJsonParser).toBe(null);
    });

    it(
        'should parse a valid JSON string which is not following the spec',
        async () => {
            const validJsonParser = jsonApiParserService('{"foo":"bar"}');
            expect(validJsonParser).toEqual({
                foo: 'bar'
            });
        }
    );

    it('should parse a valid JSON string which follows the spec', async () => {
        const validJsonApiParser = jsonApiParserService(JSON.stringify({
            data: [{
                id: 1,
                type: 'article',
                attributes: {
                    title: 'Foo bar'
                },
                relationships: {
                    author: {
                        data: { id: 1, type: 'people' }
                    }
                }
            }],
            included: [{
                type: 'people',
                id: 1,
                attributes: {
                    name: 'Peter'
                }
            }]
        }));

        expect(JSON.stringify(validJsonApiParser)).toBe(JSON.stringify({
            links: null,
            errors: null,
            data: [{
                id: 1,
                type: 'article',
                links: {},
                meta: {},
                title: 'Foo bar',
                author: {
                    id: 1,
                    type: 'people',
                    links: {},
                    meta: {},
                    name: 'Peter'
                }
            }],
            associations: {},
            aggregations: null,
            parsed: true
        }));
    });
});
