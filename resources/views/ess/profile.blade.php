@extends('layouts.app')

@section('title', 'My Profile & 201 File')

@section('content')
<div class="ess-profile-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">My 201 Employment Profile</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Review your official employment records, credentials, and manage permitted personal contact information.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('editContactModal').style.display = 'flex'">
                <i class="ph ph-note-pencil"></i> Update Contact Info
            </button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('coeModal').style.display = 'flex'">
                <i class="ph ph-certificate"></i> Request COE
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Column 1: Identity Card -->
        <div class="card" style="padding: 24px; border-radius: 14px; text-align: center;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 800; margin: 0 auto 16px auto; border: 4px solid #dbeafe;">
                @if($employee->photo_url)
                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->first_name }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                @else
                    {{ $employee->initials }}
                @endif
            </div>
            <h2 style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">{{ $employee->first_name }} {{ $employee->last_name }}</h2>
            <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">{{ $employee->position?->name ?? 'Staff' }}</div>
            
            <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 20px; {{ $employee->employment_status === 'Active' ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;' }}">
                {{ $employee->employment_status }} Employee
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; text-align: left; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <div><strong style="color: #475569;">Employee ID:</strong> <span style="float: right; color: #1e293b; font-weight: 600;">{{ $employee->employee_id }}</span></div>
                <div><strong style="color: #475569;">Company:</strong> <span style="float: right; color: #1e293b;">{{ $employee->company?->name ?? 'Company' }}</span></div>
                <div><strong style="color: #475569;">Branch:</strong> <span style="float: right; color: #1e293b;">{{ $employee->branch?->branch_name ?? 'Branch' }}</span></div>
                <div><strong style="color: #475569;">Department:</strong> <span style="float: right; color: #1e293b;">{{ $employee->department?->name ?? 'Department' }}</span></div>
                <div><strong style="color: #475569;">Date Hired:</strong> <span style="float: right; color: #1e293b;">{{ $employee->date_hired?->format('M d, Y') ?? 'N/A' }}</span></div>
                <div><strong style="color: #475569;">Supervisor:</strong> <span style="float: right; color: #1e293b;">{{ $employee->supervisor ? $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name : 'Direct' }}</span></div>
            </div>
        </div>

        <!-- Column 2: Details & Statutory Credentials -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Contact & Personal Details Card -->
            <div class="card" style="padding: 24px; border-radius: 14px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 16px 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-address-book" style="color: #2563eb;"></i> Contact Information
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 13px;">
                    <div>
                        <div style="color: #64748b; font-size: 12px;">Mobile Number</div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $employee->mobile_number ?: 'Not provided' }}</div>
                    </div>
                    <div>
                        <div style="color: #64748b; font-size: 12px;">Personal Email</div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $employee->email ?: 'Not provided' }}</div>
                    </div>
                    <div>
                        <div style="color: #64748b; font-size: 12px;">Company Email</div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $employee->company_email ?: 'Not assigned' }}</div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <div style="color: #64748b; font-size: 12px;">Present Address</div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $employee->address ?: 'Not provided' }}</div>
                    </div>
                </div>
            </div>

            <!-- Philippine Statutory Numbers Card -->
            <div class="card" style="padding: 24px; border-radius: 14px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 16px 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-shield-check" style="color: #10b981;"></i> Statutory Government Identifiers
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; font-size: 13px;">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 11px; font-weight: 700;">SSS NUMBER</div>
                        <div style="font-weight: 700; color: #1e293b; margin-top: 4px;">{{ $employee->sss_number ?: 'Pending' }}</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 11px; font-weight: 700;">PHILHEALTH NUMBER</div>
                        <div style="font-weight: 700; color: #1e293b; margin-top: 4px;">{{ $employee->philhealth_number ?: 'Pending' }}</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 11px; font-weight: 700;">PAG-IBIG (HDMF)</div>
                        <div style="font-weight: 700; color: #1e293b; margin-top: 4px;">{{ $employee->pagibig_number ?: 'Pending' }}</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                        <div style="color: #64748b; font-size: 11px; font-weight: 700;">TAX ID (TIN)</div>
                        <div style="font-weight: 700; color: #1e293b; margin-top: 4px;">{{ $employee->tin ?: 'Pending' }}</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contacts Card -->
            <div class="card" style="padding: 24px; border-radius: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-phone-call" style="color: #ef4444;"></i> Emergency Contacts
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('addEmergencyModal').style.display = 'flex'">
                        <i class="ph ph-plus"></i> Add Contact
                    </button>
                </div>
                @if($employee->emergencyContacts->isEmpty())
                    <p style="font-size: 13px; color: #94a3b8; margin: 0;">No emergency contact recorded yet.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($employee->emergencyContacts as $contact)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8fafc; border-radius: 8px;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #1e293b;">{{ $contact->name }} ({{ $contact->relationship }})</div>
                                    <div style="font-size: 12px; color: #64748b;">Phone: {{ $contact->phone }}</div>
                                </div>
                                <form action="{{ route('ess.profile.emergency-contact.destroy', $contact->id) }}" method="POST" onsubmit="return confirm('Remove emergency contact?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer;" title="Remove">
                                        <i class="ph ph-trash" style="font-size: 16px;"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div id="editContactModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 500px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">Update Contact Information</h3>
        <form action="{{ route('ess.profile.update-contact') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Mobile Number</label>
                <input type="text" name="mobile_number" value="{{ $employee->mobile_number }}" class="form-input" style="width: 100%;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Personal Email</label>
                <input type="email" name="email" value="{{ $employee->email }}" class="form-input" style="width: 100%;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Present Address</label>
                <textarea name="address" class="form-input" style="width: 100%;" rows="2">{{ $employee->address }}</textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editContactModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- COE Request Modal -->
<div id="coeModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">Request Certificate of Employment (COE)</h3>
        <form action="{{ route('ess.profile.request-coe') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Purpose</label>
                <select name="purpose" class="form-input" style="width: 100%;" required>
                    <option value="Bank Loan Application">Bank Loan Application</option>
                    <option value="Credit Card Application">Credit Card Application</option>
                    <option value="Visa Application / Travel">Visa Application / Travel</option>
                    <option value="Government Requirement">Government Requirement</option>
                    <option value="Personal Reference">Personal Reference</option>
                </select>
            </div>
            <div style="margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="with_compensation" id="with_comp" value="1" checked>
                <label for="with_comp" style="font-size: 13px; font-weight: 600; cursor: pointer;">Include Basic Salary and Allowances in COE</label>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('coeModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit COE Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Emergency Contact Modal -->
<div id="addEmergencyModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">Add Emergency Contact</h3>
        <form action="{{ route('ess.profile.emergency-contact.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Full Name</label>
                <input type="text" name="name" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Relationship</label>
                <input type="text" name="relationship" placeholder="e.g. Spouse, Parent, Sibling" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Contact Phone</label>
                <input type="text" name="phone" class="form-input" style="width: 100%;" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addEmergencyModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Contact</button>
            </div>
        </form>
    </div>
</div>
@endsection
