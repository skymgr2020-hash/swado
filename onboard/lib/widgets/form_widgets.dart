import 'package:flutter/material.dart';

import '../services/form_helpers.dart';
import '../theme/onboard_theme.dart';
import 'onboard_chrome.dart';

class WizardShell extends StatelessWidget {
  const WizardShell({super.key, required this.enabled, required this.child});

  final bool enabled;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        if (!enabled) ...[
          GlassPanel(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: OnboardColors.warning.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(
                    Icons.lock_outline,
                    color: OnboardColors.warning,
                  ),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Your role cannot submit this onboarding type.',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
        ],
        GlassPanel(padding: const EdgeInsets.all(8), child: child),
      ],
    );
  }
}

class WizardControls extends StatelessWidget {
  const WizardControls({
    super.key,
    required this.step,
    required this.last,
    required this.saving,
    required this.onNext,
    required this.onBack,
  });

  final int step;
  final int last;
  final bool saving;
  final VoidCallback onNext;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: [
          SizedBox(
            width: 180,
            child: GlassButton(
              onPressed: saving ? null : onNext,
              loading: saving,
              icon: step == last ? Icons.check : Icons.arrow_forward,
              label: step == last ? 'Submit' : 'Next',
              compact: true,
            ),
          ),
          OutlinedButton.icon(
            onPressed: saving ? null : onBack,
            icon: const Icon(Icons.arrow_back),
            label: const Text('Back'),
          ),
        ],
      ),
    );
  }
}

class FieldGrid extends StatelessWidget {
  const FieldGrid({super.key, required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, box) {
        final width = box.maxWidth > 760
            ? (box.maxWidth - 14) / 2
            : box.maxWidth;
        return Wrap(
          spacing: 14,
          runSpacing: 14,
          children: [
            for (final child in children)
              SizedBox(
                width: child is SwitchListTile || child is FullWidthField ? box.maxWidth : width,
                child: child,
              ),
          ],
        );
      },
    );
  }
}

class AppField extends StatelessWidget {
  const AppField(
    this.controller,
    this.label,
    this.icon, {
    super.key,
    this.required = false,
    this.number = false,
    this.obscure = false,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool required;
  final bool number;
  final bool obscure;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      obscureText: obscure,
      keyboardType: number
          ? const TextInputType.numberWithOptions(decimal: true, signed: true)
          : TextInputType.text,
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
      validator: required ? requiredText : null,
    );
  }
}

class FullWidthField extends StatelessWidget {
  const FullWidthField({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) => child;
}

class ChipPicker extends StatelessWidget {
  const ChipPicker({
    super.key,
    required this.label,
    required this.options,
    required this.selected,
    required this.onChanged,
    this.multi = false,
    this.emptyLabel = 'No options loaded',
  });

  final String label;
  final List<String> options;
  final Set<String> selected;
  final ValueChanged<Set<String>> onChanged;
  final bool multi;
  final String emptyLabel;

  @override
  Widget build(BuildContext context) {
    return FullWidthField(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w900)),
          const SizedBox(height: 8),
          if (options.isEmpty)
            Chip(label: Text(emptyLabel))
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final option in options)
                  FilterChip(
                    label: Text(_title(option)),
                    selected: selected.contains(option),
                    onSelected: (value) {
                      final next = <String>{...selected};
                      if (multi) {
                        value ? next.add(option) : next.remove(option);
                      } else {
                        next
                          ..clear()
                          ..add(option);
                      }
                      onChanged(next);
                    },
                  ),
              ],
            ),
        ],
      ),
    );
  }

  static String _title(String value) => value
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => part[0].toUpperCase() + part.substring(1))
      .join(' ');
}
