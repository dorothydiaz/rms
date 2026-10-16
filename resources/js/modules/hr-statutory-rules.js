/**
 * Enterprise Philippine Payroll - Statutory Rules Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

export function openRuleModal(id, name, rate, min, max, ee, er, fixed, eff, active) {
    const form = document.getElementById('ruleForm');
    if (form) form.action = '/hr/payroll/statutory-rules/' + id;
    
    const ruleName = document.getElementById('ruleName');
    const ruleRate = document.getElementById('ruleRate');
    const ruleMin = document.getElementById('ruleMin');
    const ruleMax = document.getElementById('ruleMax');
    const ruleEe = document.getElementById('ruleEe');
    const ruleEr = document.getElementById('ruleEr');
    const ruleFixed = document.getElementById('ruleFixed');
    const ruleEff = document.getElementById('ruleEff');
    const ruleActive = document.getElementById('ruleActive');

    if (ruleName) ruleName.value = name;
    if (ruleRate) ruleRate.value = rate;
    if (ruleMin) ruleMin.value = min;
    if (ruleMax) ruleMax.value = max;
    if (ruleEe) ruleEe.value = ee;
    if (ruleEr) ruleEr.value = er;
    if (ruleFixed) ruleFixed.value = fixed;
    if (ruleEff) ruleEff.value = eff;
    if (ruleActive) ruleActive.checked = active;

    openModal('ruleModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openRuleModal,
});
