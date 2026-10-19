import 'package:flutter/material.dart';

import '../blocs/auth_bloc.dart';
import '../blocs/onboarding_bloc.dart';
import '../theme/onboard_theme.dart';
import '../widgets/onboard_chrome.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({
    super.key,
    required this.authBloc,
    required this.onboardingBloc,
  });

  final AuthBloc authBloc;
  final OnboardingBloc onboardingBloc;

  @override
  Widget build(BuildContext context) {
    final session = authBloc.session!;
    final roleLabel = _roleLabel(session.role);

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        GlassPanel(
          padding: const EdgeInsets.all(24),
          child: LayoutBuilder(
            builder: (context, box) {
              final wide = box.maxWidth > 680;
              final header = _ProfileHeader(
                name: session.name,
                role: roleLabel,
              );
              final actions = _ProfileActions(
                loading: authBloc.loading,
                onRefresh: () {
                  authBloc.refreshSession();
                  onboardingBloc.refresh();
                },
                onLogout: authBloc.logout,
              );

              if (!wide) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [header, const SizedBox(height: 18), actions],
                );
              }

              return Row(
                children: [
                  Expanded(child: header),
                  const SizedBox(width: 18),
                  SizedBox(width: 280, child: actions),
                ],
              );
            },
          ),
        ),
        if (authBloc.error != null) ...[
          const SizedBox(height: 16),
          GlassPanel(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                const Icon(Icons.info_outline, color: OnboardColors.danger),
                const SizedBox(width: 10),
                Expanded(child: Text(authBloc.error!)),
              ],
            ),
          ),
        ],
        const SizedBox(height: 16),
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
                  child: _DetailsCard(
                    title: 'Account',
                    rows: [
                      _ProfileRow(Icons.person_outline, 'Name', session.name),
                      _ProfileRow(
                        Icons.mail_outline,
                        'Email',
                        session.email ?? 'Not provided',
                      ),
                      _ProfileRow(
                        Icons.phone_outlined,
                        'Phone',
                        session.phone ?? 'Not provided',
                      ),
                      _ProfileRow(Icons.badge_outlined, 'Role', roleLabel),
                      _ProfileRow(Icons.link, 'API base URL', session.baseUrl),
                    ],
                  ),
                ),
                SizedBox(
                  width: width,
                  child: _CapabilityCard(onboardingBloc: onboardingBloc),
                ),
              ],
            );
          },
        ),
      ],
    );
  }

  String _roleLabel(String role) {
    return switch (role) {
      'super_admin' => 'Super admin',
      'marketing_manager' => 'Marketing manager',
      'fleet_manager' => 'Fleet manager',
      'admin' => 'Admin',
      _ => role,
    };
  }
}

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({required this.name, required this.role});

  final String name;
  final String role;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 72,
          height: 72,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: OnboardColors.gradient,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: OnboardColors.primary.withValues(alpha: 0.24),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Text(
            _initials(name),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name, style: Theme.of(context).textTheme.headlineMedium),
              const SizedBox(height: 6),
              Chip(
                avatar: const Icon(Icons.verified_user_outlined, size: 18),
                label: Text(role),
              ),
            ],
          ),
        ),
      ],
    );
  }

  static String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((e) => e.isNotEmpty);
    final chars = parts.take(2).map((e) => e[0].toUpperCase()).join();
    return chars.isEmpty ? 'OP' : chars;
  }
}

class _ProfileActions extends StatelessWidget {
  const _ProfileActions({
    required this.loading,
    required this.onRefresh,
    required this.onLogout,
  });

  final bool loading;
  final VoidCallback onRefresh;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
      children: [
        GlassButton(
          label: 'Refresh session',
          icon: Icons.refresh,
          loading: loading,
          onPressed: loading ? null : onRefresh,
          compact: true,
        ),
        const SizedBox(height: 10),
        OutlinedButton.icon(
          onPressed: loading ? null : onLogout,
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
        ),
      ],
    );
  }
}

class _CapabilityCard extends StatelessWidget {
  const _CapabilityCard({required this.onboardingBloc});

  final OnboardingBloc onboardingBloc;

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const OnboardSectionTitle(
            title: 'Permissions',
            subtitle: 'Workspace capabilities available to this role.',
          ),
          const SizedBox(height: 14),
          _CapabilityRow(
            icon: Icons.storefront_outlined,
            label: 'Restaurant onboarding',
            enabled: onboardingBloc.canRestaurants,
          ),
          _CapabilityRow(
            icon: Icons.two_wheeler_outlined,
            label: 'Driver onboarding',
            enabled: onboardingBloc.canDrivers,
          ),
          _CapabilityRow(
            icon: Icons.analytics_outlined,
            label: 'KPI dashboard',
            enabled: true,
          ),
          _CapabilityRow(
            icon: Icons.table_rows_outlined,
            label: 'Pipeline review',
            enabled: true,
          ),
        ],
      ),
    );
  }
}

class _CapabilityRow extends StatelessWidget {
  const _CapabilityRow({
    required this.icon,
    required this.label,
    required this.enabled,
  });

  final IconData icon;
  final String label;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final color = enabled ? OnboardColors.success : OnboardColors.muted;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
          Chip(label: Text(enabled ? 'Enabled' : 'Restricted')),
        ],
      ),
    );
  }
}

class _DetailsCard extends StatelessWidget {
  const _DetailsCard({required this.title, required this.rows});

  final String title;
  final List<_ProfileRow> rows;

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          OnboardSectionTitle(
            title: title,
            subtitle: 'Current signed-in session.',
          ),
          const SizedBox(height: 14),
          for (final row in rows) row,
        ],
      ),
    );
  }
}

class _ProfileRow extends StatelessWidget {
  const _ProfileRow(this.icon, this.label, this.value);

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: OnboardColors.primary, size: 20),
          const SizedBox(width: 10),
          SizedBox(
            width: 104,
            child: Text(
              label,
              style: const TextStyle(
                color: OnboardColors.muted,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }
}
