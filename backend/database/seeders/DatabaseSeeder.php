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
        // Only seed if COTIA admin doesn't exist (prevents duplicate key errors on re-run)
        if (User::where('email', 'admin@cotia.africa')->exists()) {
            $this->command->info('Database already seeded, skipping.');
            return;
        }

        // --- COTIA: the platform super-admin, belongs to no organisation ---
        $superAdmin = User::create([
            'organization_id' => null,
            'name' => 'COTIA Platform Admin',
            'email' => 'admin@cotia.africa',
            'password' => Hash::make('ChangeMe!2026'),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        // --- First client organisation onboarded onto the platform: MECPA Uganda ---
        $organization = Organization::create([
            'name' => 'MECPA Uganda',
            'code' => 'mecpa-uganda',
            'contact_email' => 'info@mecpa.org',
            'primary_color' => '#0d1d2d',
            'secondary_color' => '#d9b15d',
            'created_by' => $superAdmin->id,
        ]);

        $ed = User::create([
            'organization_id' => $organization->id,
            'name' => 'Executive Director',
            'email' => 'ed@mecpa.org',
            'password' => Hash::make('ChangeMe!2026'),
            'role' => User::ROLE_ED,
        ]);

        $meo = User::create([
            'organization_id' => $organization->id,
            'name' => 'M&E Officer (demo)',
            'email' => 'meo@mecpa.org',
            'password' => Hash::make('ChangeMe!2026'),
            'role' => User::ROLE_MEO,
        ]);

        $po = User::create([
            'organization_id' => $organization->id,
            'name' => 'Project Officer (demo)',
            'email' => 'po@mecpa.org',
            'password' => Hash::make('ChangeMe!2026'),
            'role' => User::ROLE_PO,
            'supervisor_id' => $meo->id,
        ]);

        $fo = User::create([
            'organization_id' => $organization->id,
            'name' => 'Field Officer (demo)',
            'email' => 'fo@mecpa.org',
            'password' => Hash::make('ChangeMe!2026'),
            'role' => User::ROLE_FO,
            'supervisor_id' => $po->id,
        ]);

        // --- MECPA Uganda's first project ---
        $project = Project::create([
            'organization_id' => $organization->id,
            'code' => 'MECPA-REACT-2026',
            'name' => 'Women Leading Climate Resilience and Environmental Peacebuilding in Northern Uganda',
            'theme' => 'Empowering Rural Women for Climate Resilience, Food Security and Sustainable Livelihoods',
            'donor_funder' => 'To be confirmed',
            'start_date' => now(),
            'created_by' => $ed->id,
        ]);

        $project->officers()->sync([$meo->id, $po->id, $fo->id]);

        $schemaJson = file_get_contents(__DIR__.'/mecpa_form_schema.json');

        ProjectForm::create([
            'project_id' => $project->id,
            'title' => 'Household Climate Vulnerability Assessment',
            'slug' => 'household-climate-vulnerability-assessment',
            'version' => 1,
            'form_schema' => json_decode($schemaJson, true),
            'requires_consent' => true,
            'requires_signature' => true,
            'requires_id_capture' => true,
            'requires_photo' => true,
            'allows_voice_note' => true,
            'created_by' => $ed->id,
        ]);

        $this->command->info('Seeded COTIA super_admin (admin@cotia.africa) and MECPA Uganda as the first organisation, with its ED/M&E/PO/FO demo accounts. Password for all: ChangeMe!2026');
    }
}
