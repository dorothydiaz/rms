const fs = require('fs');
const vm = require('vm');
const path = require('path');

const BASE = path.resolve('.');
let allPassed = true;

function check(name, condition) {
    if (condition) {
        console.log(`[PASS] ${name}`);
    } else {
        console.error(`[FAIL] ${name}`);
        allPassed = false;
    }
}

// 1. Syntax check on script.js
const scriptJsPath = path.join(BASE, 'public/assets/js/script.js');
const scriptContent = fs.readFileSync(scriptJsPath, 'utf8');
try {
    new vm.Script(scriptContent);
    check('script.js syntax valid', true);
} catch (e) {
    console.error('script.js syntax error:', e.message);
    check('script.js syntax valid', false);
}

// 2. Presence of Universal Scroller in script.js
check('initUniversalFilterBarScrollers defined in script.js', scriptContent.includes('function initUniversalFilterBarScrollers()'));
check('Caret left button created in script.js', scriptContent.includes('ph-caret-left'));
check('Caret right button created in script.js', scriptContent.includes('ph-caret-right'));
check('ResizeObserver integration present in script.js', scriptContent.includes('new ResizeObserver'));
check('Smooth scroll step present in script.js', scriptContent.includes("behavior: 'smooth'"));

// 3. CSS rules in styles.css
const stylesCssPath = path.join(BASE, 'public/assets/css/styles.css');
const stylesContent = fs.readFileSync(stylesCssPath, 'utf8');

check('filter-bar-wrapper class present in styles.css', stylesContent.includes('.filter-bar-wrapper'));
check('filter-bar-scroll-btn class present in styles.css', stylesContent.includes('.filter-bar-scroll-btn'));
check('filter-bar-scroll-track class present in styles.css', stylesContent.includes('.filter-bar-scroll-track'));
check('.hr-filter-bar has flex-wrap: nowrap !important', stylesContent.includes('flex-wrap: nowrap !important; /* CRITICAL: NO 2ND ROW WRAP */'));
check('Caret button .is-hidden style present in styles.css', stylesContent.includes('.filter-bar-scroll-btn.is-hidden'));

// 4. Blade component existence
const componentPath = path.join(BASE, 'resources/views/components/filter-bar.blade.php');
check('<x-filter-bar> component created', fs.existsSync(componentPath));
if (fs.existsSync(componentPath)) {
    const compContent = fs.readFileSync(componentPath, 'utf8');
    check('<x-filter-bar> has caret-left', compContent.includes('ph-caret-left'));
    check('<x-filter-bar> has caret-right', compContent.includes('ph-caret-right'));
}

// 5. Verification that no hr-filter-bar retains flex-wrap: wrap
const hrFiles = [
    'resources/views/hr/admin/roles.blade.php',
    'resources/views/hr/attendance/corrections.blade.php',
    'resources/views/hr/attendance/overtime.blade.php',
    'resources/views/hr/attendance/undertime.blade.php',
    'resources/views/hr/people/companies.blade.php',
    'resources/views/hr/people/documents.blade.php',
    'resources/views/hr/people/employees.blade.php',
    'resources/views/hr/recruitment/vacancies.blade.php',
    'resources/views/hr/recruitment/interviews.blade.php',
    'resources/views/hr/recruitment/applicants.blade.php',
    'resources/views/hr/performance/evaluations.blade.php',
    'resources/views/hr/training/programs.blade.php',
    'resources/views/hr/training/records.blade.php',
];

hrFiles.forEach(relPath => {
    const fullPath = path.join(BASE, relPath);
    if (fs.existsSync(fullPath)) {
        const content = fs.readFileSync(fullPath, 'utf8');
        const hasWrap = content.includes('class="hr-filter-bar" style="') && content.includes('flex-wrap: wrap');
        check(`${relPath} has no inline hr-filter-bar wrap`, !hasWrap);
    }
});

if (allPassed) {
    console.log('\n>>> ALL FILTER BAR ARCHITECTURE VALIDATION CHECKS PASSED <<<');
    process.exit(0);
} else {
    console.error('\n>>> SOME VALIDATION CHECKS FAILED <<<');
    process.exit(1);
}
