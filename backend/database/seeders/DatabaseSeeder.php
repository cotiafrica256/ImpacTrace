<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectForm;
use App\Models\Respondent;
use App\Models\FormSubmission;
use App\Models\Consent;
use App\Models\Report;
use App\Models\GeographicUnit;
use App\Models\DevelopmentPlan;
use App\Models\AdvocacyIssue;
use App\Models\Publication;
use App\Models\AccessPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // --- COTIA: the platform super-admin, belongs to no organisation ---
            $superAdmin = User::updateOrCreate(
                ['email' => 'admin@cotia.africa'],
                [
                    'organization_id' => null,
                    'name' => 'COTIA Platform Admin',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role' => User::ROLE_SUPER_ADMIN,
                    'is_active' => true,
                ]
            );
            $this->command->info('✓ COTIA admin (admin@cotia.africa)');

            // --- First client organisation onboarded onto the platform: MECPA Uganda ---
            $organization = Organization::updateOrCreate(
                ['code' => 'mecpa-uganda'],
                [
                    'name' => 'MECPA Uganda',
                    'contact_email' => 'info@mecpa.org',
                    'created_by' => $superAdmin->id,
                ]
            );
            $this->command->info('✓ MECPA Uganda organization');

            $ed = User::updateOrCreate(
                ['email' => 'ed@mecpa.org'],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Executive Director',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role' => User::ROLE_ED,
                    'is_active' => true,
                ]
            );
            $this->command->info('✓ ED (ed@mecpa.org)');

            $meo = User::updateOrCreate(
                ['email' => 'meo@mecpa.org'],
                [
                    'organization_id' => $organization->id,
                    'name' => 'M&E Officer (demo)',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role' => User::ROLE_MEO,
                    'is_active' => true,
                ]
            );
            $this->command->info('✓ M&E Officer (meo@mecpa.org)');

            $po = User::updateOrCreate(
                ['email' => 'po@mecpa.org'],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Project Officer (demo)',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role' => User::ROLE_PO,
                    'supervisor_id' => $meo->id,
                    'is_active' => true,
                ]
            );
            $this->command->info('✓ Project Officer (po@mecpa.org)');

            $fo = User::updateOrCreate(
                ['email' => 'fo@mecpa.org'],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Field Officer (demo)',
                    'password' => Hash::make('ChangeMe!2026'),
                    'role' => User::ROLE_FO,
                    'supervisor_id' => $po->id,
                    'is_active' => true,
                ]
            );
            $this->command->info('✓ Field Officer (fo@mecpa.org)');

            // --- MECPA Uganda's first project ---
            $project = Project::firstOrCreate(
                ['code' => 'MECPA-REACT-2026'],
                [
                    'organization_id' => $organization->id,
                    'name' => 'Women Leading Climate Resilience and Environmental Peacebuilding in Northern Uganda',
                    'theme' => 'Empowering Rural Women for Climate Resilience, Food Security and Sustainable Livelihoods',
                    'donor_funder' => 'To be confirmed',
                    'start_date' => now(),
                    'created_by' => $ed->id,
                ]
            );
            $this->command->info('✓ MECPA Project');

            // Sync officers to project
            $project->officers()->syncWithoutDetaching([$meo->id, $po->id, $fo->id]);

            // Load and create form schema
            $schemaPath = __DIR__.'/mecpa_form_schema.json';
            if (file_exists($schemaPath)) {
                $schemaJson = file_get_contents($schemaPath);
                $schema = json_decode($schemaJson, true);

                $form = ProjectForm::firstOrCreate(
                    ['project_id' => $project->id],
                    [
                        'title' => 'Household Climate Vulnerability Assessment',
                        'slug' => 'household-climate-vulnerability-assessment',
                        'version' => 1,
                        'form_schema' => $schema,
                        'requires_consent' => true,
                        'requires_signature' => true,
                        'requires_id_capture' => true,
                        'requires_photo' => true,
                        'allows_voice_note' => true,
                        'created_by' => $ed->id,
                    ]
                );
                $this->command->info('✓ MECPA Form');

                $respondents = [
                    ['code' => 'RSP-2026-0001', 'name' => 'Amina Lakare', 'sex' => 'female', 'age' => 34, 'id' => 'CM-UG-0001', 'village' => 'Awere', 'parish' => 'Orom', 'sub_county' => 'Awach', 'district' => 'Gulu', 'phone' => '0700000001'],
                    ['code' => 'RSP-2026-0002', 'name' => 'Grace Atim', 'sex' => 'female', 'age' => 42, 'id' => 'CM-UG-0002', 'village' => 'Awere', 'parish' => 'Orom', 'sub_county' => 'Awach', 'district' => 'Gulu', 'phone' => '0700000002'],
                    ['code' => 'RSP-2026-0003', 'name' => 'Okello Martin', 'sex' => 'male', 'age' => 51, 'id' => 'CM-UG-0003', 'village' => 'Oromo', 'parish' => 'Laliya', 'sub_county' => 'Unyama', 'district' => 'Gulu', 'phone' => '0700000003'],
                    ['code' => 'RSP-2026-0004', 'name' => 'Beatrice Namukasa', 'sex' => 'female', 'age' => 29, 'id' => 'CM-UG-0004', 'village' => 'Oromo', 'parish' => 'Laliya', 'sub_county' => 'Unyama', 'district' => 'Gulu', 'phone' => '0700000004'],
                    ['code' => 'RSP-2026-0005', 'name' => 'Sarah Achieng', 'sex' => 'female', 'age' => 38, 'id' => 'CM-UG-0005', 'village' => 'Laliya', 'parish' => 'Palaro', 'sub_county' => 'Awach', 'district' => 'Gulu', 'phone' => '0700000005'],
                ];

                foreach ($respondents as $index => $row) {
                    $respondent = Respondent::updateOrCreate(
                        ['respondent_code' => $row['code']],
                        [
                            'organization_id' => $organization->id, 'full_name' => $row['name'], 'sex' => $row['sex'], 'age' => $row['age'],
                            'id_type' => 'national_id', 'id_number_hash' => hash('sha256', 'mecpa-id::'.strtoupper($row['id'])), 'id_number_last4' => substr($row['id'], -4),
                            'fuzzy_key' => strtolower($row['name']).'|'.$row['age'].'|'.strtolower($row['village']), 'village' => $row['village'], 'parish' => $row['parish'],
                            'sub_county' => $row['sub_county'], 'district' => $row['district'], 'phone' => $row['phone'],
                        ]
                    );
                    $score = [36, 44, 58, 41, 47][$index];
                    $date = '2026-08-'.str_pad((string) (5 + ($index * 4)), 2, '0', STR_PAD_LEFT);
                    $submission = FormSubmission::updateOrCreate(
                        ['submission_code' => 'SUB-2026-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT)],
                        [
                            'project_id' => $project->id, 'project_form_id' => $form->id, 'respondent_id' => $respondent->id, 'collected_by' => $fo->id,
                            'activity_date' => $date, 'village' => $row['village'], 'parish' => $row['parish'], 'sub_county' => $row['sub_county'], 'district' => $row['district'],
                            'gps_lat' => 2.7746 + ($index * 0.004), 'gps_lng' => 32.2990 + ($index * 0.003),
                            'answers' => ['a1_date' => $date, 'a1_village' => $row['village'], 'a2_name' => $row['name'], 'a2_sex' => $row['sex'], 'a2_age' => $row['age'], 'o_total_score' => $score, 'o_vulnerability_class' => $score >= 50 ? 'High' : 'Moderate'],
                            'vulnerability_score' => $score, 'vulnerability_class' => $score >= 50 ? 'High' : 'Moderate', 'status' => $index < 2 ? 'approved' : 'reviewed',
                            'reviewed_by' => $meo->id, 'review_notes' => 'Demo record reviewed for training.', 'synced_at' => $date.' 10:30:00',
                        ]
                    );
                    Consent::updateOrCreate(['form_submission_id' => $submission->id], ['consent_given' => true, 'permission_for_learning_advocacy' => true, 'permission_for_photos' => true, 'consent_statement_version' => 'v1.0-demo', 'captured_at' => $date.' 10:15:00']);
                }

                $stats = ['total_submissions' => 5, 'unique_respondents' => 5, 'by_sex' => ['female' => 4, 'male' => 1], 'by_village_top10' => ['Awere' => 2, 'Oromo' => 2, 'Laliya' => 1], 'by_field_officer' => [$fo->name => 5], 'vulnerability_distribution' => ['Moderate' => 4, 'High' => 1], 'average_vulnerability_score' => 45.2, 'period' => ['from' => '2026-08-01', 'to' => '2026-08-31']];
                $report = Report::updateOrCreate(
                    ['project_id' => $project->id, 'type' => 'monthly_me', 'period_start' => '2026-08-01', 'period_end' => '2026-08-31'],
                    ['auto_stats' => $stats, 'narrative' => ['summary' => 'Five household assessments completed across three villages.', 'achievements' => 'Women-led households were reached through community mobilisers.', 'challenges' => 'Seasonal rainfall affected access to two villages.', 'lessons_learned' => 'Early village coordination improved interview completion.', 'next_steps' => 'Follow up on high-vulnerability households with parish leaders.'], 'status' => 'approved', 'prepared_by' => $meo->id, 'approved_by' => $ed->id]
                );
                $district = GeographicUnit::updateOrCreate(['code' => 'GULU'], ['type' => 'district', 'name' => 'Gulu District']);
                $parish = GeographicUnit::updateOrCreate(['code' => 'PALARO'], ['parent_id' => $district->id, 'type' => 'parish', 'name' => 'Palaro Parish']);
                DevelopmentPlan::updateOrCreate(['geographic_unit_id' => $district->id, 'title' => 'Gulu District Climate Resilience Plan'], ['year_from' => 2026, 'year_to' => 2030, 'content' => 'A practical plan for climate-smart livelihoods, water access, and women-led adaptation initiatives.', 'status' => 'published', 'created_by' => $ed->id]);
                AdvocacyIssue::updateOrCreate(['organization_id' => $organization->id, 'title' => 'Reliable water points for climate-vulnerable households'], ['project_id' => $project->id, 'geographic_unit_id' => $parish->id, 'problem' => 'Dry-season water access increases household workload and reduces time for livelihoods.', 'evidence' => 'Four of five demo households reported longer water collection times during the dry season.', 'community_voices' => 'Women requested repaired boreholes and transparent maintenance schedules.', 'recommendations' => 'Prioritise two borehole repairs and establish parish maintenance committees.', 'target_decision_maker' => 'Gulu District Local Government', 'status' => 'engagement']);
                $publication = Publication::updateOrCreate(['slug' => 'household-climate-vulnerability-brief-2026'], ['report_id' => $report->id, 'title' => 'Household Climate Vulnerability Brief 2026', 'summary' => 'Early evidence from Gulu households shows where climate resilience support can have the greatest effect.', 'content' => 'This demonstration brief summarises five household assessments and highlights practical priorities for local partners.', 'category' => 'Climate resilience', 'status' => 'published', 'is_featured' => true, 'published_by' => $ed->id, 'published_at' => '2026-09-01 09:00:00']);
                AccessPackage::updateOrCreate(['publication_id' => $publication->id, 'type' => 'reading', 'name' => '7-day reading access'], ['duration_minutes' => 10080, 'amount_ugx' => 5000, 'momo_amount_ugx' => 5000, 'allows_download' => false, 'is_active' => true]);
                AccessPackage::updateOrCreate(['publication_id' => $publication->id, 'type' => 'download', 'name' => 'Report download'], ['duration_minutes' => null, 'amount_ugx' => 15000, 'momo_amount_ugx' => 15000, 'allows_download' => true, 'is_active' => true]);
                $this->command->info('✓ Demo respondents, submissions, report, and public records');
            } else {
                $this->command->warn('⚠ Form schema file not found, skipping form creation');
            }

            $this->command->info('✅ Database seeding complete!');
        } catch (\Exception $e) {
            $this->command->error('❌ Seeding error: '.$e->getMessage());
            \Log::error('Seeder error', ['exception' => $e]);
            throw $e;
        }
    }
}
