<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectForm;
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
            $organization = Organization::firstOrCreate(
                ['code' => 'mecpa-uganda'],
                [
                    'name' => 'MECPA Uganda',
                    'contact_email' => 'info@mecpa.org',
                    'primary_color' => '#0d1d2d',
                    'secondary_color' => '#d9b15d',
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

                ProjectForm::firstOrCreate(
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
            } else {
                $this->command->warn('⚠ Form schema file not found, skipping form creation');
            }

            $this->command->info('✅ Database seeding complete!');
        } catch (\Exception $e) {
            $this->command->error('❌ Seeding error: '.$e->getMessage());
            \Log::error('Seeder error', ['exception' => $e]);
        }
    }
}
