import 'package:flutter/material.dart';

import '../theme/onboard_theme.dart';
import 'onboard_chrome.dart';

class KpiCard extends StatelessWidget {
  const KpiCard({super.key, required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final tone = item['tone']?.toString();
    final accent = switch (tone) {
      'mint' => OnboardColors.success,
      'sky' => OnboardColors.primary,
      'amber' => OnboardColors.warning,
      'rose' => OnboardColors.danger,
      _ => OnboardColors.accent,
    };
    final icon = switch (tone) {
      'mint' => Icons.verified_outlined,
      'sky' => Icons.trending_up,
      'amber' => Icons.pending_actions_outlined,
      'rose' => Icons.priority_high_outlined,
      _ => Icons.analytics_outlined,
    };

    return SizedBox(
      width: 250,
      child: GlassPanel(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 38,
              height: 38,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: accent.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, size: 20, color: accent),
            ),
            const SizedBox(height: 12),
            Text(
              '${item['value'] ?? 0}',
              style: const TextStyle(
                color: OnboardColors.ink,
                fontSize: 32,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              item['label']?.toString() ?? '',
              style: const TextStyle(
                color: OnboardColors.inkSoft,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              '+${item['delta'] ?? 0} this month',
              style: const TextStyle(
                color: OnboardColors.muted,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
