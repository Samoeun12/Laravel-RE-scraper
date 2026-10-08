@extends('layouts.portal')

@section('title', 'Access Permissions')

@push('styles')
<style>
/* Custom Toggle Switch for RBAC Matrix */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 38px;
    height: 20px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--border-color);
    transition: all 0.2s ease;
    border-radius: 20px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: #ffffff;
    transition: all 0.2s ease;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
}

input:checked + .toggle-slider {
    background-color: var(--primary);
}

input:checked + .toggle-slider:before {
    transform: translateX(18px);
}

input:disabled + .toggle-slider {
    opacity: 0.6;
    cursor: not-allowed;
}

.role-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: var(--radius-full);
    font-size: 0.775rem;
    font-weight: 700;
}

.module-header-row td {
    background: var(--bg-surface-elevated) !important;
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--text-primary);
    padding: 0.75rem 1.25rem !important;
    border-top: 2px solid var(--border-color);
}
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Access Permissions & Roles</h1>
        <p class="page-subtitle">Configure granular Role-Based Access Control (RBAC), capability matrix, and security boundaries</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="{{ route('portal.users') }}" class="btn btn-secondary">
            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Team Members</span>
        </a>

        @if(Auth::user()->hasPermission('permissions.manage'))
            <button type="button" class="btn btn-primary" onclick="openModal('modal-create-role')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create Custom Role</span>
            </button>
        @endif
    </div>
</div>

<!-- Role Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    @foreach($roles as $role)
        <div class="card" style="position: relative; border-top: 3px solid {{ $role->badge_color }};">
            <div class="card-body" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <span class="role-pill-badge" style="background: {{ $role->badge_color }}20; color: {{ $role->badge_color }}; border: 1px solid {{ $role->badge_color }}40;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $role->badge_color }};"></span>
                        {{ $role->name }}
                    </span>

                    @if(!$role->is_system && Auth::user()->hasPermission('permissions.manage'))
                        <form action="{{ route('portal.permissions.roles.delete', $role->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Delete role {{ $role->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon" style="width:24px;height:24px;border:none;color:var(--text-muted);" title="Delete custom role">
                                &times;
                            </button>
                        </form>
                    @elseif($role->is_system)
                        <span title="System Default Role" style="font-size: 11px; color: var(--text-muted);">🔒</span>
                    @endif
                </div>

                <div style="font-size: 0.775rem; color: var(--text-secondary); margin-bottom: 1rem; min-height: 38px; line-height: 1.4;">
                    {{ $role->description ?? 'Custom security group.' }}
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.75rem;">
                    <span style="color: var(--text-muted);">
                        👥 <strong>{{ $role->users->count() }}</strong> {{ Str::plural('user', $role->users->count()) }}
                    </span>
                    <span style="color: var(--primary); font-weight: 700;">
                        @if($role->slug === 'admin')
                            All (Full Access)
                        @else
                            {{ $role->permissions->count() }} Perms
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- RBAC Permissions Matrix Card -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">
                <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Interactive Permission Matrix</span>
            </div>
            <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 2px;">
                Click any toggle switch to grant or revoke real-time privileges for that role.
            </div>
        </div>

        <span class="badge" style="background: var(--success-bg); color: var(--success); font-weight: 700;">
            Live Auto-Sync
        </span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="min-width: 260px;">Module & Capability</th>
                    @foreach($roles as $role)
                        <th style="text-align: center; min-width: 130px;">
                            <div style="font-weight: 800; color: {{ $role->badge_color }};">
                                {{ $role->name }}
                            </div>
                            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: lowercase;">
                                {{ $role->slug }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $moduleName => $modulePerms)
                    <!-- Module Section Header -->
                    <tr class="module-header-row">
                        <td colspan="{{ 1 + $roles->count() }}">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @if($moduleName === 'Scraper Hub')
                                    🕷️
                                @elseif($moduleName === 'Listings & GIS Map')
                                    🗺️
                                @elseif($moduleName === 'Valuation & Intelligence')
                                    📈
                                @elseif($moduleName === 'User Management')
                                    👥
                                @else
                                    🛡️
                                @endif
                                <span>{{ $moduleName }}</span>
                                <span style="font-size: 0.7rem; font-weight: 600; color: var(--text-muted);">({{ $modulePerms->count() }} capabilities)</span>
                            </div>
                        </td>
                    </tr>

                    @foreach($modulePerms as $perm)
                        <tr>
                            <!-- Capability Info -->
                            <td>
                                <div style="font-weight: 700; color: var(--text-primary);">{{ $perm->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    {{ $perm->description }}
                                </div>
                                <code style="font-size: 0.675rem; color: var(--primary); background: var(--bg-surface-elevated); padding: 1px 4px; border-radius: 4px; margin-top: 4px; display: inline-block;">
                                    {{ $perm->slug }}
                                </code>
                            </td>

                            <!-- Role Toggle Switches -->
                            @foreach($roles as $role)
                                <td style="text-align: center; vertical-align: middle;">
                                    @if($role->slug === 'admin')
                                        <!-- Administrator inherently has all permissions locked on -->
                                        <div title="Full Administrator Immunity" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: rgba(99, 102, 241, 0.12); color: var(--primary); margin: 0 auto;">
                                            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    @else
                                        @php
                                            $isGranted = $role->permissions->contains('id', $perm->id);
                                        @endphp
                                        <label class="toggle-switch" title="Toggle {{ $perm->name }} for {{ $role->name }}">
                                            <input 
                                                type="checkbox" 
                                                {{ $isGranted ? 'checked' : '' }}
                                                {{ !Auth::user()->hasPermission('permissions.manage') ? 'disabled' : '' }}
                                                onchange="togglePermission({{ $role->id }}, {{ $perm->id }}, this.checked, this)"
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('modals')
<!-- Modal: Create Custom Role -->
<div class="modal-backdrop" id="modal-create-role">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Create Custom Security Role</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-create-role')">&times;</button>
        </div>

        <form action="{{ route('portal.permissions.roles.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role Display Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Compliance Auditor / Property Agent" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Badge Accent Color</label>
                        <select name="badge_color" class="form-control">
                            <option value="#6366f1">Indigo (Default)</option>
                            <option value="#10b981">Emerald Green</option>
                            <option value="#f59e0b">Amber Gold</option>
                            <option value="#06b6d4">Cyan Teal</option>
                            <option value="#ec4899">Rose Pink</option>
                            <option value="#8b5cf6">Purple Violet</option>
                            <option value="#64748b">Slate Gray</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Role Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief explanation of duties and access permissions for this role..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Granted Permissions</label>
                    <div style="max-height: 240px; overflow-y: auto; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                        @foreach($permissions as $modName => $modPerms)
                            <div style="font-size: 0.775rem; font-weight: 800; color: var(--text-primary); margin: 0.5rem 0 0.25rem; padding-bottom: 2px; border-bottom: 1px solid var(--border-color);">
                                {{ $modName }}
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem;">
                                @foreach($modPerms as $perm)
                                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}">
                                        <span>{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create-role')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePermission(roleId, permissionId, isGranted, el) {
    fetch('{{ route('portal.permissions.matrix') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            role_id: roleId,
            permission_id: permissionId,
            granted: isGranted ? 1 : 0
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            }
        } else {
            alert(data.message || 'Failed to update permission');
            el.checked = !isGranted;
        }
    })
    .catch(err => {
        console.error('Permission Toggle Error:', err);
        alert('Network error updating permission');
        el.checked = !isGranted;
    });
}
</script>
@endsection
