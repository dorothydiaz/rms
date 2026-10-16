/**
 * Talent Acquisition - Applicants Pipeline Module
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let currentDrawerApplicant = null;
    let currentApplicantId = null;

    function openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.add('active');
    }

    function closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.remove('active');
    }

    function addExperienceRow() {
        const container = document.getElementById('experienceRowsContainer');
        const div = document.createElement('div');
        div.className = 'experience-row';
        div.style = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px; position: relative;';
        div.innerHTML = `
            <button type="button" onclick="this.parentElement.remove()" style="position: absolute; right: 10px; top: 10px; border: none; background: transparent; color: #dc2626; cursor: pointer;"><i class="ph ph-trash"></i></button>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Company Name</label>
                    <input type="text" name="experience_companies[]" class="hr-input" placeholder="Company name">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Held</label>
                    <input type="text" name="experience_positions[]" class="hr-input" placeholder="Role title">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Start Date</label>
                    <input type="date" name="experience_start_dates[]" class="hr-input">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">End Date</label>
                    <input type="date" name="experience_end_dates[]" class="hr-input">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reason for Leaving</label>
                    <input type="text" name="experience_reasons_leaving[]" class="hr-input" placeholder="e.g. Career growth">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Responsibilities Summary</label>
                    <input type="text" name="experience_responsibilities[]" class="hr-input" placeholder="Key accomplishments">
                </div>
            </div>
        `;
        container.appendChild(div);
    }

    function handleScreeningDecisionChange(val) {
        const wrap = document.getElementById('screeningRejectReasonWrap');
        if (val === 'Reject') {
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
        }
    }

    function openScreeningModal(applicantId, applicantName) {
        document.getElementById('screeningApplicantName').innerText = applicantName;
        document.getElementById('screeningForm').action = '/hr/recruitment/applicants/' + applicantId + '/screening';
        openModal('screeningModal');
    }

    function openScheduleInterviewFor(applicantId, applicantName, position) {
        document.getElementById('sched_applicant_id').value = applicantId;
        document.getElementById('sched_applicant_display').value = applicantName + ' (' + position + ')';
        openModal('scheduleInterviewModal');
    }

    function openScorecardModal(interviewId, candidateName) {
        document.getElementById('scorecardCandidateName').innerText = candidateName;
        document.getElementById('scorecardForm').action = '/hr/recruitment/interviews/' + interviewId + '/evaluate';
        openModal('scorecardModal');
    }

    function openAssessmentModal(applicantId, candidateName) {
        document.getElementById('assess_applicant_id').value = applicantId;
        document.getElementById('assess_applicant_display').value = candidateName;
        openModal('assessmentModal');
    }

    function openFinalReviewModal(applicantId, candidateName, proposedSalary) {
        document.getElementById('finalReviewCandidateName').innerText = candidateName;
        document.getElementById('finalReviewProposedSalary').value = proposedSalary;
        document.getElementById('finalReviewForm').action = '/hr/recruitment/applicants/' + applicantId + '/final-review';
        openModal('finalReviewModal');
    }

    function openConvertModal(applicantId, candidateName, appliedPosition) {
        document.getElementById('convertApplicantName').innerText = candidateName;
        document.getElementById('convertEmployeeForm').action = '/hr/recruitment/applicants/' + applicantId + '/convert';

        // Auto generate next employee ID EMP-YYYY-5XX
        const year = new Date().getFullYear();
        document.getElementById('convert_employee_id').value = 'EMP-' + year + '-' + Math.floor(500 + Math.random() * 50);

        openModal('convertEmployeeModal');
    }

    function openStageModal(applicantId, currentStatus) {
        currentApplicantId = applicantId;
        document.getElementById('moveStageForm').action = '/hr/recruitment/applicants/' + applicantId + '/stage';
        document.getElementById('moveStageSelect').value = currentStatus;
        openModal('moveStageModal');
    }

    function openCurrentStageModal() {
        if (currentDrawerApplicant) {
            openStageModal(currentDrawerApplicant.applicant.id, currentDrawerApplicant.applicant.status);
        }
    }

    // ==========================================
    // APPLICANT DRAWER LOGIC
    // ==========================================

    function switchDrawerTab(tabName, btn) {
        document.querySelectorAll('.drawer-tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.hr-micro-tab').forEach(b => b.classList.remove('active'));

        const target = document.getElementById('tab-' + tabName);
        if (target) {
            target.style.display = 'block';
        }

        if (btn) {
            btn.classList.add('active');
        } else {
            const matchedBtn = Array.from(document.querySelectorAll('.hr-micro-tab')).find(b => b.innerText.toLowerCase().includes(tabName.replace('-', ' ')));
            if (matchedBtn) matchedBtn.classList.add('active');
        }
    }

    function openApplicantDrawer(applicantId, initialTab = 'overview') {
        currentApplicantId = applicantId;
        const drawer = document.getElementById('applicantDrawer');
        const overlay = document.getElementById('applicantDrawerOverlay');

        drawer.classList.add('active');
        overlay.classList.add('active');

        // Fetch complete JSON data
        fetch('/hr/recruitment/applicants/' + applicantId + '/data')
            .then(res => res.json())
            .then(data => {
                currentDrawerApplicant = data;
                renderDrawerData(data);
                switchDrawerTab(initialTab);
            })
            .catch(err => {
                console.error(err);
            });
    }

    function closeApplicantDrawer() {
        document.getElementById('applicantDrawer').classList.remove('active');
        document.getElementById('applicantDrawerOverlay').classList.remove('active');
    }

    function renderDrawerData(data) {
        const app = data.applicant;

        // Header info
        document.getElementById('drawerFullName').innerText = data.full_name;
        document.getElementById('drawerAvatar').innerText = (app.first_name[0] || 'A') + (app.last_name[0] || 'A');
        document.getElementById('drawerAppliedPosition').innerText = app.applied_position;
        document.getElementById('drawerAppDate').innerText = 'Applied ' + (app.application_date ? app.application_date.substring(0, 10) : 'Recently');
        document.getElementById('drawerStatusBadge').innerText = app.status;
        document.getElementById('drawerFooterAppId').innerText = '#' + app.id;

        // Stepper Progress
        const steps = ['New', 'Screening', 'Shortlisted', 'Interview', 'Assessment', 'FinalReview', 'Offer', 'PreEmployment', 'Hired'];
        const stageIndex = {
            'New': 0, 'Screening': 1, 'Shortlisted': 2, 'Interview': 3,
            'Assessment': 4, 'Final Review': 5, 'Offer': 6, 'Pre-Employment': 7, 'Hired': 8
        };

        const currentIdx = stageIndex[app.status] !== undefined ? stageIndex[app.status] : 1;

        steps.forEach((step, idx) => {
            const el = document.getElementById('step-' + step);
            if (el) {
                el.classList.remove('active', 'completed');
                if (idx < currentIdx) el.classList.add('completed');
                else if (idx === currentIdx) el.classList.add('active');
            }
        });

        // Overview Bar
        const pct = data.pipeline_progress || 40;
        document.getElementById('drawerProgressBar').style.width = pct + '%';
        document.getElementById('drawerProgressText').innerText = pct + '% Pipeline Progress';

        // Contact info
        document.getElementById('drawerContact').innerText = app.contact_number || 'No contact number';
        document.getElementById('drawerEmail').innerText = app.email || 'No email provided';
        document.getElementById('drawerAddress').innerText = (app.city || app.province) ? (app.city + ', ' + app.province) : (app.address || 'Address not provided');

        // Snapshot
        document.getElementById('drawerSalary').innerText = app.desired_salary ? '₱' + Number(app.desired_salary).toLocaleString() + '/mo' : 'Negotiable';
        document.getElementById('drawerSetup').innerText = app.work_setup_preference || 'On-site';
        document.getElementById('drawerSource').innerText = app.source;

        // Meters
        const reqs = data.requirements_stats;
        document.getElementById('drawerReqMeter').innerText = reqs.completed + ' / ' + reqs.total;
        document.getElementById('drawerReqCounterBadge').innerText = reqs.completed + ' / ' + reqs.total + ' Completed';

        const pbs = data.preboarding_stats;
        document.getElementById('drawerTaskMeter').innerText = pbs.completed + ' / ' + pbs.total;
        document.getElementById('drawerTaskCounterBadge').innerText = pbs.completed + ' / ' + pbs.total + ' Completed';

        // Convert Button configuration
        const convBtn = document.getElementById('drawerConvertBtn');
        if (app.status === 'Hired' && app.hired_as_employee_id) {
            convBtn.innerText = 'View in Employee Directory';
            convBtn.onclick = function() {
                window.location.href = '/hr/people/employees/' + app.hired_as_employee_id;
            };
        } else {
            convBtn.innerHTML = '<i class="ph ph-user-check"></i> Convert to Employee';
            convBtn.onclick = function() {
                openConvertModal(app.id, data.full_name, app.applied_position);
            };
        }

        // Render Recent Activity (Overview)
        const recentList = document.getElementById('drawerRecentActivityList');
        recentList.innerHTML = '';
        const history = (data.activity_history || []).slice(-3).reverse();
        if (history.length === 0) {
            recentList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No activity logged yet.</div>';
        } else {
            history.forEach(item => {
                recentList.innerHTML += `
                    <div style="padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12.5px;">
                        <strong style="color: #0f172a;">${item.action}</strong>
                        <div style="color: #64748b; font-size: 12px;">${item.details || ''}</div>
                        <small style="color: #94a3b8;">${item.formatted_time || ''} by ${item.performed_by || 'HR'}</small>
                    </div>
                `;
            });
        }

        // Render Full Activity
        const fullStream = document.getElementById('drawerFullActivityStream');
        fullStream.innerHTML = '';
        (data.activity_history || []).slice().reverse().forEach(item => {
            fullStream.innerHTML += `
                <div class="hr-timeline-item">
                    <div class="hr-timeline-dot"></div>
                    <div class="hr-timeline-title">${item.action}</div>
                    <div class="hr-timeline-meta">${item.formatted_time || ''} • By <strong>${item.performed_by || 'System'}</strong></div>
                    <div class="hr-timeline-desc">${item.details || ''}</div>
                </div>
            `;
        });

        // Render Application Answers
        const answersList = document.getElementById('drawerAppAnswersList');
        answersList.innerHTML = '';
        const ans = app.application_answers || {};
        answersList.innerHTML = `
            <div style="margin-bottom: 14px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Why do you want to join our company?</div>
                <div style="font-size: 13.5px; color: #1e293b; margin-top: 4px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    ${ans.why_join || 'I am eager to contribute my culinary expertise and dedication to exceptional customer service in a high-standard restaurant setting.'}
                </div>
            </div>
            <div style="margin-bottom: 14px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Relevant Experience Highlights</div>
                <div style="font-size: 13.5px; color: #1e293b; margin-top: 4px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    ${ans.relevant_experience || '3+ years experience on hot kitchen line, station prep, grill, and inventory tracking with strict food hygiene.'}
                </div>
            </div>
        `;

        // Render Experience Tab
        const expList = document.getElementById('drawerWorkHistoryList');
        expList.innerHTML = '';
        if (app.work_experience && app.work_experience.length > 0) {
            app.work_experience.forEach(w => {
                expList.innerHTML += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">${w.position || 'Staff'} at ${w.company || ''}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">${w.start_date || ''} to ${w.end_date || 'Present'}</div>
                        ${w.responsibilities ? `<div style="font-size: 12.5px; color: #334155; margin-top: 6px;">${w.responsibilities}</div>` : ''}
                    </div>
                `;
            });
        } else {
            expList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No previous work history registered.</div>';
        }

        // Render Education
        const eduList = document.getElementById('drawerEducationList');
        eduList.innerHTML = '';
        if (app.education && app.education.length > 0) {
            app.education.forEach(e => {
                eduList.innerHTML += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">${e.degree || 'Degree'} ${e.course ? '- ' + e.course : ''}</div>
                        <div style="font-size: 12px; color: #64748b;">${e.school || 'University'} (Graduated ${e.year_graduated || 'N/A'})</div>
                    </div>
                `;
            });
        } else {
            eduList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No tertiary education registered.</div>';
        }

        // Render Interviews List
        const intList = document.getElementById('drawerInterviewsList');
        intList.innerHTML = '';
        document.getElementById('drawerScheduleInterviewBtn').onclick = function() {
            openScheduleInterviewFor(app.id, data.full_name, app.applied_position);
        };

        if (data.interviews && data.interviews.length > 0) {
            data.interviews.forEach(int => {
                intList.innerHTML += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #0f172a; font-size: 14px;">${int.interview_stage} (${int.interview_type})</strong>
                            <span class="hr-badge ${int.status === 'Completed' ? 'hr-badge-success' : 'hr-badge-warning'}">${int.status}</span>
                        </div>
                        <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                            <i class="ph ph-calendar"></i> ${int.interview_date ? int.interview_date.substring(0, 10) : ''} ${int.interview_time || ''} • Interviewer: <strong>${int.interviewer_name || 'HR'}</strong>
                        </div>
                        ${int.rating ? `
                            <div style="margin-top: 8px; padding: 8px 12px; background: #fffbeb; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-weight: 700; color: #b45309; font-size: 13px;">Overall Rating: ★ ${int.rating}/5</span>
                                <span class="hr-badge hr-badge-neutral">${int.recommendation || 'Proceed'}</span>
                            </div>
                        ` : `
                            <div style="margin-top: 8px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openScorecardModal(${int.id}, '${data.full_name.replace(/'/g, "\\'")}')">
                                    <i class="ph ph-star"></i> Fill Scorecard
                                </button>
                            </div>
                        `}
                    </div>
                `;
            });
        } else {
            intList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px; text-align: center; padding: 24px;">No interviews scheduled yet.</div>';
        }

        // Render Assessments List
        const assList = document.getElementById('drawerAssessmentsList');
        assList.innerHTML = '';
        document.getElementById('drawerAddAssessmentBtn').onclick = function() {
            openAssessmentModal(app.id, data.full_name);
        };

        if (data.assessments && data.assessments.length > 0) {
            data.assessments.forEach(ass => {
                assList.innerHTML += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #0f172a; font-size: 14px;">${ass.title}</strong>
                            <span class="hr-badge ${ass.result === 'Passed' ? 'hr-badge-success' : 'hr-badge-danger'}">${ass.result}</span>
                        </div>
                        <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                            Score: <strong>${ass.score} / ${ass.passing_score}</strong> (Passing: ${ass.passing_score}) • Evaluator: ${ass.evaluator_name || 'Lead Trainer'}
                        </div>
                    </div>
                `;
            });
        } else {
            assList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px; text-align: center; padding: 24px;">No assessments recorded yet.</div>';
        }

        // Render Screening Tab Content
        const scContent = document.getElementById('drawerScreeningContent');
        scContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <span class="hr-badge ${app.screening_data?.decision === 'Shortlist' ? 'hr-badge-success' : 'hr-badge-neutral'}">
                    Decision: ${app.screening_data?.decision || 'Pending Review'}
                </span>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openScreeningModal(${app.id}, '${data.full_name.replace(/'/g, "\\'")}')">
                    <i class="ph ph-pencil"></i> Update Screening
                </button>
            </div>
            <div style="font-size: 13px; color: #334155; line-height: 1.6;">
                <strong>Notes:</strong> ${app.screening_data?.remarks || 'Candidate meets general education and experience background criteria.'}
            </div>
        `;

        // Render Final Review Content
        const frContent = document.getElementById('drawerFinalReviewContent');
        frContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <span class="hr-badge ${app.final_review_data?.decision === 'Hire' ? 'hr-badge-success' : 'hr-badge-neutral'}">
                    Decision: ${app.final_review_data?.decision || 'Awaiting Final Review'}
                </span>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openFinalReviewModal(${app.id}, '${data.full_name.replace(/'/g, "\\'")}', ${app.desired_salary || 25000})">
                    <i class="ph ph-scales"></i> Perform Final Review
                </button>
            </div>
            <div class="hr-offer-details-grid" style="margin-top: 10px;">
                <div><small style="color: #64748b;">Recruiter Endorsement:</small><br><strong>${app.final_review_data?.recruiter_recommendation || 'Recommended'}</strong></div>
                <div><small style="color: #64748b;">Hiring Manager:</small><br><strong>${app.final_review_data?.hiring_manager_recommendation || 'Endorsed for hire'}</strong></div>
                <div><small style="color: #64748b;">Proposed Compensation:</small><br><strong>₱${Number(app.final_review_data?.proposed_salary || app.desired_salary || 25000).toLocaleString()} /mo</strong></div>
                <div><small style="color: #64748b;">Reference Checks:</small><br><strong>${app.final_review_data?.reference_results || 'Clear & Verified'}</strong></div>
            </div>
        `;

        // Render Job Offer Preview
        const offContent = document.getElementById('drawerOfferContent');
        const off = app.offer_data || {};
        offContent.innerHTML = `
            <div class="hr-offer-paper">
                <div class="hr-offer-header">
                    <div>
                        <div class="hr-offer-title">FORMAL EMPLOYMENT OFFER LETTER</div>
                        <small style="color: #64748b;">Restaurant Operations & Hospitality Group</small>
                    </div>
                    <span class="hr-badge ${off.offer_status === 'Accepted' ? 'hr-badge-success' : 'hr-badge-purple'}" style="font-size: 13px; font-weight: 700;">
                        Status: ${off.offer_status || 'Draft'}
                    </span>
                </div>
                <p>Dear <strong>${data.full_name}</strong>,</p>
                <p>We are pleased to extend an offer of employment with our restaurant organization for the position of <strong>${off.position || app.applied_position}</strong>.</p>
                
                <div class="hr-offer-details-grid">
                    <div><small style="color: #64748b;">Position:</small><br><strong>${off.position || app.applied_position}</strong></div>
                    <div><small style="color: #64748b;">Employment Type:</small><br><strong>${off.employment_type || 'Regular'}</strong></div>
                    <div><small style="color: #64748b;">Target Start Date:</small><br><strong>${off.start_date || 'October 05, 2026'}</strong></div>
                    <div><small style="color: #64748b;">Probationary Period:</small><br><strong>${off.probation_period || '6 Months'}</strong></div>
                    <div><small style="color: #64748b;">Basic Monthly Salary:</small><br><strong style="color: #059669; font-size: 15px;">₱${Number(off.basic_salary || 25000).toLocaleString()}</strong></div>
                    <div><small style="color: #64748b;">Allowances & Perks:</small><br><strong>₱${Number(off.allowances || 2000).toLocaleString()} /mo</strong></div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 18px; justify-content: flex-end;">
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/offer-status" style="display: inline;">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="action" value="send">
                        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm"><i class="ph ph-paper-plane-tilt"></i> Send Offer</button>
                    </form>
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/offer-status" style="display: inline;">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="hr-btn hr-btn-success hr-btn-sm"><i class="ph ph-check"></i> Record Acceptance</button>
                    </form>
                </div>
            </div>
        `;

        // Render Pre-Employment Requirements
        const reqList = document.getElementById('drawerRequirementsList');
        reqList.innerHTML = '';
        (data.pre_employment_requirements || []).forEach(req => {
            reqList.innerHTML += `
                <div class="hr-checklist-item">
                    <div class="hr-checklist-info">
                        <div class="hr-checklist-icon" style="background: ${req.status === 'Approved' ? 'rgba(16,185,129,0.15)' : 'rgba(148,163,184,0.15)'}; color: ${req.status === 'Approved' ? '#059669' : '#64748b'};">
                            <i class="ph ${req.status === 'Approved' ? 'ph-check-circle' : 'ph-file-text'}"></i>
                        </div>
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px;">${req.name}</strong>
                            <div style="font-size: 11.5px; color: #64748b;">${req.description || ''}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="hr-badge ${req.status === 'Approved' ? 'hr-badge-success' : (req.status === 'Submitted' ? 'hr-badge-info' : 'hr-badge-neutral')}">${req.status}</span>
                        <form method="POST" action="/hr/recruitment/applicants/${app.id}/requirements" style="display: inline;">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="req_id" value="${req.id}">
                            <input type="hidden" name="status" value="${req.status === 'Approved' ? 'Pending' : 'Approved'}">
                            <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="Toggle Approve">
                                <i class="ph ${req.status === 'Approved' ? 'ph-arrow-counter-clockwise' : 'ph-check'}"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `;
        });

        // Render Preboarding Tasks
        const pbList = document.getElementById('drawerPreboardingList');
        pbList.innerHTML = '';
        (data.preboarding_tasks || []).forEach(t => {
            const rawDept = (t.department || 'HR').trim();
            const deptUpper = rawDept.toUpperCase();
            
            let pillClass = 'hr-dept-pill-hr';
            let pillIcon = 'ph-users';
            
            if (deptUpper === 'IT') {
                pillClass = 'hr-dept-pill-it';
                pillIcon = 'ph-cpu';
            } else if (deptUpper === 'FINANCE' || deptUpper === 'FIN') {
                pillClass = 'hr-dept-pill-finance';
                pillIcon = 'ph-coins';
            } else if (deptUpper === 'MANAGEMENT' || deptUpper === 'ADMIN') {
                pillClass = 'hr-dept-pill-mgmt';
                pillIcon = 'ph-briefcase';
            } else if (deptUpper === 'FOH' || deptUpper === 'FRONT OF HOUSE') {
                pillClass = 'hr-dept-pill-foh';
                pillIcon = 'ph-storefront';
            } else if (deptUpper === 'BOH' || deptUpper === 'BACK OF HOUSE' || deptUpper === 'KITCHEN') {
                pillClass = 'hr-dept-pill-boh';
                pillIcon = 'ph-cooking-pot';
            }

            const isDone = t.status === 'Completed';

            pbList.innerHTML += `
                <div class="hr-checklist-item">
                    <div class="hr-checklist-info">
                        <span class="hr-dept-pill ${pillClass}" title="Department: ${rawDept}">
                            <i class="ph ${pillIcon}"></i>
                            <span>${rawDept}</span>
                        </span>
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px; display: block; line-height: 1.3;">${t.task_name}</strong>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">${t.description}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="hr-badge ${isDone ? 'hr-badge-success' : 'hr-badge-warning'}">
                            <i class="ph ${isDone ? 'ph-check-circle' : 'ph-clock'}"></i>
                            ${t.status}
                        </span>
                        <form method="POST" action="/hr/recruitment/applicants/${app.id}/preboarding" style="display: inline;">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="task_id" value="${t.id}">
                            <input type="hidden" name="status" value="${isDone ? 'Pending' : 'Completed'}">
                            <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="${isDone ? 'Mark as Pending' : 'Mark as Completed'}">
                                <i class="ph ${isDone ? 'ph-arrow-counter-clockwise' : 'ph-check'}"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `;
        });

        // Render Onboarding Tab
        const onbContent = document.getElementById('drawerOnboardingContent');
        const onb = app.onboarding_data || {};
        onbContent.innerHTML = `
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Employee Onboarding Tracking</h3>
                        <small style="color: #64748b;">First-Day Checklist, IT Provisioning, and Food Safety Compliance</small>
                    </div>
                    <span class="hr-badge hr-badge-success" style="font-size: 14px; font-weight: 800; padding: 6px 14px;">
                        ${onb.progress || 65}% Complete
                    </span>
                </div>
                <div style="width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin: 14px 0;">
                    <div style="height: 100%; width: ${onb.progress || 65}%; background: linear-gradient(90deg, #10b981, #059669); border-radius: 4px;"></div>
                </div>

                <div class="hr-offer-details-grid" style="margin-top: 14px;">
                    <div><small style="color: #64748b;">Employee Name:</small><br><strong>${data.full_name}</strong></div>
                    <div><small style="color: #64748b;">Designated Role:</small><br><strong>${app.applied_position}</strong></div>
                    <div><small style="color: #64748b;">Assigned Branch:</small><br><strong>${app.jobVacancy?.branch?.name || 'Main Branch'}</strong></div>
                    <div><small style="color: #64748b;">Start Date:</small><br><strong>${onb.start_date || 'October 05, 2026'}</strong></div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/onboarding" style="display: inline;">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="complete_onboarding" value="1">
                        <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #10b981, #047857);">
                            <i class="ph ph-seal-check"></i> Complete Onboarding (100%) & Activate Across HRIS
                        </button>
                    </form>
                </div>
            </div>
        `;
    }

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
