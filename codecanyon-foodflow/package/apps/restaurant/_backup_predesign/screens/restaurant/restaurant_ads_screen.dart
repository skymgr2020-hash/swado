// lib/screens/restaurant/restaurant_ads_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../config/api_constants.dart';
import '../../models/menu_item.dart';
import '../../providers/auth_provider.dart';
import '../../providers/restaurant_provider.dart';
import '../../services/ad_wallet_topup_service.dart';
import '../../services/api_service.dart';
import '../../theme/foodflow_theme.dart';
import '../../utils/currency_utils.dart';
import '../../widgets/common/network_image_loader.dart';

// ignore: camel_case_types
typedef foodflow = FoodFlowTheme;

const _purple = Color(0xFF6C3BFF);
const _purpleDark = Color(0xFF4D22D8);
const _lilac = Color(0xFFF4F0FF);
const _gold = Color(0xFFFFB020);
const _green = Color(0xFF22C55E);
const _red = Color(0xFFEF4444);

const _statusLabels = {
  'draft': 'Draft',
  'pending_review': 'Pending Review',
  'active': 'Active',
  'paused': 'Paused',
  'budget_exhausted': 'Budget Exhausted',
  'rejected': 'Rejected',
  'ended': 'Ended',
};

const _statusColors = {
  'draft': Colors.grey,
  'pending_review': Colors.orange,
  'active': _green,
  'paused': Colors.blueGrey,
  'budget_exhausted': Colors.deepOrange,
  'rejected': _red,
  'ended': Colors.grey,
};

class RestaurantAdsScreen extends StatefulWidget {
  const RestaurantAdsScreen({Key? key}) : super(key: key);

  @override
  State<RestaurantAdsScreen> createState() => _RestaurantAdsScreenState();
}

class _RestaurantAdsScreenState extends State<RestaurantAdsScreen> {
  final _api = ApiService();
  var _campaigns = <Map<String, dynamic>>[];
  var _wallet = <String, dynamic>{};
  var _performance = <String, dynamic>{};
  var _loading = true;

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  Future<void> _loadAll() async {
    if (mounted) setState(() => _loading = true);
    await Future.wait([_loadCampaigns(), _loadWallet(), _loadPerformance()]);
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _loadCampaigns() async {
    try {
      final response = await _api.get(ApiConstants.restaurantAdCampaigns);
      if (response['success'] == true) {
        final rows = (response['data']?['data'] as List? ?? const [])
            .whereType<Map>()
            .map((row) => Map<String, dynamic>.from(row))
            .toList();
        if (mounted) setState(() => _campaigns = rows);
      }
    } catch (error) {
      debugPrint('Load ad campaigns error: $error');
    }
  }

  Future<void> _loadWallet() async {
    try {
      final response = await _api.get(ApiConstants.restaurantAdsWallet);
      if (response['success'] == true && mounted) {
        setState(() => _wallet = _map(response['data']?['wallet']));
      }
    } catch (error) {
      debugPrint('Load ad wallet error: $error');
    }
  }

  Future<void> _loadPerformance() async {
    try {
      final response = await _api.get(ApiConstants.restaurantAdPerformance);
      if (response['success'] == true && mounted) {
        setState(() => _performance = _map(response['data']));
      }
    } catch (error) {
      debugPrint('Load ad performance error: $error');
    }
  }

  Future<void> _openCreate({bool smart = false}) async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
          builder: (_) => AdCampaignWorkflowScreen(smartStart: smart)),
    );
    if (created == true) await _loadAll();
  }

  Future<void> _openTopUp() async {
    final funded = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const AdWalletTopUpScreen()),
    );
    if (funded == true) await _loadAll();
  }

  Future<void> _submit(Map<String, dynamic> campaign) async {
    final id = _int(campaign['id']);
    if (id == null) return;
    try {
      final response =
          await _api.post(ApiConstants.restaurantAdCampaignSubmit(id));
      if (response['success'] == true) {
        await _loadAll();
        if (mounted)
          _toast(response['message']?.toString() ??
              'Campaign submitted for review');
      }
    } catch (error) {
      if (mounted) _toast(error.toString());
    }
  }

  Future<void> _pauseOrResume(Map<String, dynamic> campaign) async {
    final id = _int(campaign['id']);
    if (id == null) return;
    final active = campaign['status'] == 'active';
    try {
      await _api.post(active
          ? ApiConstants.restaurantAdCampaignPause(id)
          : ApiConstants.restaurantAdCampaignResume(id));
      await _loadAll();
    } catch (error) {
      if (mounted) _toast(error.toString());
    }
  }

  void _toast(String message) => ScaffoldMessenger.of(context)
      .showSnackBar(SnackBar(content: Text(message)));
  String _money(dynamic value) => formatCurrencyValue(context, _double(value));

  @override
  Widget build(BuildContext context) {
    final restaurantName =
        context.watch<RestaurantProvider>().restaurant?['name']?.toString();
    return Scaffold(
      backgroundColor: foodflow.canvas,
      appBar: AppBar(
        toolbarHeight: 48,
        title: const Text('Marketing'),
        actions: [
          IconButton(
              onPressed: _openTopUp,
              icon: const Icon(Icons.account_balance_wallet_outlined))
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _purple))
          : RefreshIndicator(
              color: _purple,
              onRefresh: _loadAll,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 92),
                children: [
                  _overview(restaurantName),
                  const SizedBox(height: 14),
                  _quickCampaign(),
                  const SizedBox(height: 18),
                  Row(children: [
                    const Expanded(
                        child: Text('Your Campaigns',
                            style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: foodflow.ink))),
                    if (_campaigns.isNotEmpty)
                      Text('${_campaigns.length} total',
                          style: const TextStyle(
                              fontSize: 12, color: foodflow.muted)),
                  ]),
                  const SizedBox(height: 10),
                  if (_campaigns.isEmpty)
                    _emptyCampaigns()
                  else
                    ..._campaigns.map(_campaignCard),
                ],
              ),
            ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: ElevatedButton.icon(
          onPressed: () => _openCreate(),
          icon: const Icon(Icons.add_rounded),
          label: const Text('Create Campaign'),
          style: _primaryButton(),
        ),
      ),
    );
  }

  Widget _overview(String? restaurantName) {
    final impressions = _int(_performance['impressions']) ?? 0;
    final clicks = _int(_performance['clicks']) ?? 0;
    final ctr = _double(_performance['ctr']);
    final active = _int(_performance['active_campaigns']) ?? 0;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _gradientBox(),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                const Text('This Month Overview',
                    style: TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w800)),
                if (restaurantName != null)
                  Text(restaurantName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          color: Colors.white.withOpacity(.78),
                          fontSize: 12,
                          fontWeight: FontWeight.w700)),
              ])),
          TextButton(
              onPressed: _campaigns.isEmpty
                  ? null
                  : () => _openDashboard(_campaigns.first),
              style: TextButton.styleFrom(foregroundColor: Colors.white),
              child: const Text('View Details')),
        ]),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
              color: Colors.white, borderRadius: BorderRadius.circular(8)),
          child: Column(children: [
            Row(children: [
              _metric('Ad Spend', _money(_performance['spend'])),
              _metric('Impressions', _compact(impressions))
            ]),
            const SizedBox(height: 14),
            Row(children: [
              _metric('Clicks', _compact(clicks)),
              _metric('CTR', '${ctr.toStringAsFixed(2)}%')
            ]),
            const SizedBox(height: 14),
            Row(children: [
              _metric('Active Campaigns', active.toString()),
              _metric('Ad Wallet', _money(_wallet['balance']))
            ]),
          ]),
        ),
      ]),
    );
  }

  Widget _metric(String label, String value) => Expanded(
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label,
            style: const TextStyle(
                fontSize: 11,
                color: foodflow.muted,
                fontWeight: FontWeight.w700)),
        Text(value,
            style: const TextStyle(
                fontSize: 16,
                color: foodflow.ink,
                fontWeight: FontWeight.w800)),
      ]));

  Widget _quickCampaign() => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
            color: const Color(0xFFFFFBEB),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFFDE68A))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: const [
            Icon(Icons.bolt_rounded, color: _gold, size: 19),
            SizedBox(width: 8),
            Text('Quick Campaign',
                style:
                    TextStyle(fontWeight: FontWeight.w800, color: foodflow.ink))
          ]),
          const SizedBox(height: 6),
          const Text(
              'Use smart targeting with your real restaurant, menu and wallet data.',
              style: TextStyle(
                  fontSize: 12, color: foodflow.inkSoft, height: 1.35)),
          const SizedBox(height: 12),
          SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                  onPressed: () => _openCreate(smart: true),
                  style: _outlineButton(_purple),
                  child: const Text('Start Auto Campaign'))),
        ]),
      );

  Widget _emptyCampaigns() => Container(
        height: 220,
        decoration: _cardDecoration(),
        child: foodflow.emptyState(
            icon: Icons.campaign_outlined,
            title: 'No campaigns yet',
            subtitle:
                'Create a campaign when you are ready to buy sponsored placement.'),
      );

  Widget _campaignCard(Map<String, dynamic> campaign) {
    final status = campaign['status']?.toString() ?? 'draft';
    final color = _statusColors[status] ?? Colors.grey;
    final metrics = _map(campaign['metrics']);
    return InkWell(
      onTap: () => _openDashboard(campaign),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: _cardDecoration(),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(
                child: Text(campaign['name']?.toString() ?? 'Campaign',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: foodflow.ink))),
            _statusBadge(status, color),
          ]),
          const SizedBox(height: 10),
          Wrap(spacing: 8, runSpacing: 8, children: [
            _miniChip(Icons.payments_outlined,
                'Spend ${_money(metrics['spend'] ?? campaign['spent_total'])}'),
            _miniChip(Icons.visibility_outlined,
                '${_compact(_int(metrics['impressions']) ?? 0)} impressions'),
            _miniChip(Icons.ads_click_outlined,
                '${_compact(_int(metrics['clicks']) ?? 0)} clicks'),
            _miniChip(
                Icons.gavel_outlined, 'Bid ${_money(campaign['max_cpc'])}'),
          ]),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(
                child: OutlinedButton(
                    onPressed: () => _openDashboard(campaign),
                    style: _outlineButton(_purple, compact: true),
                    child: const Text('View Performance'))),
            const SizedBox(width: 10),
            if (status == 'draft' || status == 'rejected')
              Expanded(
                  child: ElevatedButton(
                      onPressed: () => _submit(campaign),
                      style: _primaryButton(compact: true),
                      child: const Text('Submit')))
            else if (status == 'active' ||
                status == 'paused' ||
                status == 'budget_exhausted')
              Expanded(
                  child: OutlinedButton(
                      onPressed: () => _pauseOrResume(campaign),
                      style: _outlineButton(
                          status == 'active' ? Colors.blueGrey : _purple,
                          compact: true),
                      child: Text(status == 'active' ? 'Pause' : 'Resume'))),
          ]),
        ]),
      ),
    );
  }

  void _openDashboard(Map<String, dynamic> campaign) {
    Navigator.push(
        context,
        MaterialPageRoute(
            builder: (_) => CampaignDashboardScreen(campaign: campaign)));
  }
}

class AdCampaignWorkflowScreen extends StatefulWidget {
  const AdCampaignWorkflowScreen({Key? key, this.smartStart = false})
      : super(key: key);
  final bool smartStart;
  @override
  State<AdCampaignWorkflowScreen> createState() =>
      _AdCampaignWorkflowScreenState();
}

class _AdCampaignWorkflowScreenState extends State<AdCampaignWorkflowScreen> {
  static const _steps = [
    'Choose Goal',
    'Campaign Type',
    'What to Promote',
    'Target Audience',
    'Placement',
    'Budget',
    'Schedule',
    'Bidding',
    'Ad Creative',
    'Preview',
    'Review Campaign'
  ];
  final _api = ApiService();
  final _name = TextEditingController();
  final _search = TextEditingController();
  final _headline = TextEditingController();
  final _description = TextEditingController();
  final _offer = TextEditingController();
  var _step = 0;
  var _loading = true;
  var _saving = false;
  var _items = <MenuItem>[];
  var _categories = <Map<String, dynamic>>[];
  var _wallet = <String, dynamic>{};
  var _goal = 'orders';
  var _type = 'smart';
  var _promote = 'restaurant';
  final _selectedItems = <int>{};
  int? _selectedCategory;
  var _audience = 'smart';
  final _customers = <String>{'new_customers', 'existing_customers'};
  var _autoPlacement = true;
  final _placements = <String>{
    'search_results',
    'restaurant_listing',
    'home_recommendations',
    'cuisine_pages'
  };
  var _dailyBudget = 500.0;
  var _continuous = false;
  var _startsAt = DateTime.now();
  var _endsAt = DateTime.now().add(const Duration(days: 6));
  var _schedule = 'auto';
  final _dayparts = <String>{'dinner'};
  var _bidding = 'maximize_orders';
  var _maxCpc = 8.0;
  var _targetCpa = 30.0;

  @override
  void initState() {
    super.initState();
    _search.addListener(() => setState(() {}));
    _headline.addListener(() => setState(() {}));
    _description.addListener(() => setState(() {}));
    _offer.addListener(() => setState(() {}));
    _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _search.dispose();
    _headline.dispose();
    _description.dispose();
    _offer.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final provider = context.read<RestaurantProvider>();
      await provider.loadRestaurants();
      final selectedId = provider.selectedRestaurantId;
      final query = {
        if (selectedId != null) 'restaurant_id': selectedId.toString()
      };
      final responses = await Future.wait([
        _api.get(ApiConstants.restaurantMenuItems, queryParams: query),
        _api.get(ApiConstants.restaurantCategories, queryParams: query),
        _api.get(ApiConstants.restaurantAdsWallet),
      ]);
      final restaurantName =
          provider.restaurant?['name']?.toString() ?? 'Restaurant';
      if (!mounted) return;
      setState(() {
        _items = ((responses[0]['data'] as List?) ?? const [])
            .whereType<Map>()
            .map((row) => MenuItem.fromJson(Map<String, dynamic>.from(row)))
            .toList();
        _categories = ((responses[1]['data'] as List?) ?? const [])
            .whereType<Map>()
            .map((row) => Map<String, dynamic>.from(row))
            .toList();
        _wallet = _map(responses[2]['data']?['wallet']);
        _name.text =
            '$restaurantName campaign - ${DateFormat('d MMM').format(DateTime.now())}';
        _headline.text = restaurantName;
        if (widget.smartStart && _items.isNotEmpty) {
          final ranked = [..._items]
            ..sort((a, b) => b.totalOrders.compareTo(a.totalOrders));
          _selectedItems.add(ranked.first.id);
          _promote = 'items';
        }
      });
    } catch (error) {
      if (mounted) _toast('Unable to load campaign data: $error');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _next() {
    final error = _validate();
    if (error != null) {
      _toast(error);
      return;
    }
    if (_step < _steps.length - 1) {
      setState(() => _step++);
    } else {
      _launch();
    }
  }

  void _back() {
    if (_step > 0) {
      setState(() => _step--);
    } else {
      Navigator.pop(context);
    }
  }

  String? _validate() {
    if (_step == 2 && _promote == 'items' && _selectedItems.isEmpty)
      return 'Select at least one menu item.';
    if (_step == 2 && _promote == 'category' && _selectedCategory == null)
      return 'Select a category.';
    if (_step == 5 && _dailyBudget <= 0) return 'Enter a valid daily budget.';
    if (_step == 7 && _maxCpc < 0.5)
      return 'Minimum CPC bid is ${_money(0.5)}.';
    if (_step == 8 && _name.text.trim().isEmpty)
      return 'Enter a campaign name.';
    return null;
  }

  Future<void> _launch() async {
    setState(() => _saving = true);
    Map<String, dynamic>? campaign;
    try {
      final created =
          await _api.post(ApiConstants.restaurantAdCampaigns, data: _payload());
      if (created['success'] != true)
        throw ApiException(
            created['message']?.toString() ?? 'Unable to create campaign.');
      campaign = _map(created['data']);
      final id = _int(campaign['id']);
      if (id == null) throw ApiException('Campaign was created without an ID.');
      final submitted =
          await _api.post(ApiConstants.restaurantAdCampaignSubmit(id));
      if (submitted['success'] == true && mounted) {
        Navigator.pushReplacement(
            context,
            MaterialPageRoute(
                builder: (_) => AdCampaignSuccessScreen(
                    campaign: _map(submitted['data']))));
      }
    } catch (error) {
      if (mounted) _toast(error.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Map<String, dynamic> _payload() {
    return {
      'name': _name.text.trim(),
      'max_cpc': _maxCpc,
      'daily_budget': _dailyBudget,
      'total_budget': _continuous ? null : _maxSpend(),
      'starts_at': DateFormat('yyyy-MM-dd').format(_startsAt),
      'ends_at': _continuous ? null : DateFormat('yyyy-MM-dd').format(_endsAt),
      'targeting': {
        'goal': _goal,
        'campaign_type': _type,
        'promote_type': _promote,
        'selected_item_ids': _selectedItems.toList(),
        'selected_category_id': _selectedCategory,
        'audience_mode': _audience,
        'customer_types': _customers.toList(),
        'automatic_placement': _autoPlacement,
        'placements': _placements.toList(),
        'schedule_mode': _schedule,
        'dayparts': _dayparts.toList(),
        'bidding': _bidding,
        'target_cpa': _targetCpa,
        'creative': {
          'headline': _creativeHeadline(),
          'description': _creativeDescription(),
          'offer': _offer.text.trim(),
          'image_item_id': _previewItem()?.id
        },
      },
    };
  }

  double _maxSpend() =>
      _dailyBudget * ((_endsAt.difference(_startsAt).inDays + 1).clamp(1, 366));
  String _money(dynamic value) => formatCurrencyValue(context, _double(value));
  void _toast(String message) => ScaffoldMessenger.of(context)
      .showSnackBar(SnackBar(content: Text(message)));

  MenuItem? _previewItem() {
    for (final item in _items) {
      if (_selectedItems.contains(item.id)) return item;
    }
    return _items.isEmpty ? null : _items.first;
  }

  String _creativeHeadline() => _headline.text.trim().isNotEmpty
      ? _headline.text.trim()
      : (_previewItem()?.name ?? 'Your restaurant');
  String _creativeDescription() {
    if (_description.text.trim().isNotEmpty) return _description.text.trim();
    final item = _previewItem();
    return item?.description?.trim().isNotEmpty == true
        ? item!.description!.trim()
        : 'Sponsored placement';
  }

  List<MenuItem> get _filteredItems {
    final query = _search.text.trim().toLowerCase();
    if (query.isEmpty) return _items;
    return _items
        .where((item) =>
            item.name.toLowerCase().contains(query) ||
            (item.categoryName ?? '').toLowerCase().contains(query) ||
            (item.cuisineName ?? '').toLowerCase().contains(query))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: foodflow.canvas,
      appBar: AppBar(
          toolbarHeight: 48,
          centerTitle: true,
          title: Text(_steps[_step],
              style:
                  const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
          leading: IconButton(
              icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
              onPressed: _back)),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _purple))
          : Column(children: [
              _progress(),
              Expanded(
                  child: SingleChildScrollView(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
                      child: _buildStep())),
            ]),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: ElevatedButton(
          onPressed: _saving ? null : _next,
          style: _primaryButton(),
          child: _saving
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                      strokeWidth: 2, color: Colors.white))
              : Text(
                  _step == _steps.length - 1 ? 'Launch Campaign' : 'Continue'),
        ),
      ),
    );
  }

  Widget _progress() => Container(
        color: Colors.white,
        padding: const EdgeInsets.fromLTRB(16, 6, 16, 10),
        child: Row(children: [
          Text('${_step + 1}',
              style:
                  const TextStyle(color: _purple, fontWeight: FontWeight.w800)),
          Text(' / ${_steps.length}',
              style: const TextStyle(
                  color: foodflow.muted, fontWeight: FontWeight.w800)),
          const SizedBox(width: 10),
          Expanded(
              child: ClipRRect(
                  borderRadius: BorderRadius.circular(99),
                  child: LinearProgressIndicator(
                      value: (_step + 1) / _steps.length,
                      minHeight: 6,
                      color: _purple,
                      backgroundColor: _lilac))),
        ]),
      );

  Widget _buildStep() {
    switch (_step) {
      case 0:
        return _goalStep();
      case 1:
        return _campaignTypeStep();
      case 2:
        return _promoteStep();
      case 3:
        return _audienceStep();
      case 4:
        return _placementStep();
      case 5:
        return _budgetStep();
      case 6:
        return _scheduleStep();
      case 7:
        return _biddingStep();
      case 8:
        return _creativeStep();
      case 9:
        return _previewStep();
      default:
        return _reviewStep();
    }
  }

  Widget _goalStep() => _screenCard('What do you want to achieve?', [
        _choice(
            'orders',
            _goal,
            Icons.delivery_dining_rounded,
            'Get More Orders',
            'Increase restaurant orders.',
            (v) => setState(() => _goal = v),
            badge: 'Recommended'),
        _choice(
            'visibility',
            _goal,
            Icons.campaign_outlined,
            'Get More Visibility',
            'Show your restaurant to more customers.',
            (v) => setState(() => _goal = v)),
        _choice(
            'new_restaurant',
            _goal,
            Icons.storefront_rounded,
            'Promote New Restaurant',
            'Build awareness and acquire customers.',
            (v) => setState(() => _goal = v)),
        _choice(
            'specific_items',
            _goal,
            Icons.shopping_bag_outlined,
            'Promote Specific Items',
            'Push selected dishes or meals.',
            (v) => setState(() => _goal = v)),
        _choice('offer', _goal, Icons.local_offer_outlined, 'Promote an Offer',
            'Advertise discounts and deals.', (v) => setState(() => _goal = v)),
        _choice(
            'winback',
            _goal,
            Icons.history_rounded,
            'Bring Customers Back',
            'Target customers who have not ordered recently.',
            (v) => setState(() => _goal = v)),
      ]);

  Widget _campaignTypeStep() => _screenCard('Choose Campaign Type', [
        _largeChoice(
            'smart',
            _type,
            Icons.bolt_rounded,
            'Smart Campaign',
            'We automatically select customers, placements and bidding signals from live data.',
            (v) => setState(() => _type = v),
            badge: 'Recommended',
            iconColor: _gold),
        _largeChoice(
            'advanced',
            _type,
            Icons.tune_rounded,
            'Advanced Campaign',
            'Control audience, placement, schedule and bidding yourself.',
            (v) => setState(() => _type = v)),
      ]);

  Widget _promoteStep() => _screenCard('What do you want to promote?', [
        _radio('restaurant', _promote, 'My Restaurant',
            (v) => setState(() => _promote = v)),
        _radio('items', _promote, 'Selected Items',
            (v) => setState(() => _promote = v)),
        _radio('category', _promote, 'Category',
            (v) => setState(() => _promote = v)),
        _radio('combo', _promote, 'Combo / Meal',
            (v) => setState(() => _promote = v)),
        _radio('offer', _promote, 'Existing Offer',
            (v) => setState(() => _promote = v)),
        if (_promote == 'items' ||
            _promote == 'combo' ||
            _promote == 'offer') ...[
          const SizedBox(height: 14),
          TextField(
              controller: _search,
              decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search_rounded),
                  hintText: 'Search menu items')),
          const SizedBox(height: 10),
          if (_filteredItems.isEmpty)
            _emptyInline('No menu items found for this restaurant.')
          else
            ..._filteredItems.take(30).map(_menuTile),
          const SizedBox(height: 8),
          Text('${_selectedItems.length} items selected',
              style: const TextStyle(fontWeight: FontWeight.w800)),
        ],
        if (_promote == 'category') ...[
          const SizedBox(height: 14),
          if (_categories.isEmpty)
            _emptyInline('No categories found for this restaurant.')
          else
            ..._categories.map((category) {
              final id = _int(category['id']);
              return _radio(
                  id?.toString() ?? '',
                  _selectedCategory?.toString() ?? '',
                  category['name']?.toString() ?? 'Category',
                  (v) => setState(() => _selectedCategory = int.tryParse(v)));
            }),
        ],
      ]);

  Widget _audienceStep() => _screenCard('Who should see your ad?', [
        _largeChoice(
            'smart',
            _audience,
            Icons.auto_awesome_rounded,
            'Smart Targeting',
            'We will find customers most likely to order from your restaurant.',
            (v) => setState(() => _audience = v),
            badge: 'Recommended',
            iconColor: _gold),
        const SizedBox(height: 10),
        _section('Advanced Options'),
        _check('All Customers', 'all_customers', _customers, _toggleCustomer),
        _check('New Customers', 'new_customers', _customers, _toggleCustomer),
        _check('Existing Customers', 'existing_customers', _customers,
            _toggleCustomer),
        _check('Customers who have not ordered recently', 'lapsed_customers',
            _customers, _toggleCustomer),
        _check('Previous Customers', 'previous_customers', _customers,
            _toggleCustomer),
        _check('High-value Customers', 'high_value_customers', _customers,
            _toggleCustomer),
      ]);

  void _toggleCustomer(String value, bool selected) {
    setState(() {
      if (selected) {
        if (value == 'all_customers') {
          _customers
            ..clear()
            ..add(value);
        } else {
          _customers
            ..remove('all_customers')
            ..add(value);
        }
      } else {
        _customers.remove(value);
      }
    });
  }

  Widget _placementStep() =>
      _screenCard('Where should your restaurant appear?', [
        _placement('Search Results', 'Appear above organic search results',
            'search_results', Icons.search_rounded),
        _placement(
            'Restaurant Listing',
            'Get higher visibility while customers browse',
            'restaurant_listing',
            Icons.groups_rounded),
        _placement(
            'Home Recommendations',
            'Appear in home feed recommendations',
            'home_recommendations',
            Icons.home_outlined),
        _placement('Cuisine Pages', 'Show on matching cuisine pages',
            'cuisine_pages', Icons.restaurant_menu_rounded),
        _placement('Deals & Offers', 'Appear near offer-led discovery surfaces',
            'deals_offers', Icons.local_offer_outlined),
        _placement('Sponsored Items', 'Promote selected items where supported',
            'sponsored_items', Icons.fastfood_outlined),
        const SizedBox(height: 10),
        _largeChoice(
            'auto',
            _autoPlacement ? 'auto' : 'manual',
            Icons.auto_mode_rounded,
            'Automatic Placement',
            'We place ads wherever they are most likely to generate an order.',
            (_) => setState(() => _autoPlacement = true),
            badge: 'Recommended'),
      ]);

  Widget _budgetStep() => _screenCard('Set Your Budget', [
        const _FieldLabel('Daily Budget'),
        TextFormField(
            initialValue: _dailyBudget.toStringAsFixed(0),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration:
                InputDecoration(prefixText: currencyInputPrefix(context)),
            onChanged: (v) => setState(
                () => _dailyBudget = double.tryParse(v) ?? _dailyBudget)),
        const SizedBox(height: 12),
        Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [200, 300, 500, 1000].map((amount) {
              final selected = _dailyBudget.round() == amount;
              return ChoiceChip(
                  selected: selected,
                  selectedColor: _purple,
                  label: Text(_money(amount)),
                  labelStyle: TextStyle(
                      color: selected ? Colors.white : foodflow.ink,
                      fontWeight: FontWeight.w800),
                  onSelected: (_) =>
                      setState(() => _dailyBudget = amount.toDouble()));
            }).toList()),
        const SizedBox(height: 16),
        _infoPanel(
            'Estimated Daily Results',
            [
              MapEntry('Impressions',
                  '${_compact((_dailyBudget * 3).round())} - ${_compact((_dailyBudget * 4.6).round())}'),
              MapEntry('Restaurant Visits',
                  '${(_dailyBudget * .14).round()} - ${(_dailyBudget * .22).round()}'),
              MapEntry('Billable Clicks',
                  '${(_dailyBudget / _maxCpc).floor()} - ${((_dailyBudget / _maxCpc) * 1.35).ceil()}'),
            ],
            'These estimates use the current budget and bid. Actual results depend on demand and auction competition.'),
        SwitchListTile.adaptive(
            value: _continuous,
            activeColor: _purple,
            contentPadding: EdgeInsets.zero,
            title: const Text('Run continuously',
                style: TextStyle(fontWeight: FontWeight.w800)),
            onChanged: (v) => setState(() => _continuous = v)),
        if (!_continuous) _dateRange(),
        const Divider(height: 28),
        const Text('Estimated Maximum Spend',
            style: TextStyle(
                fontSize: 12,
                color: foodflow.muted,
                fontWeight: FontWeight.w700)),
        Text(_continuous ? 'No fixed end date' : _money(_maxSpend()),
            style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: foodflow.ink)),
      ]);

  Widget _scheduleStep() => _screenCard('When should your campaign run?', [
        _largeChoice(
            'auto',
            _schedule,
            Icons.auto_awesome_rounded,
            'Automatically optimize schedule',
            'We show your ads at the times likely to get more orders.',
            (v) => setState(() => _schedule = v)),
        const SizedBox(height: 10),
        _section('Custom Schedule'),
        _check('Breakfast', 'breakfast', _dayparts, _toggleDaypart,
            trailing: '7 AM - 11 AM'),
        _check('Lunch', 'lunch', _dayparts, _toggleDaypart,
            trailing: '11 AM - 3 PM'),
        _check('Evening', 'evening', _dayparts, _toggleDaypart,
            trailing: '3 PM - 7 PM'),
        _check('Dinner', 'dinner', _dayparts, _toggleDaypart,
            trailing: '7 PM - 11 PM'),
        _check('Late Night', 'late_night', _dayparts, _toggleDaypart,
            trailing: '11 PM - 2 AM'),
        _note(
            'Your campaign can run automatically unless you need strict kitchen-capacity windows.'),
      ]);

  void _toggleDaypart(String value, bool selected) {
    setState(() {
      _schedule = 'custom';
      selected ? _dayparts.add(value) : _dayparts.remove(value);
    });
  }

  Widget _biddingStep() => _screenCard('How would you like to pay?', [
        _largeChoice(
            'maximize_orders',
            _bidding,
            Icons.local_mall_outlined,
            'Maximize Orders',
            'We optimize to get the most orders within your budget.',
            (v) => setState(() => _bidding = v),
            badge: 'Recommended'),
        _largeChoice(
            'cpc',
            _bidding,
            Icons.ads_click_rounded,
            'Pay per Click (CPC)',
            'You pay when someone clicks your ad.',
            (v) => setState(() => _bidding = v)),
        const _FieldLabel('Maximum CPC'),
        TextFormField(
            initialValue: _maxCpc.toStringAsFixed(2),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration:
                InputDecoration(prefixText: currencyInputPrefix(context)),
            onChanged: (v) =>
                setState(() => _maxCpc = double.tryParse(v) ?? _maxCpc)),
        const SizedBox(height: 12),
        _largeChoice(
            'target_cpa',
            _bidding,
            Icons.track_changes_rounded,
            'Target Cost per Order (CPA)',
            'Set a target cost for each order received.',
            (v) => setState(() => _bidding = v)),
        const _FieldLabel('Target cost per order'),
        TextFormField(
            initialValue: _targetCpa.toStringAsFixed(0),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration:
                InputDecoration(prefixText: currencyInputPrefix(context)),
            onChanged: (v) =>
                setState(() => _targetCpa = double.tryParse(v) ?? _targetCpa)),
      ]);

  Widget _creativeStep() => _screenCard('Your Ad Preview', [
        _adPreview(compact: false),
        const SizedBox(height: 14),
        const _FieldLabel('Campaign name'),
        TextField(controller: _name),
        const SizedBox(height: 10),
        const _FieldLabel('Headline'),
        TextField(controller: _headline),
        const SizedBox(height: 10),
        const _FieldLabel('Description'),
        TextField(controller: _description),
        const SizedBox(height: 10),
        const _FieldLabel('Offer'),
        TextField(
            controller: _offer,
            decoration: const InputDecoration(hintText: 'Optional offer text')),
      ]);

  Widget _previewStep() => _screenCard('Ad Preview', [
        SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
                children: ['Home', 'Search', 'Listing', 'Item'].map((tab) {
              final selected = tab == 'Search';
              return Container(
                  margin: const EdgeInsets.only(right: 8),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
                  decoration: BoxDecoration(
                      color: selected ? _lilac : Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                          color: selected ? _purple : foodflow.line)),
                  child: Text(tab,
                      style: TextStyle(
                          fontWeight: FontWeight.w800,
                          color: selected ? _purple : foodflow.inkSoft)));
            }).toList())),
        const SizedBox(height: 14),
        TextField(
            enabled: false,
            decoration: InputDecoration(
                prefixIcon: const Icon(Icons.search_rounded),
                hintText: _previewItem()?.name ?? _creativeHeadline())),
        const SizedBox(height: 14),
        _adPreview(compact: true),
        const SizedBox(height: 12),
        Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
                color: _lilac, borderRadius: BorderRadius.circular(8)),
            child: const Text('This is how your ad will look to customers.',
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: foodflow.inkSoft, fontWeight: FontWeight.w700))),
      ]);

  Widget _reviewStep() {
    final balance = _double(_wallet['balance']);
    final funded = balance >= _maxCpc;
    return _screenCard('Review Campaign', [
      _review('Goal', _labelFor(_goal)),
      _review('Promoting', _labelFor(_promote)),
      _review(
          'Audience',
          _audience == 'smart'
              ? 'Smart Targeting'
              : '${_customers.length} customer groups'),
      _review('Placement',
          _autoPlacement ? 'Automatic' : '${_placements.length} placements'),
      _review('Budget', '${_money(_dailyBudget)} / day'),
      _review(
          'Duration',
          _continuous
              ? 'Continuous'
              : '${DateFormat('d MMM').format(_startsAt)} - ${DateFormat('d MMM').format(_endsAt)}'),
      _review('Max. Spend', _continuous ? 'No fixed max' : _money(_maxSpend())),
      const SizedBox(height: 14),
      _infoPanel(
          'Payment',
          [
            MapEntry('Ad Wallet Balance', _money(balance)),
            MapEntry('Minimum Required', _money(_maxCpc))
          ],
          funded
              ? 'You have sufficient balance to submit this campaign for review.'
              : 'Top up your ad wallet before launch. The campaign needs at least one max CPC available.',
          success: funded),
      if (!funded) ...[
        const SizedBox(height: 12),
        SizedBox(
            width: double.infinity,
            child: OutlinedButton(
                onPressed: () async {
                  final ok = await Navigator.push<bool>(
                      context,
                      MaterialPageRoute(
                          builder: (_) => const AdWalletTopUpScreen()));
                  if (ok == true) _load();
                },
                style: _outlineButton(_purple),
                child: const Text('Top Up Ad Wallet'))),
      ],
    ]);
  }

  Widget _screenCard(String title, List<Widget> children) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                  fontSize: 16,
                  height: 1.2,
                  fontWeight: FontWeight.w800,
                  color: foodflow.ink)),
          const SizedBox(height: 16),
          ...children,
        ],
      );

  Widget _choice(String value, String group, IconData icon, String title,
      String subtitle, ValueChanged<String> onTap,
      {String? badge}) {
    final selected = value == group;
    return InkWell(
      onTap: () => onTap(value),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: _selectDecoration(selected),
        child: Row(children: [
          _iconBox(icon, selected ? _purple : foodflow.muted),
          const SizedBox(width: 12),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Row(children: [
                  Expanded(
                      child: Text(title,
                          style: const TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w800,
                              color: foodflow.ink))),
                  if (badge != null) _smallBadge(badge)
                ]),
                const SizedBox(height: 3),
                Text(subtitle,
                    style: const TextStyle(
                        fontSize: 12, color: foodflow.inkSoft, height: 1.3)),
              ])),
        ]),
      ),
    );
  }

  Widget _largeChoice(String value, String group, IconData icon, String title,
      String subtitle, ValueChanged<String> onTap,
      {String? badge, Color iconColor = _purple}) {
    final selected = value == group;
    return InkWell(
      onTap: () => onTap(value),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: _selectDecoration(selected),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, size: 26, color: iconColor),
          const SizedBox(width: 12),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Row(children: [
                  Expanded(
                      child: Text(title,
                          style: const TextStyle(
                              fontSize: 13.5,
                              fontWeight: FontWeight.w800,
                              color: foodflow.ink))),
                  if (badge != null) _smallBadge(badge),
                  const SizedBox(width: 8),
                  Icon(
                      selected
                          ? Icons.radio_button_checked
                          : Icons.radio_button_off,
                      color: selected ? _purple : foodflow.faint,
                      size: 19),
                ]),
                const SizedBox(height: 8),
                Text(subtitle,
                    style: const TextStyle(
                        fontSize: 12.5, color: foodflow.inkSoft, height: 1.4)),
              ])),
        ]),
      ),
    );
  }

  Widget _radio(String value, String group, String label,
      ValueChanged<String> onChanged) {
    final selected = value == group;
    return InkWell(
      onTap: () => onChanged(value),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 7),
        child: Row(children: [
          Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off,
              color: selected ? _purple : foodflow.faint, size: 20),
          const SizedBox(width: 10),
          Expanded(
              child: Text(label,
                  style: const TextStyle(
                      fontWeight: FontWeight.w800, color: foodflow.ink))),
        ]),
      ),
    );
  }

  Widget _menuTile(MenuItem item) {
    final selected = _selectedItems.contains(item.id);
    return InkWell(
      onTap: () => setState(() => selected
          ? _selectedItems.remove(item.id)
          : _selectedItems.add(item.id)),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 7),
        child: Row(children: [
          NetworkImageLoader(
              imageUrl: item.imageUrl,
              width: 52,
              height: 52,
              borderRadius: BorderRadius.circular(8)),
          const SizedBox(width: 10),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text(item.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontWeight: FontWeight.w800, color: foodflow.ink)),
                Text(
                    '${_money(item.finalPrice)}${item.totalOrders > 0 ? ' - ${item.totalOrders} orders' : ''}',
                    style: const TextStyle(
                        fontSize: 12,
                        color: foodflow.inkSoft,
                        fontWeight: FontWeight.w700)),
              ])),
          Icon(
              selected
                  ? Icons.check_box_rounded
                  : Icons.check_box_outline_blank_rounded,
              color: selected ? _purple : foodflow.faint),
        ]),
      ),
    );
  }

  Widget _placement(
      String title, String subtitle, String value, IconData icon) {
    final selected = _placements.contains(value);
    return InkWell(
      onTap: () => setState(() {
        _autoPlacement = false;
        selected ? _placements.remove(value) : _placements.add(value);
      }),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: _selectDecoration(selected),
        child: Row(children: [
          _iconBox(icon, selected ? _purple : foodflow.muted),
          const SizedBox(width: 12),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text(title,
                    style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w800,
                        color: foodflow.ink)),
                Text(subtitle,
                    style: const TextStyle(
                        fontSize: 11.5, color: foodflow.inkSoft)),
              ])),
          Icon(
              selected
                  ? Icons.check_box_rounded
                  : Icons.check_box_outline_blank_rounded,
              color: selected ? _purple : foodflow.faint,
              size: 21),
        ]),
      ),
    );
  }

  Widget _check(String label, String value, Set<String> group,
          void Function(String, bool) onChanged,
          {String? trailing}) =>
      CheckboxListTile(
        value: group.contains(value),
        dense: true,
        contentPadding: EdgeInsets.zero,
        activeColor: _purple,
        controlAffinity: ListTileControlAffinity.leading,
        title: Text(label,
            style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: foodflow.ink)),
        secondary: trailing == null
            ? null
            : Text(trailing,
                style: const TextStyle(
                    fontSize: 11,
                    color: foodflow.muted,
                    fontWeight: FontWeight.w700)),
        onChanged: (checked) => onChanged(value, checked ?? false),
      );

  Widget _dateRange() => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: foodflow.line)),
        child: Row(children: [
          Expanded(
              child: TextButton(
                  onPressed: () => _pickDate(true),
                  child: Text(DateFormat('d MMM yyyy').format(_startsAt),
                      style: const TextStyle(fontWeight: FontWeight.w800)))),
          const Icon(Icons.arrow_forward_rounded,
              size: 18, color: foodflow.muted),
          Expanded(
              child: TextButton(
                  onPressed: () => _pickDate(false),
                  child: Text(DateFormat('d MMM yyyy').format(_endsAt),
                      style: const TextStyle(fontWeight: FontWeight.w800)))),
        ]),
      );

  Future<void> _pickDate(bool start) async {
    final picked = await showDatePicker(
        context: context,
        initialDate: start ? _startsAt : _endsAt,
        firstDate: start
            ? DateTime.now().subtract(const Duration(days: 1))
            : _startsAt,
        lastDate: DateTime.now().add(const Duration(days: 365)));
    if (picked == null) return;
    setState(() {
      if (start) {
        _startsAt = picked;
        if (_endsAt.isBefore(_startsAt))
          _endsAt = _startsAt.add(const Duration(days: 6));
      } else {
        _endsAt = picked;
      }
    });
  }

  Widget _adPreview({required bool compact}) {
    final item = _previewItem();
    final offer = _offer.text.trim();
    return Container(
      decoration: _cardDecoration(),
      clipBehavior: Clip.antiAlias,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if ((item?.imageUrl ?? '').isNotEmpty)
          NetworkImageLoader(
              imageUrl: item!.imageUrl,
              width: double.infinity,
              height: compact ? 120 : 150,
              borderRadius: BorderRadius.zero)
        else
          Container(
              height: compact ? 95 : 130,
              color: _lilac,
              alignment: Alignment.center,
              child: const Icon(Icons.restaurant_rounded,
                  color: _purple, size: 42)),
        Padding(
          padding: const EdgeInsets.all(12),
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(
                  child: Text(_creativeHeadline(),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontWeight: FontWeight.w800, color: foodflow.ink))),
              if (offer.isNotEmpty) _offerBadge(offer)
            ]),
            const SizedBox(height: 5),
            Text(_creativeDescription(),
                maxLines: compact ? 1 : 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: foodflow.inkSoft)),
            const SizedBox(height: 8),
            Row(children: [
              const Icon(Icons.star_rounded, size: 15, color: _gold),
              const SizedBox(width: 3),
              Text(item?.rating?.toStringAsFixed(1) ?? 'Sponsored',
                  style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: foodflow.inkSoft)),
              if (item != null) ...[
                const SizedBox(width: 10),
                Text(_money(item.finalPrice),
                    style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: foodflow.ink))
              ]
            ]),
          ]),
        ),
      ]),
    );
  }

  Widget _infoPanel(
          String title, List<MapEntry<String, String>> rows, String note,
          {bool success = false}) =>
      Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
            color: success ? const Color(0xFFF0FDF4) : Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
                color: success ? const Color(0xFFBBF7D0) : foodflow.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title,
              style: const TextStyle(
                  fontWeight: FontWeight.w800, color: foodflow.ink)),
          const SizedBox(height: 10),
          ...rows.map((row) => Padding(
              padding: const EdgeInsets.only(bottom: 7),
              child: Row(children: [
                Expanded(
                    child: Text(row.key,
                        style: const TextStyle(
                            fontSize: 12,
                            color: foodflow.inkSoft,
                            fontWeight: FontWeight.w700))),
                Text(row.value,
                    style: const TextStyle(
                        fontSize: 12,
                        color: foodflow.ink,
                        fontWeight: FontWeight.w800))
              ]))),
          const SizedBox(height: 4),
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Icon(success ? Icons.verified_rounded : Icons.info_outline_rounded,
                size: 16, color: success ? _green : _purple),
            const SizedBox(width: 8),
            Expanded(
                child: Text(note,
                    style: TextStyle(
                        fontSize: 11.5,
                        color: success ? Colors.green.shade800 : foodflow.muted,
                        height: 1.35,
                        fontWeight: FontWeight.w700)))
          ]),
        ]),
      );

  Widget _review(String label, String value) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(children: [
        Expanded(
            child: Text(label,
                style: const TextStyle(
                    color: foodflow.muted, fontWeight: FontWeight.w700))),
        Flexible(
            child: Text(value,
                textAlign: TextAlign.right,
                style: const TextStyle(
                    color: foodflow.ink, fontWeight: FontWeight.w800)))
      ]));
  Widget _section(String label) => Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(label,
          style: const TextStyle(
              fontWeight: FontWeight.w800, color: foodflow.ink)));
  Widget _emptyInline(String message) => Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
          color: foodflow.canvas,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: foodflow.line)),
      child: Text(message,
          style: const TextStyle(
              color: foodflow.muted, fontWeight: FontWeight.w700)));
  Widget _note(String message) => Container(
      margin: const EdgeInsets.only(top: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
          color: const Color(0xFFFFFBEB),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFFDE68A))),
      child: Row(children: [
        const Icon(Icons.lightbulb_outline_rounded, color: _gold, size: 18),
        const SizedBox(width: 10),
        Expanded(
            child: Text(message,
                style: const TextStyle(
                    fontSize: 12,
                    color: foodflow.inkSoft,
                    fontWeight: FontWeight.w700,
                    height: 1.35)))
      ]));
}

class AdCampaignSuccessScreen extends StatelessWidget {
  const AdCampaignSuccessScreen({Key? key, required this.campaign})
      : super(key: key);
  final Map<String, dynamic> campaign;

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: foodflow.canvas,
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child:
                Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              Container(
                  width: 92,
                  height: 92,
                  decoration: BoxDecoration(
                      color: _green.withOpacity(.14), shape: BoxShape.circle),
                  child:
                      const Icon(Icons.check_rounded, color: _green, size: 54)),
              const SizedBox(height: 24),
              const Text('Campaign Submitted!',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: foodflow.ink)),
              const SizedBox(height: 10),
              Text(campaign['name']?.toString() ?? 'Campaign',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: foodflow.inkSoft)),
              const SizedBox(height: 12),
              const Text(
                  'Your campaign is being reviewed. You will be notified once it goes live.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: foodflow.muted, height: 1.45)),
              const SizedBox(height: 28),
              SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                      onPressed: () => Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(
                              builder: (_) =>
                                  CampaignDashboardScreen(campaign: campaign))),
                      style: _primaryButton(),
                      child: const Text('View Campaign'))),
              const SizedBox(height: 10),
              SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: _outlineButton(_purple),
                      child: const Text('Create Another Campaign'))),
            ]),
          ),
        ),
      );
}

class CampaignDashboardScreen extends StatefulWidget {
  const CampaignDashboardScreen({Key? key, required this.campaign})
      : super(key: key);
  final Map<String, dynamic> campaign;
  @override
  State<CampaignDashboardScreen> createState() =>
      _CampaignDashboardScreenState();
}

class _CampaignDashboardScreenState extends State<CampaignDashboardScreen> {
  final _api = ApiService();
  late Map<String, dynamic> _campaign = widget.campaign;
  var _loading = false;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    final id = _int(_campaign['id']);
    if (id == null) return;
    setState(() => _loading = true);
    try {
      final response = await _api.get(ApiConstants.restaurantAdCampaign(id));
      if (response['success'] == true && mounted)
        setState(() => _campaign = _map(response['data']));
    } catch (error) {
      debugPrint('Load campaign dashboard error: $error');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _money(BuildContext context, dynamic value) =>
      formatCurrencyValue(context, _double(value));

  @override
  Widget build(BuildContext context) {
    final metrics = _map(_campaign['metrics']);
    final status = _campaign['status']?.toString() ?? 'draft';
    final color = _statusColors[status] ?? Colors.grey;
    final daily = (_campaign['daily_metrics'] as List? ?? const [])
        .whereType<Map>()
        .map((row) => Map<String, dynamic>.from(row))
        .toList();
    return Scaffold(
      backgroundColor: foodflow.canvas,
      appBar: AppBar(title: Text(_campaign['name']?.toString() ?? 'Campaign')),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: _purple,
        child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
            children: [
              if (_loading)
                const LinearProgressIndicator(color: _purple, minHeight: 2),
              Container(
                  padding: const EdgeInsets.all(16),
                  decoration: _cardDecoration(),
                  child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [
                          Expanded(
                              child: Text(
                                  _campaign['name']?.toString() ?? 'Campaign',
                                  style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w800,
                                      color: foodflow.ink))),
                          _statusBadge(status, color)
                        ]),
                        const SizedBox(height: 8),
                        Text('ID: ${_campaign['id'] ?? '-'}',
                            style: const TextStyle(
                                fontSize: 12,
                                color: foodflow.muted,
                                fontWeight: FontWeight.w700)),
                      ])),
              const SizedBox(height: 14),
              GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: 1.45,
                  children: [
                    _dashboardMetric(
                        'Spend',
                        _money(context,
                            metrics['spend'] ?? _campaign['spent_total']),
                        Icons.payments_outlined,
                        _green),
                    _dashboardMetric(
                        'Avg. CPC',
                        _money(context, metrics['avg_cpc']),
                        Icons.gavel_outlined,
                        _purple),
                    _dashboardMetric(
                        'Clicks',
                        _compact(_int(metrics['clicks']) ?? 0),
                        Icons.ads_click_outlined,
                        Colors.blue),
                    _dashboardMetric(
                        'CTR',
                        '${_double(metrics['ctr']).toStringAsFixed(2)}%',
                        Icons.insights_outlined,
                        _gold),
                  ]),
              const SizedBox(height: 16),
              Container(
                  padding: const EdgeInsets.all(16),
                  decoration: _cardDecoration(),
                  child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: const [
                          Expanded(
                              child: Text('Performance Overview',
                                  style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                      color: foodflow.ink))),
                          Text('Real-time',
                              style: TextStyle(
                                  fontSize: 12,
                                  color: foodflow.muted,
                                  fontWeight: FontWeight.w700))
                        ]),
                        const SizedBox(height: 14),
                        SizedBox(
                            height: 160,
                            child: daily.isEmpty
                                ? const Center(
                                    child: Text(
                                        'Trend appears once ad impressions or clicks are recorded.',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                            color: foodflow.muted,
                                            fontWeight: FontWeight.w700)))
                                : CustomPaint(
                                    painter: _CampaignChartPainter(daily),
                                    size: Size.infinite)),
                      ])),
              const SizedBox(height: 16),
              Container(
                  padding: const EdgeInsets.all(16),
                  decoration: _cardDecoration(),
                  child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Budget',
                            style: TextStyle(
                                fontWeight: FontWeight.w800,
                                color: foodflow.ink)),
                        const SizedBox(height: 12),
                        _dashRow(
                            'Daily Budget',
                            _campaign['daily_budget'] == null
                                ? 'Unlimited'
                                : _money(context, _campaign['daily_budget'])),
                        _dashRow(
                            'Total Budget',
                            _campaign['total_budget'] == null
                                ? 'Unlimited'
                                : _money(context, _campaign['total_budget'])),
                        _dashRow(
                            'Remaining',
                            _campaign['budget_remaining'] == null
                                ? 'No fixed cap'
                                : _money(
                                    context, _campaign['budget_remaining'])),
                        _dashRow('Wallet Balance',
                            _money(context, _campaign['wallet_balance'])),
                      ])),
            ]),
      ),
    );
  }

  Widget _dashboardMetric(
          String label, String value, IconData icon, Color color) =>
      Container(
        padding: const EdgeInsets.all(13),
        decoration: _cardDecoration(radius: 8),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: color, size: 20),
          const Spacer(),
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: foodflow.ink)),
          Text(label,
              style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: foodflow.muted))
        ]),
      );

  Widget _dashRow(String label, String value) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(children: [
        Expanded(
            child: Text(label,
                style: const TextStyle(
                    color: foodflow.muted, fontWeight: FontWeight.w700))),
        Text(value,
            style: const TextStyle(
                color: foodflow.ink, fontWeight: FontWeight.w800))
      ]));
}

class _CampaignChartPainter extends CustomPainter {
  _CampaignChartPainter(this.rows);
  final List<Map<String, dynamic>> rows;

  @override
  void paint(Canvas canvas, Size size) {
    final grid = Paint()
      ..color = foodflow.line
      ..strokeWidth = 1;
    for (var i = 0; i < 4; i++) {
      final y = size.height * i / 3;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), grid);
    }
    _line(
        canvas,
        size,
        rows.map((row) => _double(row['impressions'])).toList(),
        Paint()
          ..color = _green
          ..strokeWidth = 2.4
          ..style = PaintingStyle.stroke);
    _line(
        canvas,
        size,
        rows.map((row) => _double(row['clicks'])).toList(),
        Paint()
          ..color = _purple
          ..strokeWidth = 2.4
          ..style = PaintingStyle.stroke);
  }

  void _line(Canvas canvas, Size size, List<double> values, Paint paint) {
    if (values.isEmpty) return;
    final maxValue =
        values.fold<double>(0, (max, value) => value > max ? value : max);
    final path = Path();
    for (var i = 0; i < values.length; i++) {
      final x = values.length == 1
          ? size.width
          : size.width * i / (values.length - 1);
      final y = maxValue <= 0
          ? size.height
          : size.height - ((values[i] / maxValue) * (size.height - 12)) - 6;
      if (i == 0) {
        path.moveTo(x, y);
      } else {
        path.lineTo(x, y);
      }
    }
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _CampaignChartPainter oldDelegate) =>
      oldDelegate.rows != rows;
}

class AdWalletTopUpScreen extends StatefulWidget {
  const AdWalletTopUpScreen({Key? key}) : super(key: key);
  @override
  State<AdWalletTopUpScreen> createState() => _AdWalletTopUpScreenState();
}

class _AdWalletTopUpScreenState extends State<AdWalletTopUpScreen> {
  static const _quickAmounts = [500, 1000, 2500, 5000];
  final _amount = TextEditingController();
  AdWalletTopUpService? _service;
  var _processing = false;

  @override
  void dispose() {
    _service?.dispose();
    _amount.dispose();
    super.dispose();
  }

  Future<void> _startTopUp() async {
    final amount = double.tryParse(_amount.text.trim());
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Enter a valid amount')));
      return;
    }
    setState(() => _processing = true);
    _service = AdWalletTopUpService(
      onSuccess: () async {
        if (!mounted) return;
        setState(() => _processing = false);
        Navigator.pop(context, true);
      },
      onFailure: (message) {
        if (!mounted) return;
        setState(() => _processing = false);
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      },
    );
    try {
      await _service!.start(
          amount: amount, user: context.read<AuthProvider>().currentUser);
    } catch (error) {
      if (!mounted) return;
      setState(() => _processing = false);
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Unable to start payment: $error')));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: foodflow.canvas,
        appBar: AppBar(title: const Text('Top Up Ad Wallet')),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(18),
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                    color: _lilac, borderRadius: BorderRadius.circular(8)),
                child: const Text(
                    'This balance is used only for sponsored placements. It does not affect your order earnings wallet.',
                    style: TextStyle(
                        color: foodflow.inkSoft,
                        fontWeight: FontWeight.w700,
                        height: 1.4))),
            const SizedBox(height: 22),
            const _FieldLabel('Enter amount'),
            TextField(
                controller: _amount,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: foodflow.ink),
                decoration: InputDecoration(
                    prefixText: currencyInputPrefix(context), hintText: '0')),
            const SizedBox(height: 14),
            Wrap(
                spacing: 10,
                runSpacing: 10,
                children: _quickAmounts.map((amount) {
                  final selected = _amount.text.trim() == amount.toString();
                  return ChoiceChip(
                      selected: selected,
                      selectedColor: _purple,
                      label: Text(
                          '+ ${formatCurrencyValue(context, amount.toDouble())}'),
                      labelStyle: TextStyle(
                          color: selected ? Colors.white : foodflow.ink,
                          fontWeight: FontWeight.w800),
                      onSelected: (_) =>
                          setState(() => _amount.text = amount.toString()));
                }).toList()),
          ]),
        ),
        bottomNavigationBar: SafeArea(
          minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: ElevatedButton(
              onPressed: _processing ? null : _startTopUp,
              style: _primaryButton(),
              child: _processing
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Text('Proceed to Pay')),
        ),
      );
}

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.label);
  final String label;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 7),
      child: Text(label,
          style: const TextStyle(
              fontSize: 12, color: foodflow.ink, fontWeight: FontWeight.w800)));
}

BoxDecoration _gradientBox({double radius = 8}) => BoxDecoration(
        gradient: const LinearGradient(
            colors: [_purple, _purpleDark],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(radius),
        boxShadow: [
          BoxShadow(
              color: _purple.withOpacity(.18),
              blurRadius: 18,
              offset: const Offset(0, 10))
        ]);
BoxDecoration _cardDecoration({double radius = 8}) => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(radius),
      border: Border.all(color: foodflow.line),
    );
BoxDecoration _selectDecoration(bool selected, {double radius = 8}) =>
    BoxDecoration(
        color: selected ? _lilac : Colors.white,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(
            color: selected ? _purple : foodflow.line,
            width: selected ? 1.2 : 1));
ButtonStyle _primaryButton({bool compact = false}) => ElevatedButton.styleFrom(
    backgroundColor: _purple,
    foregroundColor: Colors.white,
    elevation: 0,
    minimumSize: Size(0, compact ? 38 : 46),
    padding: EdgeInsets.symmetric(horizontal: 18, vertical: compact ? 10 : 13),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
    textStyle:
        TextStyle(fontSize: compact ? 13 : 15, fontWeight: FontWeight.w700));
ButtonStyle _outlineButton(Color color, {bool compact = false}) =>
    OutlinedButton.styleFrom(
        foregroundColor: color,
        side: BorderSide(color: color.withOpacity(.35)),
        backgroundColor: color.withOpacity(.04),
        minimumSize: Size(0, compact ? 36 : 44),
        padding:
            EdgeInsets.symmetric(horizontal: 14, vertical: compact ? 9 : 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
        textStyle: TextStyle(
            fontSize: compact ? 12 : 14, fontWeight: FontWeight.w800));
Widget _iconBox(IconData icon, Color color) => Container(
    width: 34,
    height: 34,
    decoration: BoxDecoration(
        color: color.withOpacity(.12), borderRadius: BorderRadius.circular(8)),
    child: Icon(icon, color: color, size: 19));
Widget _smallBadge(String label) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
    decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: _purple.withOpacity(.35))),
    child: Text(label,
        style: const TextStyle(
            fontSize: 9, color: _purple, fontWeight: FontWeight.w800)));
Widget _offerBadge(String label) => Container(
    constraints: const BoxConstraints(maxWidth: 110),
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
    decoration:
        BoxDecoration(color: _red, borderRadius: BorderRadius.circular(8)),
    child: Text(label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(
            fontSize: 10, color: Colors.white, fontWeight: FontWeight.w800)));
Widget _statusBadge(String status, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
    decoration: BoxDecoration(
        color: color.withOpacity(.12),
        borderRadius: BorderRadius.circular(999)),
    child: Text(_statusLabels[status] ?? status,
        style: TextStyle(
            fontSize: 10.5, color: color, fontWeight: FontWeight.w800)));
Widget _miniChip(IconData icon, String label) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
    decoration:
        BoxDecoration(color: _lilac, borderRadius: BorderRadius.circular(8)),
    child: Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, color: _purple, size: 13),
      const SizedBox(width: 5),
      Text(label,
          style: const TextStyle(
              fontSize: 11, fontWeight: FontWeight.w800, color: _purpleDark))
    ]));
String _compact(int value) => value >= 1000000
    ? '${(value / 1000000).toStringAsFixed(1)}M'
    : value >= 1000
        ? '${(value / 1000).toStringAsFixed(1)}K'
        : value.toString();
String _labelFor(String value) =>
    const {
      'orders': 'Get More Orders',
      'visibility': 'Get More Visibility',
      'new_restaurant': 'Promote New Restaurant',
      'specific_items': 'Promote Specific Items',
      'offer': 'Promote an Offer',
      'winback': 'Bring Customers Back',
      'restaurant': 'Restaurant',
      'items': 'Selected Items',
      'category': 'Category',
      'combo': 'Combo / Meal'
    }[value] ??
    value;
Map<String, dynamic> _map(dynamic value) => value is Map<String, dynamic>
    ? value
    : value is Map
        ? Map<String, dynamic>.from(value)
        : <String, dynamic>{};
int? _int(dynamic value) => value is int
    ? value
    : value is num
        ? value.toInt()
        : int.tryParse(value?.toString() ?? '');
double _double(dynamic value) => value is num
    ? value.toDouble()
    : double.tryParse(value?.toString() ?? '') ?? 0;
