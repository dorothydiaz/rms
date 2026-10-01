<?php

namespace Tests\Feature;

use App\Models\Hr\Applicant;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Interview;
use App\Models\Hr\JobVacancy;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TalentAcquisitionModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdminUser(): User
    {
        return User::where('username', 'peter')->first() ?: User::first();
    }

    /**
     * Test 1: All 3 primary tabs in Talent Acquisition render status 200
     */
    public function test_all_3_primary_talent_acquisition_pages_render_successfully(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'hr.recruitment.vacancies',
            'hr.recruitment.applicants',
            'hr.recruitment.interviews',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertStatus(200);
            $response->assertSee('Talent Acquisition');
        }
    }

    /**
     * Test 2: Vacancy CRUD, publishing, duplication, and statistics
     */
    public function test_job_vacancy_lifecycle(): void
    {
        $user = $this->getAdminUser();
        $branch = Branch::first();
        $dept = Department::first();
        $pos = Position::first();

        // 1. Create Vacancy
        $storeData = [
            'title' => 'Senior Head Chef - Italian Cuisine',
            'position_id' => $pos?->id,
            'department_id' => $dept?->id,
            'branch_id' => $branch?->id,
            'number_of_openings' => 2,
            'employment_type' => 'Regular',
            'work_setup' => 'On-site',
            'salary_range_min' => 35000,
            'salary_range_max' => 50000,
            'job_description' => 'Lead the culinary line and inventory management for fine dining.',
            'responsibilities' => 'Recipe development, kitchen hygiene, staff supervision.',
            'qualifications' => '5+ years culinary experience, culinary degree.',
            'required_skills' => 'Knife Skills, Italian Cooking, HACCP',
            'preferred_skills' => 'Sommelier knowledge',
            'benefits' => 'Health insurance, meal allowance, 13th month pay',
            'opening_date' => now()->toDateString(),
            'closing_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
        ];

        $res = $this->actingAs($user)->post(route('hr.recruitment.vacancies.store'), $storeData);
        $res->assertSessionHas('success');

        $vacancy = JobVacancy::where('title', 'Senior Head Chef - Italian Cuisine')->first();
        $this->assertNotNull($vacancy);
        $this->assertEquals('Draft', $vacancy->status);
        $this->assertEquals('On-site', $vacancy->work_setup);

        // 2. Publish Vacancy
        $this->actingAs($user)->post(route('hr.recruitment.vacancies.publish', $vacancy->id));
        $vacancy->refresh();
        $this->assertEquals('Open', $vacancy->status);

        // 3. Duplicate Vacancy
        $this->actingAs($user)->post(route('hr.recruitment.vacancies.duplicate', $vacancy->id));
        $clone = JobVacancy::where('title', 'like', '%[Copy] Senior Head Chef%')->first();
        $this->assertNotNull($clone);
        $this->assertEquals('Draft', $clone->status);

        // 4. Close Vacancy
        $this->actingAs($user)->post(route('hr.recruitment.vacancies.close', $vacancy->id));
        $vacancy->refresh();
        $this->assertEquals('Closed', $vacancy->status);
    }

    /**
     * Test 3: Candidate registration with comprehensive fields & JSON data endpoint
     */
    public function test_candidate_registration_and_data_endpoint(): void
    {
        $user = $this->getAdminUser();

        $appData = [
            'first_name' => 'Miguel',
            'middle_name' => 'Santos',
            'last_name' => 'Torres',
            'suffix' => 'Jr.',
            'preferred_name' => 'Miggy',
            'date_of_birth' => '1995-08-15',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'email' => 'miguel.torres@example.com',
            'contact_number' => '09171234567',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'address' => '123 Ayala Avenue',
            'applied_position' => 'Line Cook / Griller',
            'desired_salary' => 26000,
            'employment_type' => 'Regular',
            'available_start_date' => now()->addDays(14)->toDateString(),
            'years_of_experience' => '4 years',
            'work_setup_preference' => 'On-site',
            'source' => 'Walk-in',
            'application_date' => now()->toDateString(),
            'school' => 'De La Salle University - CSB',
            'degree' => 'Bachelor of Science',
            'course' => 'Culinary Arts & Kitchen Operations',
            'year_graduated' => '2018',
            'technical_skills' => 'Line Cooking, Grilling, Food Safety, HACCP',
            'soft_skills' => 'Punctual, Team Player',
            'q_why_join' => 'Passionate about culinary excellence in high-volume dining.',
            'q_relevant_exp' => '4 years as line cook and station grill chef.',
            'privacy_consent' => 1,
            'applicant_declaration' => 1,
        ];

        $res = $this->actingAs($user)->post(route('hr.recruitment.applicants.store'), $appData);
        $res->assertSessionHas('success');

        $applicant = Applicant::where('email', 'miguel.torres@example.com')->first();
        $this->assertNotNull($applicant);
        $this->assertEquals('Miguel Santos Torres Jr.', $applicant->full_name);
        $this->assertEquals('New', $applicant->status);
        $this->assertNotEmpty($applicant->activity_history);

        // Test JSON data API for drawer
        $jsonRes = $this->actingAs($user)->get(route('hr.recruitment.applicants.data', $applicant->id));
        $jsonRes->assertStatus(200);
        $jsonRes->assertJsonFragment(['full_name' => 'Miguel Santos Torres Jr.']);
    }

    /**
     * Test 4: Complete recruitment pipeline workflow:
     * Screening → Shortlisted → Interview (with Scorecard) → Assessment → Final Review → Job Offer → Acceptance → Pre-Employment
     */
    public function test_complete_recruitment_pipeline_workflow(): void
    {
        $user = $this->getAdminUser();

        // Create initial applicant
        $applicant = Applicant::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@test.com',
            'contact_number' => '09181112233',
            'applied_position' => 'Senior Barista',
            'desired_salary' => 24000,
            'source' => 'Online',
            'application_date' => now()->toDateString(),
            'status' => 'New',
        ]);

        // 1. Recruiter Screening
        $screenRes = $this->actingAs($user)->post(route('hr.recruitment.applicants.screening', $applicant->id), [
            'chk_education' => 'Pass',
            'chk_experience' => 'Pass',
            'chk_skills' => 'Pass',
            'chk_salary' => 'Pass',
            'chk_availability' => 'Pass',
            'chk_work_setup' => 'Pass',
            'chk_eligibility' => 'Pass',
            'decision' => 'Shortlist',
            'remarks' => 'Candidate has strong barista background and latte art portfolio.',
        ]);
        $screenRes->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('Shortlisted', $applicant->status);

        // 2. Schedule Interview
        $intRes = $this->actingAs($user)->post(route('hr.recruitment.interviews.store'), [
            'applicant_id' => $applicant->id,
            'interview_date' => now()->addDay()->toDateString(),
            'interview_time' => '14:00',
            'interview_type' => 'Face-to-face',
            'interview_stage' => 'Hiring Manager',
            'interviewer_id' => $user->id,
            'location_or_link' => 'Main Cafe Dining Area',
            'instructions' => 'Bring updated resume',
            'status' => 'Scheduled',
        ]);
        $intRes->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('Interview', $applicant->status);
        $interview = Interview::where('applicant_id', $applicant->id)->first();
        $this->assertNotNull($interview);

        // 3. Structured Interview Scorecard Evaluation (1-5 ratings across 6 criteria)
        $scoreRes = $this->actingAs($user)->post(route('hr.recruitment.interviews.evaluate', $interview->id), [
            'rating_communication' => 5,
            'rating_technical' => 4,
            'rating_experience' => 4,
            'rating_problem_solving' => 5,
            'rating_team_fit' => 5,
            'rating_overall' => 5,
            'recommendation' => 'Proceed',
            'notes' => 'Excellent customer interaction and coffee brewing knowledge.',
        ]);
        $scoreRes->assertSessionHas('success');

        $interview->refresh();
        $this->assertEquals('Completed', $interview->status);
        $this->assertEquals(5, $interview->rating);
        $this->assertNotNull($interview->scorecard);

        // 4. Record Assessment Result
        $assRes = $this->actingAs($user)->post(route('hr.recruitment.assessments.store'), [
            'applicant_id' => $applicant->id,
            'assessment_type' => 'Practical Test',
            'title' => 'Espresso Extraction & Milk Frothing Practical',
            'assessment_date' => now()->toDateString(),
            'score' => 92,
            'passing_score' => 75,
            'result' => 'Passed',
            'evaluator_id' => $user->id,
            'remarks' => 'Clean espresso workflow and consistent microfoam texture.',
        ]);
        $assRes->assertSessionHas('success');

        // 5. Final Hiring Review
        $revRes = $this->actingAs($user)->post(route('hr.recruitment.applicants.final-review', $applicant->id), [
            'recruiter_recommendation' => 'Endorsed for hire',
            'hiring_manager_recommendation' => 'Approved',
            'reference_results' => 'Positive feedback from previous coffee chain',
            'proposed_salary' => 25000,
            'decision' => 'Hire',
            'remarks' => 'Clear hire for Senior Barista role.',
        ]);
        $revRes->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('Offer', $applicant->status);
        $this->assertNotNull($applicant->offer_data);

        // 6. Job Offer Action (Send & Accept)
        $offerRes = $this->actingAs($user)->post(route('hr.recruitment.applicants.offer-status', $applicant->id), [
            'action' => 'accept',
        ]);
        $offerRes->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('Pre-Employment', $applicant->status);
        $this->assertEquals('Accepted', $applicant->offer_data['offer_status']);
        $this->assertNotEmpty($applicant->pre_employment_requirements);
        $this->assertNotEmpty($applicant->preboarding_tasks);
    }

    /**
     * Test 5: Convert Applicant to Employee:
     * Transfers personal info, contact, education, work experience, documents,
     * auto-generates EMP-YYYY-XXX, links hired_as_employee_id, initializes onboarding.
     */
    public function test_convert_applicant_to_employee_without_duplicate_data(): void
    {
        $user = $this->getAdminUser();
        $branch = Branch::first();
        $dept = Department::first();
        $pos = Position::first();

        $applicant = Applicant::create([
            'first_name' => 'Roberto',
            'middle_name' => 'Alcantara',
            'last_name' => 'Gomez',
            'suffix' => null,
            'email' => 'roberto.gomez@test.com',
            'contact_number' => '09199998877',
            'address' => '456 Buendia Ave, Makati City',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'applied_position' => 'Sous Chef',
            'desired_salary' => 32000,
            'employment_type' => 'Regular',
            'source' => 'Employee Referral',
            'application_date' => now()->toDateString(),
            'status' => 'Pre-Employment',
            'education' => [
                ['school' => 'Culinary Institute of the Philippines', 'degree' => 'Diploma in Culinary Arts', 'year_graduated' => '2019']
            ],
            'work_experience' => [
                ['company' => 'Bistro Group', 'position' => 'Chef de Partie', 'start_date' => '2020-01-01', 'end_date' => '2023-12-31', 'responsibilities' => 'Section lead']
            ],
        ]);

        $convertData = [
            'branch_id' => $branch->id,
            'department_id' => $dept?->id,
            'position_id' => $pos?->id,
            'date_hired' => now()->toDateString(),
            'employment_status' => 'Probationary',
            'employment_type' => 'Regular',
            'basic_salary' => 32000,
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'allowances' => 2500,
            'work_location' => $branch->name,
            'work_schedule' => 'Rotating Shifts',
        ];

        $res = $this->actingAs($user)->post(route('hr.recruitment.applicants.convert', $applicant->id), $convertData);
        $res->assertSessionHas('success');

        $applicant->refresh();
        $this->assertEquals('Hired', $applicant->status);
        $this->assertNotNull($applicant->hired_as_employee_id);

        // Verify created employee record
        $employee = Employee::find($applicant->hired_as_employee_id);
        $this->assertNotNull($employee);
        $this->assertEquals('Roberto', $employee->first_name);
        $this->assertEquals('Gomez', $employee->last_name);
        $this->assertEquals('roberto.gomez@test.com', $employee->email);
        $this->assertEquals('09199998877', $employee->mobile_number);
        $this->assertEquals(32000, (float) $employee->basic_salary);
        $this->assertStringStartsWith('EMP-', $employee->employee_id);

        // Verify Applicant -> Employee relationship
        $this->assertEquals($applicant->id, $employee->applicant->id);

        // Verify Onboarding record initialized
        $this->assertNotNull($applicant->onboarding_data);
        $this->assertEquals(65, $applicant->onboarding_data['progress']);

        // Complete Onboarding (100%) and activate employee
        $onbRes = $this->actingAs($user)->post(route('hr.recruitment.applicants.onboarding', $applicant->id), [
            'complete_onboarding' => 1,
        ]);
        $onbRes->assertSessionHas('success');

        $applicant->refresh();
        $employee->refresh();
        $this->assertEquals(100, $applicant->onboarding_data['progress']);
        $this->assertEquals('Active', $employee->employment_status);
    }
}
