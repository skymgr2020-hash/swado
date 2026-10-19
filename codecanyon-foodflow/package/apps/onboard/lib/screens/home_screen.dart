import 'package:flutter/material.dart';

import '../blocs/auth_bloc.dart';
import '../blocs/onboarding_bloc.dart';
import '../theme/onboard_theme.dart';
import '../widgets/onboard_chrome.dart';
import 'dashboard_screen.dart';
import 'driver_wizard_screen.dart';
import 'pipeline_screen.dart';
import 'profile_screen.dart';
import 'restaurant_wizard_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.authBloc});

  final AuthBloc authBloc;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late final OnboardingBloc onboardingBloc;
  int selected = 0;

  @override
  void initState() {
    super.initState();
    onboardingBloc = OnboardingBloc(widget.authBloc.session!)..refresh();
  }

  @override
  void dispose() {
    onboardingBloc.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width >= 980;
    final pages = [
      DashboardScreen(bloc: onboardingBloc),
      RestaurantWizardScreen(bloc: onboardingBloc),
      DriverWizardScreen(bloc: onboardingBloc),
      PipelineScreen(bloc: onboardingBloc),
      ProfileScreen(authBloc: widget.authBloc, onboardingBloc: onboardingBloc),
    ];
    final titles = [
      'Onboarding dashboard',
      'Restaurant wizard',
      'Driver wizard',
      'Pipeline',
      'Profile',
    ];

    return AnimatedBuilder(
      animation: onboardingBloc,
      builder: (context, _) => OnboardScaffold(
        appBar: GlassAppBar(
          title: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  gradient: OnboardColors.gradient,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.fact_check_outlined,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Flexible(child: Text(titles[selected])),
            ],
          ),
          actions: [
            IconButton(
              onPressed: onboardingBloc.refresh,
              icon: const Icon(Icons.refresh),
              tooltip: 'Refresh',
            ),
            if (wide)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 10),
                child: Center(
                  child: Chip(
                    avatar: const Icon(Icons.person_outline, size: 18),
                    label: Text(widget.authBloc.session!.name),
                  ),
                ),
              ),
            IconButton(
              onPressed: widget.authBloc.logout,
              icon: const Icon(Icons.logout),
              tooltip: 'Sign out',
            ),
            const SizedBox(width: 8),
          ],
        ),
        body: Row(
          children: [
            if (wide)
              _DesktopRail(
                selected: selected,
                onSelected: (i) => setState(() => selected = i),
              ),
            Expanded(child: pages[selected]),
          ],
        ),
        bottomNavigationBar: wide
            ? null
            : GlassBottomBar(
                child: NavigationBar(
                  selectedIndex: selected,
                  onDestinationSelected: (i) => setState(() => selected = i),
                  destinations: bottomDestinations,
                ),
              ),
      ),
    );
  }
}

class _DesktopRail extends StatelessWidget {
  const _DesktopRail({required this.selected, required this.onSelected});

  final int selected;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 112,
      margin: const EdgeInsets.fromLTRB(16, 16, 0, 16),
      child: GlassPanel(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: NavigationRail(
          backgroundColor: Colors.transparent,
          elevation: 0,
          selectedIndex: selected,
          onDestinationSelected: onSelected,
          labelType: NavigationRailLabelType.all,
          indicatorColor: OnboardColors.primary.withValues(alpha: 0.14),
          selectedIconTheme: const IconThemeData(color: OnboardColors.primary),
          unselectedIconTheme: const IconThemeData(color: OnboardColors.muted),
          selectedLabelTextStyle: const TextStyle(
            color: OnboardColors.primary,
            fontWeight: FontWeight.w900,
          ),
          unselectedLabelTextStyle: const TextStyle(
            color: OnboardColors.muted,
            fontWeight: FontWeight.w600,
          ),
          destinations: navDestinations,
        ),
      ),
    );
  }
}

const navDestinations = [
  NavigationRailDestination(
    icon: Icon(Icons.dashboard_outlined),
    selectedIcon: Icon(Icons.dashboard),
    label: Text('Dashboard'),
  ),
  NavigationRailDestination(
    icon: Icon(Icons.storefront_outlined),
    selectedIcon: Icon(Icons.storefront),
    label: Text('Restaurant'),
  ),
  NavigationRailDestination(
    icon: Icon(Icons.two_wheeler_outlined),
    selectedIcon: Icon(Icons.two_wheeler),
    label: Text('Driver'),
  ),
  NavigationRailDestination(
    icon: Icon(Icons.table_rows_outlined),
    selectedIcon: Icon(Icons.table_rows),
    label: Text('Pipeline'),
  ),
  NavigationRailDestination(
    icon: Icon(Icons.person_outline),
    selectedIcon: Icon(Icons.person),
    label: Text('Profile'),
  ),
];

const bottomDestinations = [
  NavigationDestination(
    icon: Icon(Icons.dashboard_outlined),
    selectedIcon: Icon(Icons.dashboard),
    label: 'Dashboard',
  ),
  NavigationDestination(
    icon: Icon(Icons.storefront_outlined),
    selectedIcon: Icon(Icons.storefront),
    label: 'Restaurant',
  ),
  NavigationDestination(
    icon: Icon(Icons.two_wheeler_outlined),
    selectedIcon: Icon(Icons.two_wheeler),
    label: 'Driver',
  ),
  NavigationDestination(
    icon: Icon(Icons.table_rows_outlined),
    selectedIcon: Icon(Icons.table_rows),
    label: 'Pipeline',
  ),
  NavigationDestination(
    icon: Icon(Icons.person_outline),
    selectedIcon: Icon(Icons.person),
    label: 'Profile',
  ),
];
