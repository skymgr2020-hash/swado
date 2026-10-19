import 'package:flutter/material.dart';

import '../blocs/onboarding_bloc.dart';
import '../services/form_helpers.dart';
import '../widgets/form_widgets.dart';

class DriverWizardScreen extends StatefulWidget {
  const DriverWizardScreen({super.key, required this.bloc});

  final OnboardingBloc bloc;

  @override
  State<DriverWizardScreen> createState() => _DriverWizardScreenState();
}

class _DriverWizardScreenState extends State<DriverWizardScreen> {
  final form = GlobalKey<FormState>();
  final c = <String, TextEditingController>{};
  int step = 0;
  bool saving = false;
  String earning = 'commission';
  int? areaId;

  @override
  void initState() {
    super.initState();
    for (final key in [
      'name',
      'email',
      'phone',
      'password',
      'vehicle_type',
      'vehicle_number',
      'license_number',
      'pan',
      'monthly_salary',
      'salary_effective_from',
      'address',
      'city',
      'pincode',
      'latitude',
      'longitude',
      'max_active_orders',
      'account_holder_name',
      'bank_name',
      'account_number',
      'ifsc_code',
      'upi_id',
      'notes',
    ]) {
      c[key] = TextEditingController();
    }
    c['vehicle_type']!.text = 'Bike';
  }

  Future<void> submit() async {
    if (!widget.bloc.canDrivers) return;
    if (!form.currentState!.validate()) return;
    setState(() => saving = true);

    try {
      final password = c['password']!.text;
      await widget.bloc.createDriver({
        for (final entry in c.entries) entry.key: entry.value.text.trim(),
        'password_confirmation': password,
        'earning_mode': earning,
        'area_id': areaId,
      });
      if (mounted) saved('Driver onboarded');
    } catch (e) {
      if (mounted) showToast(context, e.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  void saved(String message) {
    showToast(context, message);
    form.currentState!.reset();
    for (final controller in c.values) {
      controller.clear();
    }
    c['vehicle_type']!.text = 'Bike';
    setState(() => step = 0);
  }

  @override
  Widget build(BuildContext context) {
    final areas = widget.bloc.meta['delivery_areas'] as List<dynamic>? ?? [];
    return WizardShell(
      enabled: widget.bloc.canDrivers,
      child: Form(
        key: form,
        child: Stepper(
          currentStep: step,
          onStepTapped: (i) => setState(() => step = i),
          controlsBuilder: (context, details) => WizardControls(
            step: step,
            last: 2,
            saving: saving,
            onNext: () => step == 2 ? submit() : setState(() => step++),
            onBack: step == 0 ? null : () => setState(() => step--),
          ),
          steps: [profileStep(), fleetStep(), areaStep(areas)],
        ),
      ),
    );
  }

  Step profileStep() => Step(
    title: const Text('Profile'),
    isActive: step >= 0,
    content: FieldGrid(
      children: [
        AppField(c['name']!, 'Full name', Icons.person, required: true),
        AppField(c['email']!, 'Email', Icons.mail, required: true),
        AppField(c['phone']!, 'Phone', Icons.phone, required: true),
        AppField(
          c['password']!,
          'Password',
          Icons.password,
          required: true,
          obscure: true,
        ),
        AppField(c['pan']!, 'PAN', Icons.badge),
      ],
    ),
  );

  Step fleetStep() => Step(
    title: const Text('Fleet'),
    isActive: step >= 1,
    content: FieldGrid(
      children: [
        AppField(
          c['vehicle_type']!,
          'Vehicle type',
          Icons.two_wheeler,
          required: true,
        ),
        AppField(
          c['vehicle_number']!,
          'Vehicle number',
          Icons.confirmation_number,
          required: true,
        ),
        AppField(
          c['license_number']!,
          'License number',
          Icons.card_membership,
          required: true,
        ),
        DropdownButtonFormField(
          initialValue: earning,
          decoration: const InputDecoration(
            labelText: 'Earning mode',
            prefixIcon: Icon(Icons.payments),
          ),
          items: const [
            DropdownMenuItem(value: 'commission', child: Text('Commission')),
            DropdownMenuItem(value: 'salary', child: Text('Salary')),
          ],
          onChanged: (value) => setState(() => earning = value ?? 'commission'),
        ),
        AppField(
          c['monthly_salary']!,
          'Monthly salary',
          Icons.currency_rupee,
          number: true,
        ),
        AppField(
          c['salary_effective_from']!,
          'Salary effective date',
          Icons.calendar_month,
        ),
        AppField(
          c['max_active_orders']!,
          'Max active orders',
          Icons.route,
          number: true,
        ),
      ],
    ),
  );

  Step areaStep(List<dynamic> areas) => Step(
    title: const Text('Area and bank'),
    isActive: step >= 2,
    content: FieldGrid(
      children: [
        AppField(c['address']!, 'Address', Icons.location_on),
        AppField(c['city']!, 'City', Icons.location_city),
        AppField(c['pincode']!, 'Pincode', Icons.markunread_mailbox),
        AppField(c['latitude']!, 'Latitude', Icons.my_location, number: true),
        AppField(c['longitude']!, 'Longitude', Icons.explore, number: true),
        DropdownButtonFormField<int>(
          initialValue: areaId,
          decoration: const InputDecoration(
            labelText: 'Delivery area',
            prefixIcon: Icon(Icons.map),
          ),
          items: [
            for (final area in areas)
              DropdownMenuItem(
                value: area['id'] as int,
                child: Text(area['name'].toString()),
              ),
          ],
          onChanged: (value) => setState(() => areaId = value),
        ),
        AppField(
          c['account_holder_name']!,
          'Account holder',
          Icons.account_balance,
        ),
        AppField(c['bank_name']!, 'Bank name', Icons.account_balance_wallet),
        AppField(c['account_number']!, 'Account number', Icons.numbers),
        AppField(c['ifsc_code']!, 'IFSC / routing code', Icons.tag),
        AppField(c['upi_id']!, 'UPI ID', Icons.payments),
        AppField(c['notes']!, 'Internal notes', Icons.note_alt),
      ],
    ),
  );
}
