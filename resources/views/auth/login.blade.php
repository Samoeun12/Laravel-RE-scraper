@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="auth-header">
    <div class="brand-icon-box">
        <svg style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    </div>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Enter your credentials to access the data portal</p>
</div>

<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email', 'admin@portal.test') }}" 
            class="form-control @error('email') is-invalid @enderror" 
            placeholder="name@organization.com" 
            required 
            autofocus
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
            <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
            <span style="font-size: 0.775rem; color: var(--primary); cursor: pointer;" onclick="showToast('Use demo password: password123', 'info')">Forgot password?</span>
        </div>
        <div style="position: relative;">
            <input 
                type="password" 
                id="password" 
                name="password" 
                value="password123"
                class="form-control @error('password') is-invalid @enderror" 
                placeholder="••••••••••••" 
                required
            >
            <button 
                type="button" 
                onclick="togglePasswordVisibility('password', this)"
                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center;"
                title="Toggle visibility"
            >
                <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        </div>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-secondary); cursor: pointer;">
            <input type="checkbox" name="remember" value="1" checked style="accent-color: var(--primary);">
            <span>Keep me logged in for 30 days</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 0.95rem;">
        <span>Sign In to Portal</span>
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
    </button>
</form>

<!-- Demo Credentials Autofill Helper Box -->
<div class="demo-credentials-box">
    <div class="demo-title">
        <span>⚡ Quick Test Credentials:</span>
        <span style="font-size: 0.7rem; color: var(--text-muted);">One-Click Autofill</span>
    </div>
    <div class="demo-pills">
        <button type="button" class="demo-pill-btn" onclick="fillCredentials('admin')">
            Administrator (Alex)
        </button>
        <button type="button" class="demo-pill-btn" onclick="fillCredentials('sarah')">
            Operator (Sarah)
        </button>
    </div>
</div>

<div style="margin-top: 1.75rem; text-align: center; font-size: 0.875rem; color: var(--text-secondary);">
    Don't have an analyst account? 
    <a href="{{ route('register') }}" style="color: var(--primary); font-weight: 600;">Create one now</a>
</div>
@endsection
