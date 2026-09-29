@extends('layouts.app')

@section('title', 'Bill of Materials (BOM) - Restaurant Management System')

@section('content')
<div class="inv-page-container">
    <header class="inv-header-bar" style="margin-bottom: 24px;">
        <div class="inv-header-title-box">
            <div class="inv-header-icon">
                <i class="ph ph-book-open"></i>
            </div>
            <div>
                <h1>Bill of Materials (BOM)</h1>
                <p>Configure product recipes, component quantities, automated inventory deductions, and production costing.</p>
            </div>
        </div>
    </header>
    <div class="inv-grid-card" style="padding: 32px; text-align: center; color: var(--inv-text-muted, #64748b);">
        <i class="ph ph-cooking-pot" style="font-size: 48px; color: var(--inv-primary, #a855f7); margin-bottom: 12px; display: inline-block;"></i>
        <h3 style="font-size: 1.1rem; color: #1e293b; margin-bottom: 6px;">Recipe & BOM Configuration Engine</h3>
        <p style="font-size: 0.85rem; max-width: 520px; margin: 0 auto;">Configure multi-tier recipes, sub-recipes, yield percentages, automated raw material deductions upon POS transactions, and dynamic cost forecasting.</p>
    </div>
</div>
@endsection
