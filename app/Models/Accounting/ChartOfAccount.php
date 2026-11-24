<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';
    protected $primaryKey = 'account_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const ACCOUNT_TYPES = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'COGS', 'EXPENSE'];
    public const NORMAL_BALANCES = ['DEBIT', 'CREDIT'];
    public const CASH_FLOW_CATEGORIES = ['OPERATING', 'INVESTING', 'FINANCING', 'CASH_EQUIVALENT', 'NONE'];

    public const SYSTEM_ROLES = [
        'CASH_ON_HAND',
        'CASH_IN_BANK',
        'ACCOUNTS_RECEIVABLE',
        'PDC_RECEIVABLE',
        'CWT_RECEIVABLE',
        'EMPLOYEE_ADVANCES',
        'VENDOR_PREPAYMENTS',
        'INPUT_VAT',
        'INVENTORY_ASSET',
        'LANDED_COST_CLEARING',
        'FIXED_ASSET_CLEARING',
        'ACCUMULATED_DEPRECIATION',
        'ACCOUNTS_PAYABLE',
        'GRNI_ACCRUED_PURCHASES',
        'PDC_PAYABLE',
        'CUSTOMER_DEPOSITS',
        'OUTPUT_VAT',
        'EWT_PAYABLE',
        'LOAN_PAYABLE',
        'RETAINED_EARNINGS',
        'SALES_REVENUE',
        'SALES_DISCOUNTS',
        'COGS',
        'PURCHASE_PRICE_VARIANCE',
        'DEPRECIATION_EXPENSE',
        'INTEREST_EXPENSE',
        'BANK_PENALTY_EXPENSE',
        'BAD_DEBT_EXPENSE',
        'FX_GAIN_LOSS_REALIZED',
        'FX_GAIN_LOSS_UNREALIZED',
        'GAIN_LOSS_DISPOSAL',
    ];

    protected $fillable = [
        'account_id',
        'company_id',
        'account_code',
        'account_name',
        'account_type',
        'normal_balance',
        'cash_flow_category',
        'system_role',
        'active_system_role_key',
        'parent_account_id',
        'default_tax_code',
        'allow_manual_journal',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'allow_manual_journal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChartOfAccount $account) {
            if (empty($account->account_id)) {
                $account->account_id = (string) Str::uuid();
            }
            if (empty($account->company_id)) {
                $account->company_id = '1';
            }
        });

        static::saving(function (ChartOfAccount $account) {
            $role = $account->system_role ? strtoupper(trim($account->system_role)) : null;
            $account->system_role = $role;

            if ($role !== null && !in_array($role, self::SYSTEM_ROLES, true)) {
                throw new InvalidArgumentException("Invalid system_role [{$role}].");
            }

            if ($role !== null && $account->is_active) {
                $key = $account->company_id . ':' . $role;
                $duplicate = self::where('active_system_role_key', $key)
                    ->where('account_id', '!=', $account->account_id)
                    ->exists();
                if ($duplicate) {
                    throw new InvalidArgumentException("An active account already holds system_role [{$role}] for company [{$account->company_id}].");
                }
                $account->active_system_role_key = $key;
            } else {
                $account->active_system_role_key = null;
            }
        });

        static::updating(function (ChartOfAccount $account) {
            $originalRole = $account->getOriginal('system_role');
            if (!empty($originalRole)) {
                if ($account->isDirty('account_type') || $account->isDirty('normal_balance')) {
                    throw new DomainException("System-role account [{$originalRole}] is protected: cannot change account_type or normal_balance.");
                }
                if ($account->isDirty('system_role') && $account->system_role !== $originalRole) {
                    throw new DomainException("System-role account [{$originalRole}] is protected: cannot remove or reassign its system_role.");
                }
                if ($account->isDirty('is_active') && !$account->is_active) {
                    throw new DomainException("System-role account [{$originalRole}] is protected: cannot deactivate a system-role account.");
                }
            }
        });

        static::deleting(function (ChartOfAccount $account) {
            if (!empty($account->system_role)) {
                throw new DomainException("System-role account [{$account->system_role}] is protected and cannot be deleted.");
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id', 'account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id', 'account_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class, 'account_id', 'account_id');
    }
}

