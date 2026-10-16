/**
 * Talent Acquisition - Interviews Schedule Module
 */

    function openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.add('active');
    }

    function closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.remove('active');
    }

    function openScorecardModal(interviewId, candidateName) {
        document.getElementById('scorecardCandidateName').innerText = candidateName;
        document.getElementById('scorecardForm').action = '/hr/recruitment/interviews/' + interviewId + '/evaluate';
        openModal('scorecardModal');
    }

    function openEditInterviewModal(interview) {
        document.getElementById('editInterviewForm').action = '/hr/recruitment/interviews/' + interview.id;
        document.getElementById('edit_int_date').value = interview.interview_date ? interview.interview_date.substring(0, 10) : '';
        document.getElementById('edit_int_time').value = interview.interview_time || '';
        document.getElementById('edit_int_stage').value = interview.interview_stage || 'HR Interview';
        document.getElementById('edit_int_type').value = interview.interview_type || 'Face-to-face';
        document.getElementById('edit_int_status').value = interview.status || 'Scheduled';
        document.getElementById('edit_int_link').value = interview.location_or_link || '';
        document.getElementById('edit_int_instructions').value = interview.instructions || '';
        openModal('editInterviewModal');
    }

Object.assign(window, {
    openModal,
    closeModal,
    openScorecardModal,
    openEditInterviewModal,
});
