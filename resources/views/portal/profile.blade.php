@extends('layouts.portal')

@section('title', 'Profile & Settings')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Account Settings & Preferences</h1>
        <p class="page-subtitle">Manage your profile, theme mode switcher, and security credentials</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Column 1: Appearance & Theme Mode Switcher -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <!-- Theme Preference Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>Appearance & Theme Switcher</span>
                </div>
            </div>
            <div class="card-body">
                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    Select your preferred interface display mode. Changes take effect immediately and are saved to your account.
                </p>

                <div class="theme-options-grid">
                    <!-- Light Mode Card -->
                    <div class="theme-card-option" onclick="setPortalTheme('light')">
                        <input type="radio" name="theme_preference_radio" value="light" style="display: none;">
                        <svg class="theme-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <div style="font-weight: 700; font-size: 0.95rem;">Light Mode</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Crisp white canvas</div>
                    </div>

                    <!-- Dark Mode Card -->
                    <div class="theme-card-option" onclick="setPortalTheme('dark')">
                        <input type="radio" name="theme_preference_radio" value="dark" style="display: none;">
                        <svg class="theme-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <div style="font-weight: 700; font-size: 0.95rem;">Dark Mode</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Deep obsidian night</div>
                    </div>

                    <!-- System Sync Card -->
                    <div class="theme-card-option" onclick="setPortalTheme('system')">
                        <input type="radio" name="theme_preference_radio" value="system" style="display: none;">
                        <svg class="theme-card-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div style="font-weight: 700; font-size: 0.95rem;">Auto Sync</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Match operating system</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-surface-elevated); border-radius: var(--radius-md); font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--success); flex-shrink: 0;"></div>
                    <span>Instant anti-flicker CSS variable injection active with persistent localStorage synchronizer.</span>
                </div>
            </div>
        </div>

        <!-- Security / Password Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Change Account Password</span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('portal.password.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Min. 8 chars" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">Update Security Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Column 2: Personal Profile Details & Activity Timeline -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <!-- Personal Information Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Analyst Profile Information</span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('portal.profile.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="theme_preference" id="hidden_theme_pref" value="{{ $user->theme_preference }}">

                    <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem;">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 72px; height: 72px; border-radius: var(--radius-full); object-fit: cover; border: 3px solid var(--primary);">
                        <div>
                            <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">{{ $user->name }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $user->role }} &bull; {{ $user->title ?? 'Platform User' }}</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Job Title / Specialization</label>
                        <input type="text" name="title" value="{{ old('title', $user->title) }}" class="form-control" placeholder="e.g. Chief Real Estate Analyst">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Avatar Image URL</label>
                        <input type="url" name="avatar" value="{{ old('avatar', $user->avatar) }}" class="form-control" placeholder="https://images.unsplash.com/photo-...">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Profile Details</button>
                </form>
            </div>
        </div>

        <!-- Recent Audit Log for User -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>My Session Audit Log</span>
                </div>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    @forelse($activities as $act)
                        <div class="activity-item">
                            <div class="activity-dot-box">
                                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-title">{{ $act->action }}</div>
                                <div class="activity-desc">{{ $act->description }}</div>
                                <div class="activity-time">{{ $act->created_at->format('M d, Y - h:i A') }} (IP: {{ $act->ip_address ?? '127.0.0.1' }})</div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                            No personal logs recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
