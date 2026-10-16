/**
 * build_hr_recruitment_assets.cjs
 * Modularizes the 3 HR Recruitment & Talent Acquisition views:
 * 1. applicants.blade.php
 * 2. vacancies.blade.php
 * 3. interviews.blade.php
 */

const fs = require('fs');
const path = require('path');

// ==========================================
// 1. applicants.blade.php
// ==========================================
console.log('Processing applicants.blade.php...');
const applicantsPath = 'resources/views/hr/recruitment/applicants.blade.php';
const applicantsRaw = fs.readFileSync(applicantsPath, 'utf8');
const applicantsLines = applicantsRaw.split('\n');

// Style is lines 20 to 39 in file (0-indexed: 19 to 39)
const applicantsCss = `/* Talent Acquisition - Applicants Pipeline */
${applicantsLines.slice(19, 39).join('\n').trim()}

.applicant-drawer {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
`;

// Script is lines 1602 to 2177 (0-indexed: 1601 to 2177)
let applicantsJsRaw = applicantsLines.slice(1601, 2177).join('\n');

// Bridge CSRF token in dynamic form strings:
// Replace '@csrf' with `<input type="hidden" name="_token" value="${csrfToken}">`
const csrfReplacement = `<input type="hidden" name="_token" value="\${csrfToken}">`;
applicantsJsRaw = applicantsJsRaw.split('@csrf').join(csrfReplacement);

const applicantsJs = `/**
 * Talent Acquisition - Applicants Pipeline Module
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

${applicantsJsRaw}

// Global window exposure
Object.assign(window, {
    openModal,
    closeModal,
    addExperienceRow,
    handleScreeningDecisionChange,
    openScreeningModal,
    openScheduleInterviewFor,
    openScorecardModal,
    openAssessmentModal,
    openFinalReviewModal,
    openConvertModal,
    openStageModal,
    openCurrentStageModal,
    switchDrawerTab,
    openApplicantDrawer,
    closeApplicantDrawer,
    renderDrawerData,
});
`;

// HTML content: lines 6 to 18 (0-indexed 5 to 18) and lines 41 to 1599 (0-indexed 40 to 1599)
const applicantsHtml = [
    ...applicantsLines.slice(5, 18),
    ...applicantsLines.slice(40, 1599)
].join('\n');

const newApplicantsBlade = `@extends('layouts.app')

@section('title', 'Talent Acquisition - Applicants Pipeline')

@push('styles')
    @vite(['resources/css/modules/hr-applicants.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-applicants.js'])
@endpush

@section('content')
${applicantsHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-applicants.css', applicantsCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-applicants.js', applicantsJs, 'utf8');
fs.writeFileSync(applicantsPath, newApplicantsBlade, 'utf8');
console.log('Saved applicants.blade.php, hr-applicants.css, hr-applicants.js');


// ==========================================
// 2. vacancies.blade.php
// ==========================================
console.log('Processing vacancies.blade.php...');
const vacanciesPath = 'resources/views/hr/recruitment/vacancies.blade.php';
const vacanciesRaw = fs.readFileSync(vacanciesPath, 'utf8');
const vacanciesLines = vacanciesRaw.split('\n');

// Style is lines 16 to 35 in file (0-indexed: 15 to 35)
const vacanciesCss = `/* Talent Acquisition - Job Vacancies */
${vacanciesLines.slice(15, 35).join('\n').trim()}
`;

// Script is lines 659 to 695 (0-indexed: 658 to 695)
const vacanciesJsRaw = vacanciesLines.slice(658, 695).join('\n');

const vacanciesJs = `/**
 * Talent Acquisition - Job Vacancies Module
 */

${vacanciesJsRaw}

Object.assign(window, {
    openModal,
    closeModal,
    openEditVacancyModal,
});
`;

// HTML content: lines 6 to 14 (0-indexed 5 to 14) and lines 37 to 656 (0-indexed 36 to 656)
const vacanciesHtml = [
    ...vacanciesLines.slice(5, 14),
    ...vacanciesLines.slice(36, 656)
].join('\n');

const newVacanciesBlade = `@extends('layouts.app')

@section('title', 'Job Vacancies - Talent Acquisition')

@push('styles')
    @vite(['resources/css/modules/hr-vacancies.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-vacancies.js'])
@endpush

@section('content')
${vacanciesHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-vacancies.css', vacanciesCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-vacancies.js', vacanciesJs, 'utf8');
fs.writeFileSync(vacanciesPath, newVacanciesBlade, 'utf8');
console.log('Saved vacancies.blade.php, hr-vacancies.css, hr-vacancies.js');


// ==========================================
// 3. interviews.blade.php
// ==========================================
console.log('Processing interviews.blade.php...');
const interviewsPath = 'resources/views/hr/recruitment/interviews.blade.php';
const interviewsRaw = fs.readFileSync(interviewsPath, 'utf8');
const interviewsLines = interviewsRaw.split('\n');

// Style is lines 16 to 35 in file (0-indexed: 15 to 35)
const interviewsCss = `/* Talent Acquisition - Interviews Schedule */
${interviewsLines.slice(15, 35).join('\n').trim()}
`;

// Script is lines 536 to 562 (0-indexed: 535 to 562)
const interviewsJsRaw = interviewsLines.slice(535, 562).join('\n');

const interviewsJs = `/**
 * Talent Acquisition - Interviews Schedule Module
 */

${interviewsJsRaw}

Object.assign(window, {
    openModal,
    closeModal,
    openScorecardModal,
    openEditInterviewModal,
});
`;

// HTML content: lines 6 to 14 (0-indexed 5 to 14) and lines 37 to 533 (0-indexed 36 to 533)
const interviewsHtml = [
    ...interviewsLines.slice(5, 14),
    ...interviewsLines.slice(36, 533)
].join('\n');

const newInterviewsBlade = `@extends('layouts.app')

@section('title', 'Interviews Schedule - Talent Acquisition')

@push('styles')
    @vite(['resources/css/modules/hr-interviews.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-interviews.js'])
@endpush

@section('content')
${interviewsHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-interviews.css', interviewsCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-interviews.js', interviewsJs, 'utf8');
fs.writeFileSync(interviewsPath, newInterviewsBlade, 'utf8');
console.log('Saved interviews.blade.php, hr-interviews.css, hr-interviews.js');

console.log('ALL 3 RECRUITMENT MODULES EXTRACTED SUCCESSFULLY!');
