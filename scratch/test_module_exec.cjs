const fs = require('fs');

function testModule(name, path, configObjName, configVal) {
    console.log(`\n=================== TESTING ${name} ===================`);
    const code = fs.readFileSync(path, 'utf8');

    // Mock minimal browser environment
    const storage = {};
    const mockElement = () => ({
        value: '',
        textContent: '',
        innerHTML: '',
        style: {},
        classList: {
            add: () => {},
            remove: () => {},
            contains: () => false,
            toggle: () => {}
        },
        setAttribute: () => {},
        removeAttribute: () => {},
        addEventListener: () => {},
        querySelector: () => mockElement(),
        querySelectorAll: () => [],
        appendChild: () => {},
        children: [],
        dataset: {}
    });

    const windowMock = {
        addEventListener: (event, cb) => {
            if (event === 'DOMContentLoaded') {
                try {
                    cb();
                } catch(e) {
                    console.error(`  [DOMContentLoaded Error in ${name}]:`, e);
                }
            }
        },
        removeEventListener: () => {},
        localStorage: {
            getItem: (k) => storage[k] || null,
            setItem: (k, v) => { storage[k] = String(v); },
            removeItem: (k) => { delete storage[k]; }
        },
        document: {
            getElementById: (id) => mockElement(),
            querySelector: (sel) => mockElement(),
            querySelectorAll: (sel) => [mockElement()],
            createElement: (tag) => mockElement(),
            addEventListener: (event, cb) => {
                if (event === 'DOMContentLoaded') {
                    try {
                        cb();
                    } catch(e) {
                        console.error(`  [document DOMContentLoaded Error in ${name}]:`, e);
                    }
                }
            }
        },
        scrollTo: () => {},
        location: { href: '' },
        navigator: { clipboard: { writeText: () => Promise.resolve() } }
    };
    windowMock.window = windowMock;
    windowMock.document.defaultView = windowMock;
    if (configObjName) {
        windowMock[configObjName] = configVal;
    }

    try {
        const fn = new Function('window', 'document', 'localStorage', 'navigator', code);
        fn(windowMock, windowMock.document, windowMock.localStorage, windowMock.navigator);
        console.log(`  SUCCESS: ${name} executed without top-level errors!`);
    } catch(err) {
        console.error(`  CRASH in ${name}:`, err);
    }
}

testModule('Production', 'resources/js/modules/production.js', 'ProductionConfig', {
    routes: {
        getProductionData: '/inventory/api/production-data',
        createBatch: '/inventory/api/production/create-batch',
        cancelBatch: '/inventory/api/production/cancel-batch',
    }
});

testModule('Recipe Management', 'resources/js/modules/recipe-management.js');

testModule('Stocks Overview', 'resources/js/modules/stocks-overview.js', 'StocksOverviewConfig', {
    routes: {
        stocksOverviewData: '/inventory/api/stocks-overview-data'
    },
    data: {
        initialProducts: [],
        initialCategories: [],
        initialLedger: []
    }
});
