import 'package:flutter/material.dart';

import '../blocs/onboarding_bloc.dart';
import '../theme/onboard_theme.dart';
import '../widgets/application_tile.dart';
import '../widgets/error_panel.dart';
import '../widgets/kpi_card.dart';
import '../widgets/onboard_chrome.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key, required this.bloc});

  final OnboardingBloc bloc;

  @override
  Widget build(BuildContext context) {
    if (bloc.loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (bloc.error != null) {
      return ErrorPanel(message: bloc.error!, onRetry: bloc.refresh);
    }

    final kpis = bloc.dashboard['kpis'] as List<dynamic>? ?? [];
    final ops =
        (bloc.dashboard['operational'] as Map?)?.cast<String, dynamic>() ?? {};

    return RefreshIndicator(
      onRefresh: bloc.refresh,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const OnboardSectionTitle(
            title: 'Today at a glance',
            subtitle:
                'Restaurant and driver onboarding health from admin data.',
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 14,
            runSpacing: 14,
            children: [
              for (final item in kpis)
                KpiCard(item: (item as Map).cast<String, dynamic>()),
            ],
          ),
          const SizedBox(height: 18),
          LayoutBuilder(
            builder: (context, box) {
              final two = box.maxWidth > 900;
              final width = two ? (box.maxWidth - 16) / 2 : box.maxWidth;
              return Wrap(
                spacing: 16,
                runSpacing: 16,
                children: [
                  SizedBox(
                    width: width,
                    child: OpsCard(operational: ops),
                  ),
                  SizedBox(
                    width: width,
                    child: RecentCard(items: bloc.pipeline),
                  ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}

class OpsCard extends StatelessWidget {
  const OpsCard({super.key, required this.operational});

  final Map<String, dynamic> operational;

  @override
  Widget build(BuildContext context) {
    final rows = [
      _OpsRow(
        'Verified restaurants',
        operational['restaurants_verified'],
        Icons.verified_outlined,
      ),
      _OpsRow(
        'Open restaurants',
        operational['restaurants_open'],
        Icons.storefront_outlined,
      ),
      _OpsRow(
        'Active drivers',
        operational['drivers_active'],
        Icons.two_wheeler_outlined,
      ),
      _OpsRow(
        'Submitted today',
        operational['submitted_today'],
        Icons.today_outlined,
      ),
    ];

    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const OnboardSectionTitle(
            title: 'Operational health',
            subtitle: 'Production readiness signals for the onboarding desk.',
          ),
          const SizedBox(height: 12),
          for (final row in rows) row,
        ],
      ),
    );
  }
}

class _OpsRow extends StatelessWidget {
  const _OpsRow(this.label, this.value, this.icon);

  final String label;
  final dynamic value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: OnboardColors.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: OnboardColors.primary, size: 19),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
          Text(
            '${value ?? 0}',
            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
          ),
        ],
      ),
    );
  }
}

class RecentCard extends StatelessWidget {
  const RecentCard({super.key, required this.items});

  final List<dynamic> items;

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const OnboardSectionTitle(
            title: 'Recent pipeline',
            subtitle: 'Latest onboarding submissions and handoff status.',
          ),
          const SizedBox(height: 12),
          if (items.isEmpty)
            const ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(Icons.inbox_outlined),
              title: Text('No applications yet'),
            ),
          for (final raw in items.take(6))
            ApplicationTile(item: (raw as Map).cast<String, dynamic>()),
        ],
      ),
    );
  }
}
