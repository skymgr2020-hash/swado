import 'dart:async';

import 'package:flutter/material.dart';

import 'theatre_api_service.dart';
import 'theatre_models.dart';
import 'theatre_theme.dart';

class TheatreConfirmationScreen extends StatefulWidget {
  const TheatreConfirmationScreen({super.key, required this.result});

  final TheatreOrderGroupResult result;

  @override
  State<TheatreConfirmationScreen> createState() =>
      _TheatreConfirmationScreenState();
}

class _TheatreConfirmationScreenState
    extends State<TheatreConfirmationScreen> {
  late Future<Map<String, dynamic>> _future;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _future = TheatreApiService.instance.orderGroup(widget.result.orderGroupId);
    _refreshTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      if (mounted && (ModalRoute.of(context)?.isCurrent ?? false)) _refresh();
    });
  }

  void _refresh() {
    setState(() => _future = TheatreApiService.instance.orderGroup(widget.result.orderGroupId));
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BmsTheme.bgLight,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: BmsTheme.textDark,
        elevation: 0.5,
        automaticallyImplyLeading: false,
        title: const Text(
          'Order Confirmed',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: BmsTheme.textDark,
          ),
        ),
        actions: [
          IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh), tooltip: 'Refresh orders'),
          TextButton(
            onPressed: () =>
                Navigator.of(context).popUntil((route) => route.isFirst),
            style: TextButton.styleFrom(foregroundColor: BmsTheme.primary),
            child: const Text(
              'Done',
              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            final data = snapshot.data;
            final group = Map<String, dynamic>.from(
              (data?['order_group'] as Map?) ?? {},
            );
            final orders = ((data?['orders'] as List?) ?? const [])
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();

            final theatreName =
                group['theatre']?['name']?.toString() ?? 'Cinema Hall';
            final screenName =
                group['screen']?['name']?.toString() ?? 'AUDI';
            final seatLabel = group['seat_label']?.toString() ?? '';

            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
              children: [
                if (snapshot.hasError)
                  const Padding(
                    padding: EdgeInsets.all(12),
                    child: Text('Could not refresh delivery status. Tap refresh to try again.'),
                  ),
                // Top Success Pill
                Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFECFDF5),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: const Color(0xFFA7F3D0),
                      ),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.check_circle_rounded,
                          color: Color(0xFF059669),
                          size: 16,
                        ),
                        SizedBox(width: 6),
                        Text(
                          'ORDER PLACED SUCCESSFULLY',
                          style: TextStyle(
                            color: Color(0xFF059669),
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 14),

                // BookMyShow Style Digital M-Ticket Card
                BmsTicketCard(
                  backgroundColor: Colors.white,
                  borderColor: const Color(0xFFE5E7EB),
                  notchColor: BmsTheme.bgLight,
                  header: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFF1F3),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: const Color(0xFFFFD4DC),
                                ),
                              ),
                              child: const Center(
                                child: Icon(
                                  Icons.confirmation_number_rounded,
                                  color: BmsTheme.primary,
                                  size: 20,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    theatreName,
                                    style: const TextStyle(
                                      color: Color(0xFF111827),
                                      fontWeight: FontWeight.w800,
                                      fontSize: 16,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Order Group #${widget.result.groupNumber}',
                                    style: const TextStyle(
                                      color: Color(0xFF6B7280),
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        // Prominent Golden Seat Delivery Banner
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFFBEB),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: const Color(0xFFFDE68A),
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(
                                Icons.event_seat_rounded,
                                color: Color(0xFFD97706),
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                '${screenName.toUpperCase()} · SEAT $seatLabel',
                                style: const TextStyle(
                                  color: Color(0xFF92400E),
                                  fontSize: 15,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 0.6,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        // Digital Pass QR Code for Runner Scanning
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE5E7EB)),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.06),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: SelectableText(widget.result.groupNumber),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Share each stall delivery code below when its food arrives.',
                          style: TextStyle(
                            color: Color(0xFF6B7280),
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                  body: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Live Order Timeline Tracker
                        _buildDeliveryTimeline(orders),

                        const SizedBox(height: 16),
                        const Divider(
                          color: Color(0xFFE5E7EB),
                          height: 1,
                        ),
                        const SizedBox(height: 14),

                        // Stall breakdown
                        const Text(
                          'STALL ORDERS',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF6B7280),
                            letterSpacing: 0.8,
                          ),
                        ),
                        const SizedBox(height: 10),

                        if (snapshot.connectionState != ConnectionState.done && !snapshot.hasData)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.all(16),
                              child: CircularProgressIndicator(
                                color: BmsTheme.primary,
                              ),
                            ),
                          )
                        else if (orders.isEmpty)
                          Padding(
                            padding: const EdgeInsets.all(8.0),
                            child: Text(
                              'Order Total: ₹${widget.result.total.toStringAsFixed(0)}',
                              style: const TextStyle(
                                color: Color(0xFF111827),
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          )
                        else
                          ...orders.map((order) {
                            final restaurant = Map<String, dynamic>.from(
                              order['restaurant'] as Map? ?? {},
                            );
                            final status =
                                _statusLabel(order['status']?.toString());

                            return Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF9FAFB),
                                borderRadius: BorderRadius.circular(10),
                                border:
                                    Border.all(color: const Color(0xFFE5E7EB)),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          restaurant['name']?.toString() ??
                                              'Concession Stall',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 13,
                                            color: Color(0xFF111827),
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          '#${order['order_number']} · $status',
                                          style: const TextStyle(
                                            fontSize: 11.5,
                                            color: BmsTheme.primary,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        if (order['delivery_otp'] != null &&
                                            order['status'] != 'delivered' &&
                                            order['status'] != 'cancelled' &&
                                            order['status'] != 'rejected')
                                          Padding(
                                            padding: const EdgeInsets.only(top: 8),
                                            child: SelectableText('Delivery code: ${order['delivery_otp']}',
                                              style: const TextStyle(fontWeight: FontWeight.bold)),
                                          ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    '₹${order['total']}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w900,
                                      fontSize: 14,
                                      color: Color(0xFF111827),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          }),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 20),

                // Done Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => Navigator.of(context)
                        .popUntil((route) => route.isFirst),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: BmsTheme.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 4,
                      shadowColor: BmsTheme.primary.withValues(alpha: 0.4),
                    ),
                    child: const Text(
                      'Back to Home',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.3,
                      ),
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildDeliveryTimeline(List<Map<String, dynamic>> orders) {
    final active = orders.where((order) =>
        order['status'] != 'cancelled' && order['status'] != 'rejected').toList();
    if (active.isEmpty) {
      return Text(orders.isEmpty ? 'Loading delivery status?' : 'These orders were cancelled.');
    }
    if (active.any((order) => order['scheduled_for'] != null &&
        order['released_at'] == null && order['status'] == 'pending')) {
      return const Text('Scheduled for the interval. Preparation starts shortly before delivery.');
    }
    int stage(Map<String, dynamic> order) {
      switch (order['status']) {
        case 'delivered': return 3;
        case 'picked_up':
        case 'on_the_way': return 2;
        case 'confirmed':
        case 'preparing':
        case 'ready_for_pickup':
        case 'reached_pickup': return 1;
        default: return 0;
      }
    }
    final progress = active.map(stage).reduce((a, b) => a < b ? a : b);
    return Column(children: [
      _TimelineStep(icon: Icons.receipt_long_rounded, title: 'Order placed',
        subtitle: 'Waiting for the stalls to accept', isDone: progress > 0, isCurrent: progress == 0),
      _TimelineStep(icon: Icons.soup_kitchen_rounded, title: 'Preparing at concessions',
        subtitle: 'See each stall status below', isDone: progress > 1, isCurrent: progress == 1),
      _TimelineStep(icon: Icons.directions_run_rounded, title: progress == 3 ? 'Delivered' : 'Runner delivering to your seat',
        subtitle: 'Share the delivery code when your food arrives', isDone: progress == 3, isCurrent: progress == 2, isLast: true),
    ]);
  }

  String _statusLabel(String? status) {
    if (status == null || status.isEmpty) return 'Pending';
    return status[0].toUpperCase() + status.substring(1).replaceAll('_', ' ');
  }
}

class _TimelineStep extends StatelessWidget {
  const _TimelineStep({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.isDone,
    required this.isCurrent,
    this.isLast = false,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool isDone;
  final bool isCurrent;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final activeColor = isDone
        ? const Color(0xFF059669)
        : (isCurrent ? BmsTheme.primary : const Color(0xFF9CA3AF));

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 26,
                height: 26,
                decoration: BoxDecoration(
                  color: isDone
                      ? const Color(0xFFECFDF5)
                      : (isCurrent
                          ? const Color(0xFFFFF1F3)
                          : const Color(0xFFF3F4F6)),
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: isDone
                        ? const Color(0xFF10B981)
                        : (isCurrent
                            ? BmsTheme.primary
                            : const Color(0xFFD1D5DB)),
                    width: isCurrent || isDone ? 1.5 : 1.0,
                  ),
                ),
                child: Center(
                  child: Icon(
                    isDone ? Icons.check_rounded : icon,
                    size: 13,
                    color: activeColor,
                  ),
                ),
              ),
              if (!isLast)
                Expanded(
                  child: Container(
                    width: 1.5,
                    color: isDone
                        ? const Color(0xFFA7F3D0)
                        : const Color(0xFFE5E7EB),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight:
                          isCurrent || isDone ? FontWeight.w800 : FontWeight.w600,
                      color: isCurrent || isDone
                          ? const Color(0xFF111827)
                          : const Color(0xFF6B7280),
                    ),
                  ),
                  const SizedBox(height: 1),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
