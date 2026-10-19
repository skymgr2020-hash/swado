import 'package:flutter/material.dart';

import 'blocs/auth_bloc.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'theme/onboard_theme.dart';
import 'widgets/onboard_chrome.dart';

void main() => runApp(const OnboardApp());

class OnboardApp extends StatefulWidget {
  const OnboardApp({super.key});

  @override
  State<OnboardApp> createState() => _OnboardAppState();
}

class _OnboardAppState extends State<OnboardApp> {
  late final AuthBloc authBloc;

  @override
  void initState() {
    super.initState();
    authBloc = AuthBloc()..restore();
  }

  @override
  void dispose() {
    authBloc.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Swado Onboard',
      theme: OnboardTheme.build(),
      home: AnimatedBuilder(
        animation: authBloc,
        builder: (context, _) {
          if (authBloc.booting) {
            return const OnboardScaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }

          return authBloc.session == null
              ? LoginScreen(bloc: authBloc)
              : HomeScreen(authBloc: authBloc);
        },
      ),
    );
  }
}
