import 'package:flutter/material.dart';

import '../blocs/auth_bloc.dart';
import '../config/api_constants.dart';
import '../services/form_helpers.dart';
import '../theme/onboard_theme.dart';
import '../widgets/onboard_chrome.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.bloc});

  final AuthBloc bloc;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final form = GlobalKey<FormState>();
  final email = TextEditingController(text: 'admin@example.com');
  final password = TextEditingController(text: 'password');
  final baseUrl = TextEditingController(text: ApiConstants.baseUrl);
  String role = 'admin';

  Future<void> login() async {
    if (!form.currentState!.validate()) return;
    try {
      await widget.bloc.login(
        baseUrl: baseUrl.text,
        email: email.text,
        password: password.text,
        role: role,
      );
    } catch (e) {
      if (mounted) showToast(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width > 900;
    return AnimatedBuilder(
      animation: widget.bloc,
      builder: (context, _) => OnboardScaffold(
        body: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 1120),
              child: wide
                  ? Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Expanded(
                          flex: 11,
                          child: _BrandPanel(wide: true),
                        ),
                        const SizedBox(width: 24),
                        Expanded(flex: 9, child: _buildLoginPanel()),
                      ],
                    )
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const _BrandPanel(wide: false),
                        const SizedBox(height: 18),
                        _buildLoginPanel(),
                      ],
                    ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLoginPanel() {
    return GlassPanel(
      padding: const EdgeInsets.all(28),
      child: Form(
        key: form,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Sign in', style: Theme.of(context).textTheme.headlineMedium),
            const SizedBox(height: 8),
            const Text(
              'Use an admin, marketing manager, or fleet manager account.',
            ),
            const SizedBox(height: 24),
            TextFormField(
              controller: baseUrl,
              decoration: const InputDecoration(
                labelText: 'API base URL',
                prefixIcon: Icon(Icons.link),
              ),
              validator: requiredText,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: email,
              decoration: const InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.mail_outline),
              ),
              validator: requiredText,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: password,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Password',
                prefixIcon: Icon(Icons.lock_outline),
              ),
              validator: requiredText,
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: role,
              decoration: const InputDecoration(
                labelText: 'Role',
                prefixIcon: Icon(Icons.badge_outlined),
              ),
              items: const [
                DropdownMenuItem(value: 'admin', child: Text('Admin')),
                DropdownMenuItem(
                  value: 'super_admin',
                  child: Text('Super admin'),
                ),
                DropdownMenuItem(
                  value: 'marketing_manager',
                  child: Text('Marketing manager'),
                ),
                DropdownMenuItem(
                  value: 'fleet_manager',
                  child: Text('Fleet manager'),
                ),
              ],
              onChanged: (value) => setState(() => role = value ?? 'admin'),
            ),
            const SizedBox(height: 24),
            GlassButton(
              onPressed: widget.bloc.loading ? null : login,
              loading: widget.bloc.loading,
              icon: Icons.login,
              label: 'Continue',
            ),
          ],
        ),
      ),
    );
  }
}

class _BrandPanel extends StatelessWidget {
  const _BrandPanel({required this.wide});

  final bool wide;

  @override
  Widget build(BuildContext context) {
    return GlassPanel(
      padding: EdgeInsets.all(wide ? 36 : 26),
      child: SizedBox(
        height: wide ? 520 : 300,
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: OnboardColors.gradient,
            borderRadius: BorderRadius.circular(18),
          ),
          child: Stack(
            children: [
              Positioned(
                right: -54,
                top: -58,
                child: Icon(
                  Icons.delivery_dining,
                  size: wide ? 220 : 150,
                  color: Colors.white.withValues(alpha: 0.12),
                ),
              ),
              Positioned(
                left: 26,
                right: 26,
                bottom: 26,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 58,
                      height: 58,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.16),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.24),
                        ),
                      ),
                      child: const Icon(
                        Icons.fact_check_outlined,
                        color: Colors.white,
                        size: 30,
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Swado Onboard',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: wide ? 42 : 30,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Partner acquisition, fleet growth, KPI review, and clean admin-panel handoff in one operations console.',
                      style: TextStyle(
                        color: Color(0xFFE8F0FF),
                        fontSize: 16,
                        height: 1.45,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 20),
                    const Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: [
                        _Pill(icon: Icons.storefront, label: 'Restaurants'),
                        _Pill(icon: Icons.two_wheeler, label: 'Drivers'),
                        _Pill(icon: Icons.analytics_outlined, label: 'KPIs'),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: Colors.white),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}
