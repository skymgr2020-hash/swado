import 'package:flutter/material.dart';

import '../blocs/onboarding_bloc.dart';
import '../services/form_helpers.dart';
import '../widgets/form_widgets.dart';

class RestaurantWizardScreen extends StatefulWidget {
  const RestaurantWizardScreen({super.key, required this.bloc});

  final OnboardingBloc bloc;

  @override
  State<RestaurantWizardScreen> createState() => _RestaurantWizardScreenState();
}

class _RestaurantWizardScreenState extends State<RestaurantWizardScreen> {
  final form = GlobalKey<FormState>();
  final c = <String, TextEditingController>{};
  final selectedCuisines = <String>{};
  final weeklyOff = <String>{};
  final documentStatus = <String, bool>{};
  int step = 0;
  bool saving = false;
  bool pureVeg = false;
  bool gst = false;
  String restaurantType = 'delivery';
  String commissionType = 'global';

  static const fallbackRestaurantTypes = ['delivery', 'dining', 'both', 'takeaway', 'all'];
  static const fallbackWeekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
  static const fallbackDocuments = ['logo_image', 'banner_image', 'menu_photo', 'fssai_license'];

  @override
  void initState() {
    super.initState();
    for (final key in [
      'name',
      'email',
      'phone',
      'description',
      'address',
      'city',
      'state',
      'state_code',
      'pincode',
      'latitude',
      'longitude',
      'delivery_radius',
      'gstin',
      'pan',
      'fssai_license_number',
      'min_order_amount',
      'delivery_fee',
      'commission_rate',
      'delivery_time',
      'order_lead_time',
      'open_time',
      'close_time',
      'owner_name',
      'owner_email',
      'owner_phone',
      'owner_password',
      'account_holder_name',
      'bank_name',
      'account_number',
      'ifsc_code',
      'upi_id',
      'notes',
    ]) {
      c[key] = TextEditingController();
    }
    resetDefaults();
    WidgetsBinding.instance.addPostFrameCallback((_) => widget.bloc.refreshMetadata());
  }

  @override
  void dispose() {
    for (final controller in c.values) {
      controller.dispose();
    }
    super.dispose();
  }

  void resetDefaults() {
    c['delivery_radius']!.text = '5';
    c['delivery_time']!.text = '30';
    c['open_time']!.text = '09:00';
    c['close_time']!.text = '22:00';
  }

  Future<void> submit() async {
    if (!widget.bloc.canRestaurants) return;
    if (selectedCuisines.isEmpty) {
      showToast(context, 'Select at least one cuisine.');
      setState(() => step = 0);
      return;
    }
    if (!form.currentState!.validate()) return;
    setState(() => saving = true);

    try {
      final ownerPassword = c['owner_password']!.text;
      await widget.bloc.createRestaurant({
        for (final entry in c.entries) entry.key: entry.value.text.trim(),
        'owner_password_confirmation': ownerPassword,
        'restaurant_type': restaurantType,
        'commission_calculation_type': commissionType,
        'is_pure_veg': pureVeg,
        'is_gst_registered': gst,
        'cuisine': selectedCuisines.toList(),
        'weekly_off': weeklyOff.toList(),
        'document_status': documentStatus,
      });
      if (mounted) saved('Restaurant onboarded');
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
    selectedCuisines.clear();
    weeklyOff.clear();
    documentStatus.clear();
    pureVeg = false;
    gst = false;
    restaurantType = 'delivery';
    commissionType = 'global';
    resetDefaults();
    setState(() => step = 0);
  }

  @override
  Widget build(BuildContext context) {
    final restaurantTypes = _stringList(widget.bloc.meta['restaurant_types'], fallbackRestaurantTypes);
    final cuisines = _nameList(widget.bloc.meta['cuisines']);
    final weekDays = _stringList(widget.bloc.meta['week_days'], fallbackWeekDays);
    final documents = _stringList(widget.bloc.meta['restaurant_document_types'], fallbackDocuments);
    final typeItems = {...restaurantTypes, restaurantType}.where((e) => e.isNotEmpty).toList();

    return WizardShell(
      enabled: widget.bloc.canRestaurants,
      child: Form(
        key: form,
        child: Stepper(
          currentStep: step,
          onStepTapped: (i) => setState(() => step = i),
          controlsBuilder: (context, details) => WizardControls(
            step: step,
            last: 4,
            saving: saving,
            onNext: () => step == 4 ? submit() : setState(() => step++),
            onBack: step == 0 ? null : () => setState(() => step--),
          ),
          steps: [
            restaurantStep(typeItems, cuisines),
            ownerStep(),
            locationStep(),
            payoutStep(),
            hoursReviewStep(weekDays, documents),
          ],
        ),
      ),
    );
  }

  Step restaurantStep(List<String> restaurantTypes, List<String> cuisines) => Step(
        title: const Text('Restaurant'),
        isActive: step >= 0,
        content: FieldGrid(
          children: [
            AppField(c['name']!, 'Restaurant name', Icons.storefront, required: true),
            AppField(c['email']!, 'Business email', Icons.mail, required: true),
            AppField(c['phone']!, 'Business phone', Icons.phone, required: true),
            AppField(c['description']!, 'Description', Icons.notes),
            DropdownButtonFormField(
              initialValue: restaurantType,
              decoration: const InputDecoration(labelText: 'Service type', prefixIcon: Icon(Icons.room_service)),
              items: [for (final type in restaurantTypes) DropdownMenuItem(value: type, child: Text(_title(type)))],
              onChanged: (value) => setState(() => restaurantType = value ?? 'delivery'),
            ),
            SwitchListTile(value: pureVeg, onChanged: (value) => setState(() => pureVeg = value), title: const Text('Pure veg')),
            ChipPicker(
              label: 'Cuisine',
              options: cuisines,
              selected: selectedCuisines,
              multi: true,
              emptyLabel: widget.bloc.metadataRefreshing ? 'Loading cuisines...' : 'No cuisines configured',
              onChanged: (value) => setState(() => selectedCuisines
                ..clear()
                ..addAll(value)),
            ),
          ],
        ),
      );

  Step ownerStep() => Step(
        title: const Text('Owner'),
        isActive: step >= 1,
        content: FieldGrid(
          children: [
            AppField(c['owner_name']!, 'Owner name', Icons.person, required: true),
            AppField(c['owner_email']!, 'Owner email', Icons.mail, required: true),
            AppField(c['owner_phone']!, 'Owner phone', Icons.phone, required: true),
            AppField(c['owner_password']!, 'Owner password', Icons.password, required: true, obscure: true),
          ],
        ),
      );

  Step locationStep() => Step(
        title: const Text('Location'),
        isActive: step >= 2,
        content: FieldGrid(
          children: [
            AppField(c['address']!, 'Restaurant address', Icons.location_on, required: true),
            AppField(c['city']!, 'City', Icons.location_city, required: true),
            AppField(c['state']!, 'State', Icons.map, required: true),
            AppField(c['state_code']!, 'State code', Icons.pin),
            AppField(c['pincode']!, 'Pincode', Icons.markunread_mailbox, required: true),
            AppField(c['latitude']!, 'Latitude', Icons.my_location, number: true),
            AppField(c['longitude']!, 'Longitude', Icons.explore, number: true),
            AppField(c['delivery_radius']!, 'Delivery radius km', Icons.radar, required: true, number: true),
          ],
        ),
      );

  Step payoutStep() => Step(
        title: const Text('Payout'),
        isActive: step >= 3,
        content: FieldGrid(
          children: [
            AppField(c['account_holder_name']!, 'Account holder', Icons.account_balance),
            AppField(c['bank_name']!, 'Bank name', Icons.account_balance_wallet),
            AppField(c['account_number']!, 'Account number', Icons.numbers),
            AppField(c['ifsc_code']!, 'IFSC / routing code', Icons.tag),
            AppField(c['upi_id']!, 'UPI ID', Icons.payments),
          ],
        ),
      );

  Step hoursReviewStep(List<String> weekDays, List<String> documents) => Step(
        title: const Text('Hours and review'),
        isActive: step >= 4,
        content: FieldGrid(
          children: [
            SwitchListTile(value: gst, onChanged: (value) => setState(() => gst = value), title: const Text('GST registered')),
            AppField(c['gstin']!, 'GSTIN', Icons.receipt_long),
            AppField(c['pan']!, 'PAN', Icons.badge),
            AppField(c['fssai_license_number']!, 'FSSAI license number', Icons.verified),
            AppField(c['min_order_amount']!, 'Minimum order', Icons.currency_rupee, number: true),
            AppField(c['delivery_fee']!, 'Delivery fee', Icons.payments, number: true),
            DropdownButtonFormField(
              initialValue: commissionType,
              decoration: const InputDecoration(labelText: 'Commission type', prefixIcon: Icon(Icons.percent)),
              items: const [
                DropdownMenuItem(value: 'global', child: Text('Global')),
                DropdownMenuItem(value: 'percentage', child: Text('Percentage')),
                DropdownMenuItem(value: 'fixed', child: Text('Fixed')),
              ],
              onChanged: (value) => setState(() => commissionType = value ?? 'global'),
            ),
            AppField(c['commission_rate']!, 'Commission value', Icons.trending_up, number: true),
            AppField(c['delivery_time']!, 'Delivery time min', Icons.timer, number: true),
            AppField(c['order_lead_time']!, 'Lead time min', Icons.schedule, number: true),
            AppField(c['open_time']!, 'Opening time HH:mm', Icons.lock_open),
            AppField(c['close_time']!, 'Closing time HH:mm', Icons.lock_clock),
            ChipPicker(
              label: 'Weekly off days',
              options: weekDays,
              selected: weeklyOff,
              multi: true,
              onChanged: (value) => setState(() => weeklyOff
                ..clear()
                ..addAll(value)),
            ),
            FullWidthField(child: _documentChecklist(documents)),
            AppField(c['notes']!, 'Internal notes', Icons.note_alt),
          ],
        ),
      );

  Widget _documentChecklist(List<String> documents) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Documents collected', style: TextStyle(fontWeight: FontWeight.w900)),
        const SizedBox(height: 8),
        for (final document in documents)
          CheckboxListTile(
            value: documentStatus[document] ?? false,
            onChanged: (value) => setState(() => documentStatus[document] = value ?? false),
            title: Text(_title(document)),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
          ),
      ],
    );
  }

  List<String> _stringList(dynamic value, List<String> fallback) {
    if (value is List) {
      final parsed = value.map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
      if (parsed.isNotEmpty) return parsed;
    }
    return fallback;
  }

  List<String> _nameList(dynamic value) {
    if (value is! List) return const [];
    return value
        .map((item) => item is Map ? item['name']?.toString() : item.toString())
        .whereType<String>()
        .where((name) => name.isNotEmpty)
        .toList();
  }

  String _title(String value) => value
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => part[0].toUpperCase() + part.substring(1))
      .join(' ');
}
