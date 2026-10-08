<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Roles
        $rolesData = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full administrative access to scrapers, listings, intelligence models, user accounts, and security controls.',
                'badge_color' => '#6366f1',
                'is_system' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Oversees real estate portfolio, valuation engines, team members, and initiates data collection runs.',
                'badge_color' => '#10b981',
                'is_system' => true,
            ],
            [
                'name' => 'Scraper Operator',
                'slug' => 'scraper_operator',
                'description' => 'Configures crawlers, executes portal scraping tasks, and inspects ingestion performance and error logs.',
                'badge_color' => '#f59e0b',
                'is_system' => true,
            ],
            [
                'name' => 'Real Estate Analyst',
                'slug' => 'analyst',
                'description' => 'Accesses market analytics, runs CMA valuations, models land prices, and discovers discounted investment deals.',
                'badge_color' => '#06b6d4',
                'is_system' => true,
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to browse properties catalog, explore GIS map, and view verified property specs.',
                'badge_color' => '#64748b',
                'is_system' => true,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['slug']] = Role::updateOrCreate(
                ['slug' => $r['slug']],
                $r
            );
        }

        // 2. Permissions
        $permissionsData = [
            // Scraper Hub
            [
                'name' => 'View Scraper Hub',
                'slug' => 'scrapers.view',
                'module' => 'Scraper Hub',
                'description' => 'Inspect active crawler tasks, schedules, and scraped item statistics.',
            ],
            [
                'name' => 'Execute Scrapers',
                'slug' => 'scrapers.trigger',
                'module' => 'Scraper Hub',
                'description' => 'Dispatch live scraper extraction pipelines across target portals.',
            ],
            [
                'name' => 'Manage Scraper Pipelines',
                'slug' => 'scrapers.manage',
                'module' => 'Scraper Hub',
                'description' => 'Configure new scraper crawlers, change targets, and delete tasks.',
            ],

            // Listings & GIS Map
            [
                'name' => 'View Listings & Map',
                'slug' => 'properties.view',
                'module' => 'Listings & GIS Map',
                'description' => 'Browse property catalog, search listings, and navigate interactive Google Map.',
            ],
            [
                'name' => 'Create Listings',
                'slug' => 'properties.create',
                'module' => 'Listings & GIS Map',
                'description' => 'Manually add new real estate property listings into the system.',
            ],
            [
                'name' => 'Edit Listings',
                'slug' => 'properties.edit',
                'module' => 'Listings & GIS Map',
                'description' => 'Modify prices, coordinates, images, and property specifications.',
            ],
            [
                'name' => 'Delete Listings',
                'slug' => 'properties.delete',
                'module' => 'Listings & GIS Map',
                'description' => 'Permanently remove property records from the database.',
            ],
            [
                'name' => 'Export Listings',
                'slug' => 'properties.export',
                'module' => 'Listings & GIS Map',
                'description' => 'Export listing datasets to CSV, Excel, or JSON formats.',
            ],

            // Valuation & Intelligence
            [
                'name' => 'Run CMA Valuation',
                'slug' => 'valuation.cma',
                'module' => 'Valuation & Intelligence',
                'description' => 'Generate Comparable Market Analysis (CMA) reports for properties.',
            ],
            [
                'name' => 'Access Land Estimator',
                'slug' => 'valuation.land',
                'module' => 'Valuation & Intelligence',
                'description' => 'Model land pricing using road multipliers and district $/sqm benchmarks.',
            ],
            [
                'name' => 'Access Deal Finder',
                'slug' => 'valuation.deals',
                'module' => 'Valuation & Intelligence',
                'description' => 'Filter urgency tags and detect listings priced significantly below district medians.',
            ],

            // User Management
            [
                'name' => 'View Users',
                'slug' => 'users.view',
                'module' => 'User Management',
                'description' => 'Inspect team member profiles, activity history, and assigned roles.',
            ],
            [
                'name' => 'Create Users',
                'slug' => 'users.create',
                'module' => 'User Management',
                'description' => 'Invite, register, and onboard new portal accounts.',
            ],
            [
                'name' => 'Edit Users',
                'slug' => 'users.edit',
                'module' => 'User Management',
                'description' => 'Update user roles, job titles, passwords, and profile details.',
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'users.delete',
                'module' => 'User Management',
                'description' => 'Suspend or delete user accounts from the portal.',
            ],

            // Access Control & Security
            [
                'name' => 'View Permissions',
                'slug' => 'permissions.view',
                'module' => 'Access Control & Security',
                'description' => 'View roles and permission access matrix.',
            ],
            [
                'name' => 'Manage Permissions',
                'slug' => 'permissions.manage',
                'module' => 'Access Control & Security',
                'description' => 'Assign, toggle, and configure granular permissions across roles.',
            ],
            [
                'name' => 'View Activity Logs',
                'slug' => 'logs.view',
                'module' => 'Access Control & Security',
                'description' => 'Audit security events, IP addresses, and user operational history.',
            ],
        ];

        $permissions = [];
        foreach ($permissionsData as $p) {
            $permissions[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }

        // 3. Assign Permissions to Roles
        // Admin: All permissions
        $allPermissionIds = Permission::pluck('id')->toArray();
        $roles['admin']->permissions()->sync($allPermissionIds);

        // Manager
        $managerPerms = Permission::whereIn('slug', [
            'scrapers.view', 'scrapers.trigger',
            'properties.view', 'properties.create', 'properties.edit', 'properties.export',
            'valuation.cma', 'valuation.land', 'valuation.deals',
            'users.view', 'logs.view',
        ])->pluck('id')->toArray();
        $roles['manager']->permissions()->sync($managerPerms);

        // Scraper Operator
        $operatorPerms = Permission::whereIn('slug', [
            'scrapers.view', 'scrapers.trigger', 'scrapers.manage',
            'properties.view', 'logs.view',
        ])->pluck('id')->toArray();
        $roles['scraper_operator']->permissions()->sync($operatorPerms);

        // Real Estate Analyst
        $analystPerms = Permission::whereIn('slug', [
            'properties.view', 'properties.export',
            'valuation.cma', 'valuation.land', 'valuation.deals',
        ])->pluck('id')->toArray();
        $roles['analyst']->permissions()->sync($analystPerms);

        // Viewer
        $viewerPerms = Permission::whereIn('slug', [
            'properties.view',
        ])->pluck('id')->toArray();
        $roles['viewer']->permissions()->sync($viewerPerms);

        // 4. Update existing users and seed extra team members
        // Alexander Vance -> Admin
        $adminUser = User::where('email', 'admin@portal.test')->first();
        if ($adminUser) {
            $adminUser->update([
                'role' => 'Administrator',
                'role_id' => $roles['admin']->id,
                'status' => 'active',
                'phone' => '+855 12 889 900',
            ]);
        }

        // Sarah Connor -> Scraper Operator
        $sarahUser = User::where('email', 'sarah@portal.test')->first();
        if ($sarahUser) {
            $sarahUser->update([
                'role' => 'Scraper Operator',
                'role_id' => $roles['scraper_operator']->id,
                'status' => 'active',
                'phone' => '+855 89 443 210',
            ]);
        }

        // Extra realistic team members
        $extraUsers = [
            [
                'name' => 'Sophia Chen',
                'email' => 'sophia@portal.test',
                'password' => Hash::make('password123'),
                'role' => 'Manager',
                'role_id' => $roles['manager']->id,
                'title' => 'Senior Portfolio Director',
                'phone' => '+855 10 334 556',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=256&q=80',
            ],
            [
                'name' => 'Dara Sok',
                'email' => 'dara@portal.test',
                'password' => Hash::make('password123'),
                'role' => 'Real Estate Analyst',
                'role_id' => $roles['analyst']->id,
                'title' => 'Phnom Penh Market Specialist',
                'phone' => '+855 77 654 321',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=256&q=80',
            ],
            [
                'name' => 'Elena Rostova',
                'email' => 'elena@portal.test',
                'password' => Hash::make('password123'),
                'role' => 'Viewer',
                'role_id' => $roles['viewer']->id,
                'title' => 'Client Representative',
                'phone' => '+855 92 112 334',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=256&q=80',
            ],
        ];

        foreach ($extraUsers as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }
    }
}
