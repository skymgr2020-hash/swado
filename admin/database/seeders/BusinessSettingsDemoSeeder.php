<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Fills every Business Settings / tax / accounting key with a realistic,
 * internally-consistent demo profile: a fictional Indian private limited
 * company that is GST-registered, a TDS deductor and a TCS collector, with
 * the double-entry ledger and gig-cess switched on.
 *
 *   php artisan db:seed --class=BusinessSettingsDemoSeeder
 *
 * The IDs are format-valid so TaxConfig activates every service:
 *   GSTIN 27AABCF1234K1Z5  ·  PAN AABCF1234K  ·  TAN MUMF12345K
 */
class BusinessSettingsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $gstin = '27AABCF1234K1Z5';
        $pan = 'AABCF1234K';   // = chars 3-12 of the GSTIN
        $tan = 'MUMF12345K';

        $settings = [
            // ---- Identity ----
            'business_legal_name' => 'Swado Technologies Private Limited',
            'business_trade_name' => 'Swado',
            'business_gstin' => $gstin,
            'business_pan' => $pan,
            'business_cin' => 'U74999MH2021PTC123456',
            'business_reg_address' => 'Unit 4B, Lodha Supremus, Saki Vihar Road, Powai',
            'business_city' => 'Mumbai',
            'business_state' => 'Maharashtra',
            'business_state_code' => '27',
            'business_pincode' => '400072',
            'business_country' => 'India',
            'business_email' => 'accounts@swado.example',
            'business_phone' => '+912249000000',
            'support_email' => 'support@swado.example',
            'business_website' => 'https://swado.example',

            // ---- Entity / compliance profile ----
            'business_entity_type' => 'pvt_ltd',
            'business_turnover_band' => '5cr_10cr',
            'business_has_employees' => '1',
            'business_cit_tds' => 'The Commissioner of Income Tax (TDS), Mumbai',
            'business_signatory_designation' => 'Director',

            // ---- Invoice presentation ----
            'invoice_number_prefix' => 'SWD',
            'invoice_authorised_signatory' => 'Ananya Rao',
            'invoice_declaration' => 'We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct.',
            'invoice_terms' => 'Payment due on receipt. Subject to Mumbai jurisdiction.',
            'invoice_bank_name' => 'HDFC Bank',
            'invoice_bank_account' => '50200001234567',
            'invoice_bank_ifsc' => 'HDFC0000123',

            // ---- GST workflow ----
            'business_gst_enabled' => '1',
            'business_default_gst_rate' => '5',
            'business_default_hsn' => '996331',
            'business_gst_supply_type' => 'intra',
            'business_gst_rounding' => 'invoice',

            // ---- Marketplace GST: Sec 9(5) + TCS ----
            'gst_9_5_mode' => '1',
            'gst_eco_food_rate' => '5',
            'gst_service_rate' => '18',
            'gst_tcs_enabled' => '1',
            'gst_tcs_registered' => '1',
            'gst_tcs_rate' => '0.5',

            // ---- Income-tax TDS + TAN ----
            'business_tan' => $tan,
            'tax_financial_year_start_month' => '4',
            'tds_194o_enabled' => '1',
            'tds_194o_rate' => '0.1',
            'tds_194o_nopan_rate' => '5',
            'tds_194o_threshold' => '500000',
            'tds_194o_after_threshold_only' => '1',
            'tds_194c_enabled' => '1',
            'tds_194c_rate_individual' => '1',
            'tds_194c_rate_other' => '2',
            'tds_194c_nopan_rate' => '20',
            'tds_194c_threshold_single' => '30000',
            'tds_194c_threshold_annual' => '100000',

            // ---- Gig-worker welfare cess ----
            'gig_welfare_cess_enabled' => '1',
            'gig_welfare_cess_rate' => '1',
            'gig_welfare_cess_base' => 'order_value',
            'gig_cess_borne_by' => 'platform',
            'gig_welfare_cess_state' => 'Maharashtra',

            // ---- Double-entry ledger ----
            'accounting_enabled' => '1',

            // ---- Commission GST (read by PayoutCalculationService / TaxConfig) ----
            'gst_rate' => '18',

            // ---- E-invoicing (manual mode) ----
            'einvoice_enabled' => '0',
            'einvoice_provider' => 'manual',
            'einvoice_gstin' => $gstin,
        ];

        foreach ($settings as $key => $value) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => is_numeric($value) ? 'number' : 'string'],
            );
        }

        Cache::forget('app_settings');

        $this->command?->info('Business Settings demo profile loaded: Swado Technologies Pvt Ltd (GSTIN ' . $gstin . ', TAN ' . $tan . ').');
        $this->command?->line('  GST invoicing, Sec 9(5), TCS, TDS 194-O/194-C, gig cess and the general ledger are all ON.');
        $this->command?->line('  Run  php artisan ledger:reconcile  to backfill journals for existing orders.');
    }
}
