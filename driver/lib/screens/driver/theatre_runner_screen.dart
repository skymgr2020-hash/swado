import 'dart:async';

import 'package:flutter/material.dart';

import '../../services/api_service.dart';
import 'driver_order_detail_screen.dart';
import 'driver_profile_screen.dart';

class TheatreRunnerScreen extends StatefulWidget {
  const TheatreRunnerScreen({super.key});

  @override
  State<TheatreRunnerScreen> createState() => _TheatreRunnerScreenState();
}

class _TheatreRunnerScreenState extends State<TheatreRunnerScreen> {
  final ApiService _api = ApiService();
  Timer? _refreshTimer;
  List<Map<String, dynamic>> _claimable = [];
  List<Map<String, dynamic>> _mine = [];
  bool _loading = true;
  bool _refreshing = false;
  int? _claiming;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
    _refreshTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      if (ModalRoute.of(context)?.isCurrent ?? false) _load();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      final response = await _api.get('/theatre-runner/queue');
      if (response['success'] != true) {
        throw Exception(response['message'] ?? 'Could not load the queue.');
      }
      final data = response['data'] as Map;
      if (!mounted) return;
      setState(() {
        _claimable = (data['claimable'] as List)
            .map((item) => Map<String, dynamic>.from(item as Map)).toList();
        _mine = (data['mine'] as List)
            .map((item) => Map<String, dynamic>.from(item as Map)).toList();
        _error = null;
      });
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      _refreshing = false;
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _claim(Map<String, dynamic> order) async {
    if (_claiming != null) return;
    final id = int.parse(order['id'].toString());
    setState(() => _claiming = id);
    try {
      final response = await _api.post('/theatre-runner/orders/$id/claim');
      if (response['success'] != true) {
        throw Exception(response['message'] ?? 'Could not claim this order.');
      }
      if (!mounted) return;
      await Navigator.of(context).push(MaterialPageRoute<void>(
        builder: (_) => DriverOrderDetailScreen(orderId: id),
      ));
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error.toString())),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _claiming = null);
        await _load();
      }
    }
  }

  Widget _orderCard(Map<String, dynamic> order, {required bool claimable}) {
    final restaurant = order['restaurant'] as Map?;
    final id = int.parse(order['id'].toString());
    return Card(
      child: ListTile(
        isThreeLine: true,
        title: Text('${restaurant?['name'] ?? 'Stall'} · #${order['order_number']}'),
        subtitle: Text(
          '${order['screen_name'] ?? 'Screen'} · Seat ${order['seat_label'] ?? ''}\n'
          '${order['aisle_handoff_only'] == true ? 'Hand off at the aisle' : 'Deliver to the seat'} · '
          '${order['status'].toString().replaceAll('_', ' ')}',
        ),
        trailing: claimable
            ? FilledButton(
                onPressed: _claiming == null ? () => _claim(order) : null,
                child: Text(_claiming == id ? 'Claiming…' : 'Claim'),
              )
            : const Icon(Icons.chevron_right),
        onTap: claimable ? null : () async {
          await Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => DriverOrderDetailScreen(orderId: id),
          ));
          if (mounted) await _load();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Theatre deliveries'),
        actions: [
          IconButton(onPressed: _load, icon: const Icon(Icons.refresh), tooltip: 'Refresh'),
          IconButton(
            tooltip: 'Profile',
            icon: const Icon(Icons.person_outline),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(
              builder: (_) => const DriverProfileScreen(showAppBar: true),
            )),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                children: [
                  if (_error != null) Padding(
                    padding: const EdgeInsets.only(bottom: 16),
                    child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ),
                  Text('My deliveries', style: Theme.of(context).textTheme.titleLarge),
                  if (_mine.isEmpty) const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Text('Claim a ready order to start delivering.'),
                  ),
                  ..._mine.map((order) => _orderCard(order, claimable: false)),
                  const SizedBox(height: 24),
                  Text('Ready to collect', style: Theme.of(context).textTheme.titleLarge),
                  if (_claimable.isEmpty) const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Text('No orders are ready yet. This list refreshes automatically.'),
                  ),
                  ..._claimable.map((order) => _orderCard(order, claimable: true)),
                ],
              ),
            ),
    );
  }
}
