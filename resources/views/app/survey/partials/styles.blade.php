@push('styles')
<style>
    .step-item { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; position: relative; }
    .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 14px;
        left: calc(50% + 16px);
        right: calc(-50% + 16px);
        height: 2px;
        background: #E5E7EB;
        z-index: 0;
    }
    .step-item.done::after { background: #0EA5E9; }
    .step-circle {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: #F3F4F6;
        color: #9CA3AF;
        font-size: 0.7rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #E5E7EB;
        position: relative; z-index: 1;
        transition: all 0.2s;
    }
    .step-item.active .step-circle { background: #0EA5E9; color: #fff; border-color: #0EA5E9; }
    .step-item.done .step-circle { background: #0EA5E9; color: #fff; border-color: #0EA5E9; }
    .step-label { font-size: 0.6rem; color: #9CA3AF; text-align: center; white-space: nowrap; }
    .step-item.active .step-label { color: #0EA5E9; font-weight: 600; }
    .step-item.done .step-label { color: #0EA5E9; }

    .form-section { display: none; }
    .form-section.active { display: block; }

    .form-label { font-size: 0.78rem; font-weight: 500; color: #374151; margin-bottom: 0.35rem; display: block; }
    .form-input {
        width: 100%;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 0.6rem 0.85rem;
        font-size: 0.8rem;
        color: #1F2937;
        outline: none;
        transition: all 0.15s;
    }
    .form-input:focus { border-color: #38BDF8; box-shadow: 0 0 0 3px rgba(56,189,248,0.1); }
    .form-input::placeholder { color: #D1D5DB; }
    select.form-input { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem; appearance: none; padding-right: 2.5rem; }

    .section-heading { font-size: 0.9rem; font-weight: 700; color: #0EA5E9; border-left: 3px solid #0EA5E9; padding-left: 0.75rem; margin-bottom: 1.25rem; }
    .section-sub { font-size: 0.75rem; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 1.25rem 0 0.75rem; }

    .radio-group, .check-group { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .radio-opt, .check-opt {
        display: flex; align-items: center; gap: 0.4rem;
        background: #F9FAFB; border: 1px solid #E5E7EB;
        border-radius: 8px; padding: 0.4rem 0.75rem;
        font-size: 0.75rem; color: #374151; cursor: pointer;
        transition: all 0.15s;
    }
    .radio-opt:hover, .check-opt:hover { border-color: #38BDF8; background: #EFF9FF; }
    .radio-opt input, .check-opt input { accent-color: #0EA5E9; }

    .matrix-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
    .matrix-table th { background: #F9FAFB; padding: 0.5rem 0.75rem; font-weight: 600; color: #6B7280; border: 1px solid #E5E7EB; text-align: center; }
    .matrix-table td { padding: 0.5rem 0.75rem; border: 1px solid #F3F4F6; text-align: center; }
    .matrix-table td:first-child { text-align: left; font-weight: 500; color: #374151; }
    .matrix-table tr:nth-child(even) td { background: #FAFAFA; }
</style>
@endpush
