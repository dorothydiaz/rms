import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

import fs from 'node:fs';
import path from 'node:path';

function normalizeManifestPlugin() {
    return {
        name: 'normalize-manifest',
        closeBundle() {
            const manifestPath = path.resolve(process.cwd(), 'public/build/manifest.json');
            if (fs.existsSync(manifestPath)) {
                try {
                    const content = fs.readFileSync(manifestPath, 'utf-8');
                    const manifest = JSON.parse(content);
                    const normalized = {};
                    for (const [key, value] of Object.entries(manifest)) {
                        const cleanKey = key.replace(/^[A-Za-z]:\/.*?(resources\/)/i, '$1');
                        if (value && typeof value === 'object' && value.src) {
                            value.src = value.src.replace(/^[A-Za-z]:\/.*?(resources\/)/i, '$1');
                        }
                        normalized[cleanKey] = value;
                    }
                    fs.writeFileSync(manifestPath, JSON.stringify(normalized, null, 2), 'utf-8');
                } catch (e) {
                    console.error('Failed to normalize manifest:', e);
                }
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/erp-rfq-core.css',
                'resources/js/app.js',
                'resources/css/modules/purchase-orders.css',
                'resources/js/modules/purchase-orders.js',
                'resources/css/modules/request-quotations.css',
                'resources/js/modules/request-quotations.js',
                'resources/js/modules/internal-transfer.js',
                'resources/css/modules/stocks-overview.css',
                'resources/js/modules/stocks-overview.js',
                'resources/css/modules/stock-in.css',
                'resources/js/modules/stock-in.js',
                'resources/css/modules/stock-out.css',
                'resources/js/modules/stock-out.js',
                'resources/css/modules/production.css',
                'resources/js/modules/production.js',
                'resources/css/modules/waste-expiry.css',
                'resources/js/modules/waste-expiry.js',
                'resources/css/modules/recipe-management.css',
                'resources/js/modules/recipe-management.js',
                'resources/css/modules/reorder-rules.css',
                'resources/js/modules/reorder-rules.js',
                'resources/css/modules/product-categories.css',
                'resources/js/modules/product-categories.js',
                'resources/css/modules/uom-configuration.css',
                'resources/js/modules/uom-configuration.js',
                'resources/css/modules/warehouse-storage.css',
                'resources/js/modules/warehouse-storage.js',
                'resources/css/modules/movement-supply-flows.css',
                'resources/js/modules/movement-supply-flows.js',
                'resources/css/modules/vendor-masterlist.css',
                'resources/js/modules/vendor-masterlist.js',
                'resources/css/modules/hr-schedules.css',
                'resources/js/modules/hr-schedules.js',
                'resources/css/modules/hr-corrections.css',
                'resources/js/modules/hr-corrections.js',
                'resources/js/modules/hr-dtr.js',
                'resources/css/modules/hr-timekeeping.css',
                'resources/js/modules/hr-timekeeping.js',
                'resources/js/modules/hr-undertime.js',
                'resources/js/modules/hr-overtime.js',
                'resources/css/modules/hr-employees.css',
                'resources/js/modules/hr-employees.js',
                'resources/css/modules/hr-employee-detail.css',
                'resources/js/modules/hr-employee-detail.js',
                'resources/css/modules/hr-members-modal.css',
                'resources/js/modules/hr-members-modal.js',
                'resources/js/modules/hr-companies.js',
                'resources/js/modules/hr-documents.js',
                'resources/js/modules/hr-branches.js',
                'resources/js/modules/hr-departments.js',
                'resources/css/modules/hr-org-structure.css',
                'resources/js/modules/hr-org-structure.js',
                'resources/js/modules/hr-positions.js',
                'resources/css/modules/hr-wage-distortion.css',
                'resources/js/modules/hr-wage-distortion.js',
                'resources/css/modules/hr-premium-pay.css',
                'resources/js/modules/hr-premium-pay.js',
                'resources/js/modules/hr-holidays.js',
                'resources/css/modules/hr-payslip-detail.css',
                'resources/js/modules/hr-payslip-detail.js',
                'resources/css/modules/hr-payslips.css',
                'resources/js/modules/hr-payslips.js',
                'resources/css/modules/hr-register.css',
                'resources/js/modules/hr-register.js',
                'resources/js/modules/hr-statutory-rules.js',
                'resources/js/modules/hr-periods.js',
                'resources/js/modules/hr-applicants.js',
                'resources/js/modules/hr-vacancies.js',
                'resources/js/modules/hr-interviews.js',
                'resources/js/modules/hr-roles.js',
                'resources/js/modules/hr-dashboard.js',
                'resources/js/modules/hr-users.js',
                'resources/js/modules/hr-audit-logs.js',
                'resources/js/modules/hr-leave.js',
                'resources/js/modules/hr-performance.js',
                'resources/js/modules/hr-training.js',
                'resources/css/modules/business-settings.css',
                'resources/js/modules/business-settings.js',
                'resources/css/modules/companies-branches.css',
                'resources/js/modules/companies-branches.js',
                'resources/css/modules/account-settings.css',
                'resources/js/modules/account-settings.js',
                'resources/js/modules/auth-login.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        normalizeManifestPlugin(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        chunkSizeWarningLimit: 1200,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                    }
                },
            },
        },
    },
});




