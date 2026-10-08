@extends('layouts.portal')

@section('title', 'User Management')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage team member credentials, security roles, and platform operational permissions</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="{{ route('portal.permissions') }}" class="btn btn-secondary" title="View & Configure Role Permissions">
            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Access Permissions</span>
        </a>

        <button type="button" class="btn btn-primary" onclick="openModal('modal-create-user')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Add Team Member</span>
        </button>
    </div>
</div>

<!-- Stats Overview Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon indigo">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <span class="stat-trend up">
                <span>{{ $stats['active'] }} Active</span>
            </span>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Team Members</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon violet">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; font-weight: 700;">Super Admin</span>
        </div>
        <div class="stat-value">{{ $stats['admins'] }}</div>
        <div class="stat-label">System Administrators</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <span class="badge" style="background: var(--warning-bg); color: var(--warning); font-weight: 700;">Crawlers</span>
        </div>
        <div class="stat-value">{{ $stats['operators'] }}</div>
        <div class="stat-label">Scraper Operators</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon emerald">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <span class="badge" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; font-weight: 700;">Intelligence</span>
        </div>
        <div class="stat-value">{{ $stats['analysts'] }}</div>
        <div class="stat-label">Real Estate Analysts</div>
    </div>
</div>

<!-- Search & Filtering Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1rem 1.25rem;">
        <form action="{{ route('portal.users') }}" method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <div style="flex: 2; min-width: 220px; position: relative;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Search by name, email, job title, or role..." 
                    style="padding-left: 2.35rem;"
                >
            </div>

            <div style="flex: 1; min-width: 160px;">
                <select name="role_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Security Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }} ({{ $role->users_count }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Active Accounts</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>🔴 Suspended</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request()->anyFilled(['search', 'role_id', 'status']))
                    <a href="{{ route('portal.users') }}" class="btn btn-secondary" title="Reset Filters">&times; Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Users Table Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Team Directory ({{ $users->total() }} accounts)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User Profile</th>
                    <th>Role & Job Title</th>
                    <th>Status</th>
                    <th>Contributions</th>
                    <th>Contact Phone</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <!-- User Profile -->
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img 
                                    src="{{ $user->avatar_url }}" 
                                    alt="{{ $user->name }}" 
                                    style="width: 40px; height: 40px; border-radius: var(--radius-full); object-fit: cover; border: 2px solid var(--border-color); flex-shrink: 0;"
                                >
                                <div style="min-width: 0;">
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.925rem;" class="font-khmer">
                                        {{ $user->name }}
                                        @if($user->id === Auth::id())
                                            <span style="font-size: 0.7rem; font-weight: 700; color: var(--primary); background: var(--primary-light); padding: 1px 6px; border-radius: var(--radius-full); margin-left: 4px;">You</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.785rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $user->email }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Role & Title -->
                        <td>
                            <div style="margin-bottom: 2px;">
                                <span class="badge" style="background: {{ $user->role_badge_color }}22; color: {{ $user->role_badge_color }}; font-weight: 700; border: 1px solid {{ $user->role_badge_color }}44;">
                                    {{ $user->roleModel ? $user->roleModel->name : $user->role }}
                                </span>
                            </div>
                            <div style="font-size: 0.775rem; color: var(--text-secondary); margin-top: 2px;">
                                {{ $user->title ?? 'Team Member' }}
                            </div>
                        </td>

                        <!-- Status -->
                        <td>
                            @if($user->isActive())
                                <span class="badge" style="background: var(--success-bg); color: var(--success); font-weight: 700;">
                                    <span class="badge-dot" style="background: var(--success);"></span>
                                    Active
                                </span>
                            @else
                                <span class="badge" style="background: var(--danger-bg); color: var(--danger); font-weight: 700;">
                                    <span class="badge-dot" style="background: var(--danger);"></span>
                                    Suspended
                                </span>
                            @endif
                        </td>

                        <!-- Contributions -->
                        <td>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 2px;">
                                <span>🏷️ <strong>{{ number_format($user->properties_count) }}</strong> listings</span>
                                <span>🕷️ <strong>{{ number_format($user->scraper_tasks_count) }}</strong> scrapers</span>
                            </div>
                        </td>

                        <!-- Phone -->
                        <td style="font-size: 0.825rem; color: var(--text-secondary);">
                            {{ $user->phone ?? '—' }}
                        </td>

                        <!-- Joined Date -->
                        <td style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                        </td>

                        <!-- Actions -->
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                <!-- Edit Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role-id="{{ $user->role_id ?? ($user->roleModel ? $user->roleModel->id : 1) }}"
                                    data-title="{{ $user->title }}"
                                    data-phone="{{ $user->phone }}"
                                    data-status="{{ $user->status }}"
                                    data-avatar="{{ $user->avatar }}"
                                    onclick="openEditUserModal(this)"
                                    title="Edit user details"
                                >
                                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit</span>
                                </button>

                                @if($user->id !== Auth::id())
                                    <!-- Toggle Status Button -->
                                    <form action="{{ route('portal.users.toggle-status', $user->id) }}" method="POST" style="margin:0;">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="btn btn-secondary btn-sm" 
                                            style="padding: 4px 8px;"
                                            title="{{ $user->isActive() ? 'Suspend user account' : 'Reactivate user account' }}"
                                        >
                                            @if($user->isActive())
                                                <svg style="width:14px;height:14px;color:var(--warning);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            @else
                                                <svg style="width:14px;height:14px;color:var(--success);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form action="{{ route('portal.users.delete', $user->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Permanently remove {{ $user->name }} from the portal?');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="btn btn-danger btn-sm" 
                                            style="padding: 4px 8px;"
                                            title="Delete account"
                                        >
                                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <svg style="width:40px;height:40px;margin: 0 auto 0.5rem;opacity:0.6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <div style="font-weight: 700; font-size: 0.95rem;">No users found matching your filters.</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">Try clearing your search keyword or selected security role.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

@section('modals')
<!-- Modal: Add New Team Member -->
<div class="modal-backdrop" id="modal-create-user">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Add Team Member</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-create-user')">&times;</button>
        </div>

        <form action="{{ route('portal.users.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. John Doe / ហេង ពិសិដ្ឋ" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Security Role *</label>
                        <select name="role_id" class="form-control" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active Account</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Job Title / Specialty</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Senior Portfolio Analyst">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Contact</label>
                        <input type="text" name="phone" class="form-control" placeholder="+855 12 345 678">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Password * (Minimum 8 Characters)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                </div>

                <div class="form-group">
                    <label class="form-label">Avatar Image URL (Optional)</label>
                    <input type="url" name="avatar" class="form-control" placeholder="https://example.com/avatar.jpg">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create-user')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Team Member -->
<div class="modal-backdrop" id="modal-edit-user">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Edit Team Member</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-edit-user')">&times;</button>
        </div>

        <form id="edit-user-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Security Role *</label>
                        <select name="role_id" id="edit-role-id" class="form-control" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <select name="status" id="edit-status" class="form-control">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="title" id="edit-title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Contact</label>
                        <input type="text" name="phone" id="edit-phone" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password (Leave blank to keep existing password)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="8">
                </div>

                <div class="form-group">
                    <label class="form-label">Avatar Image URL</label>
                    <input type="url" name="avatar" id="edit-avatar" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-user')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(btn) {
    const d = btn.dataset;
    const form = document.getElementById('edit-user-form');
    form.action = `{{ url('/portal/users') }}/${d.id}`;

    document.getElementById('edit-name').value = d.name || '';
    document.getElementById('edit-email').value = d.email || '';
    document.getElementById('edit-role-id').value = d.roleId || '';
    document.getElementById('edit-status').value = d.status || 'active';
    document.getElementById('edit-title').value = d.title || '';
    document.getElementById('edit-phone').value = d.phone || '';
    document.getElementById('edit-avatar').value = d.avatar || '';

    openModal('modal-edit-user');
}
</script>
@endsection
