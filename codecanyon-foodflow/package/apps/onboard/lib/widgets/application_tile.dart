import 'package:flutter/material.dart';

import '../theme/onboard_theme.dart';

class ApplicationTile extends StatelessWidget {
  const ApplicationTile({super.key, required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final restaurant = item['type'] == 'restaurant';
    final status = item['status']?.toString() ?? 'pending';
    final accent = restaurant ? OnboardColors.primary : OnboardColors.accent;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        leading: Container(
          width: 42,
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: accent.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(
            restaurant ? Icons.storefront : Icons.two_wheeler,
            color: accent,
          ),
        ),
        title: Text(
          item['name']?.toString() ?? 'Unnamed',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
        subtitle: Text(
          '${item['application_number']} | ${item['city'] ?? 'No city'}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: Chip(label: Text(status)),
      ),
    );
  }
}
