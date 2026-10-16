/**
 * Talent Acquisition - Job Vacancies Module
 */

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function openEditVacancyModal(vacancy) {
        document.getElementById('editVacancyForm').action = '/hr/recruitment/vacancies/' + vacancy.id;
        document.getElementById('edit_title').value = vacancy.title || '';
        document.getElementById('edit_department_id').value = vacancy.department_id || '';
        document.getElementById('edit_position_id').value = vacancy.position_id || '';
        document.getElementById('edit_employment_type').value = vacancy.employment_type || 'Regular';
        document.getElementById('edit_work_setup').value = vacancy.work_setup || 'On-site';
        document.getElementById('edit_branch_id').value = vacancy.branch_id || '';
        document.getElementById('edit_number_of_openings').value = vacancy.number_of_openings || 1;
        document.getElementById('edit_salary_range_min').value = vacancy.salary_range_min || '';
        document.getElementById('edit_salary_range_max').value = vacancy.salary_range_max || '';
        document.getElementById('edit_opening_date').value = vacancy.opening_date ? vacancy.opening_date.substring(0, 10) : '';
        document.getElementById('edit_closing_date').value = vacancy.closing_date ? vacancy.closing_date.substring(0, 10) : '';
        document.getElementById('edit_status').value = vacancy.status || 'Open';
        document.getElementById('edit_job_description').value = vacancy.job_description || '';
        document.getElementById('edit_responsibilities').value = vacancy.responsibilities || '';
        document.getElementById('edit_qualifications').value = vacancy.qualifications || '';
        document.getElementById('edit_required_skills').value = vacancy.required_skills || '';
        document.getElementById('edit_preferred_skills').value = vacancy.preferred_skills || '';
        document.getElementById('edit_benefits').value = vacancy.benefits || '';

        openModal('editVacancyModal');
    }

Object.assign(window, {
    openModal,
    closeModal,
    openEditVacancyModal,
});
