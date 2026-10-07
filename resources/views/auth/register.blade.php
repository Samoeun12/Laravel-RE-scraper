@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
<div class="auth-header">
    <div class="brand-icon-box">
        <svg style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
    </div>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Join the Apex Real Estate Intelligence Network</p>
</div>

<form action="{{ route('register') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            value="{{ old('name') }}" 
            class="form-control @error('name') is-invalid @enderror" 
            placeholder="Johnathan Doe" 
            required 
            autofocus
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Work Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            class="form-control @error('email') is-invalid @enderror" 
            placeholder="john@enterprise.com" 
            required
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="title" class="form-label">Professional Role / Title</label>
        <input 
            type="text" 
            id="title" 
            name="title" 
            value="{{ old('title') }}" 
            class="form-control @error('title') is-invalid @enderror" 
            placeholder="e.g. Senior Property Valuer"
        >
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div style="position: relative;">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control @error('password') is-invalid @enderror" 
                    placeholder="Min. 8 characters" 
                    required
                >
                <button 
                    type="button" 
                    onclick="togglePasswordVisibility('password', this)"
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center;"
                >
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div style="position: relative;">
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-control" 
                    placeholder="Repeat password" 
                    required
                >
                <button 
                    type="button" 
                    onclick="togglePasswordVisibility('password_confirmation', this)"
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center;"
                >
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8rem; color: var(--text-secondary); cursor: pointer;">
            <input type="checkbox" required checked style="margin-top: 3px; accent-color: var(--primary);">
            <span>I agree to the Data Processing Agreement and Portal Privacy Policy.</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 0.95rem;">
        <span>Create My Portal Account</span>
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
    </button>
</form>

<div style="margin-top: 1.75rem; text-align: center; font-size: 0.875rem; color: var(--text-secondary);">
    Already registered? 
    <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600;">Sign in to your account</a>
</div>
@endsection
