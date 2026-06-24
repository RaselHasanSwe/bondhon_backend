@extends('admin.layout')
@section('title', 'Site Settings')
@section('page-title', 'Site Settings')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <form method="POST" action="{{ route('admin.web.settings.update') }}" enctype="multipart/form-data">
            @csrf

            {{-- Branding --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-badge me-2"></i>Branding</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Site Name</label>
                        <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror"
                               value="{{ old('site_name', $settings['site_name'] ?? '') }}" placeholder="My Bouma">
                        @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Currency Code</label>
                        <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror"
                               value="{{ old('currency', $settings['currency'] ?? 'BDT') }}" placeholder="BDT">
                        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control @error('currency_symbol') is-invalid @enderror"
                               value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '৳') }}" placeholder="৳">
                        @error('currency_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Site Logo</label>
                        @if(!empty($settings['site_logo']))
                            <div class="mb-2">
                                <img src="{{ $settings['site_logo'] }}" alt="Logo" style="max-height:60px;border-radius:8px;border:1px solid #e5e7eb;">
                            </div>
                        @endif
                        <input type="file" name="site_logo" class="form-control @error('site_logo') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">PNG/SVG recommended. Max 2MB.</small>
                        @error('site_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Site Favicon</label>
                        @if(!empty($settings['site_favicon']))
                            <div class="mb-2">
                                <img src="{{ $settings['site_favicon'] }}" alt="Favicon" style="max-height:32px;border-radius:4px;border:1px solid #e5e7eb;">
                            </div>
                        @endif
                        <input type="file" name="site_favicon" class="form-control @error('site_favicon') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">ICO/PNG, 32×32px. Max 512KB.</small>
                        @error('site_favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Contact Info --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-envelope me-2"></i>Contact Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror"
                               value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="support@My Bouma.com">
                        @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror"
                               value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}" placeholder="+880 1700-000000">
                        @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Contact Address</label>
                        <input type="text" name="contact_address" class="form-control @error('contact_address') is-invalid @enderror"
                               value="{{ old('contact_address', $settings['contact_address'] ?? '') }}" placeholder="Dhaka, Bangladesh">
                        @error('contact_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Email Verification --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-envelope-check me-2"></i>Email Verification</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="email_verification_enabled" name="email_verification_enabled" value="1" {{ !empty($settings['email_verification_enabled']) && filter_var($settings['email_verification_enabled'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="email_verification_enabled">Require email verification after registration</label>
                    </div>
                    <p class="small text-muted mb-0">When enabled, new users must enter a 6-digit code sent to their email before they can proceed to face scan or the dashboard.</p>
                </div>
            </div>

            {{-- Face Verification --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-camera-video me-2"></i>Face Verification</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="face_scan_enabled" name="face_scan_enabled" value="1" {{ !empty($settings['face_scan_enabled']) && filter_var($settings['face_scan_enabled'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="face_scan_enabled">Require face scan after registration</label>
                    </div>
                    <p class="small text-muted mb-0">When enabled, new users must complete the camera-based face scan before they can access the dashboard. Their captures will be stored for super-admin review.</p>
                </div>
            </div>

            {{-- Social Links --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-share me-2"></i>Social Media Links</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="bi bi-facebook me-1"></i>Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control @error('facebook_url') is-invalid @enderror"
                               value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}" placeholder="https://facebook.com/...">
                        @error('facebook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="bi bi-twitter-x me-1"></i>Twitter / X URL</label>
                        <input type="url" name="twitter_url" class="form-control @error('twitter_url') is-invalid @enderror"
                               value="{{ old('twitter_url', $settings['twitter_url'] ?? '') }}" placeholder="https://twitter.com/...">
                        @error('twitter_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small"><i class="bi bi-instagram me-1"></i>Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control @error('instagram_url') is-invalid @enderror"
                               value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}" placeholder="https://instagram.com/...">
                        @error('instagram_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:#C9A227"><i class="bi bi-search me-2"></i>SEO / Meta Defaults</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Default Meta Title <small class="text-muted">(max 160 chars)</small></label>
                        <input type="text" name="meta_title" maxlength="160" class="form-control @error('meta_title') is-invalid @enderror"
                               value="{{ old('meta_title', $settings['meta_title'] ?? '') }}">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Default Meta Description <small class="text-muted">(max 320 chars)</small></label>
                        <textarea name="meta_description" maxlength="320" rows="2"
                                  class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                        @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Meta Keywords <small class="text-muted">(comma separated)</small></label>
                        <input type="text" name="meta_keywords" class="form-control @error('meta_keywords') is-invalid @enderror"
                               value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}" placeholder="matrimony, marriage, Bangladesh">
                        @error('meta_keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-warning fw-semibold px-4" style="background:#C9A227;border-color:#C9A227;color:#fff;">
                <i class="bi bi-check-lg me-1"></i>Save Settings
            </button>
        </form>
    </div>

    {{-- Side info --}}
    <div class="col-12 col-xl-4">
        <div class="table-card p-4 mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>How Settings Work</h6>
            <p class="small text-muted mb-0">These settings are served via the public API endpoint <code>/api/v1/settings</code> and consumed by the frontend (Next.js) to populate the site name, logo, SEO metadata, contact info, and social links across all public pages.</p>
        </div>
        <div class="table-card p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-currency-exchange text-success fs-5"></i>
                <span class="fw-semibold small">Current Currency</span>
            </div>
            <div class="fs-4 fw-bold" style="color:#C9A227">
                {{ $settings['currency_symbol'] ?? '৳' }} {{ $settings['currency'] ?? 'BDT' }}
            </div>
        </div>
    </div>
</div>
@endsection

