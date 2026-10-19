import 'package:flutter/material.dart';

import '../blocs/onboarding_bloc.dart';
import '../widgets/application_tile.dart';
import '../widgets/onboard_chrome.dart';

class PipelineScreen extends StatelessWidget {
  const PipelineScreen({super.key, required this.bloc});

  final OnboardingBloc bloc;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: bloc.refresh,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          GlassPanel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const OnboardSectionTitle(
                  title: 'Pipeline',
                  subtitle:
                      'All restaurant and driver onboarding applications.',
                ),
                const SizedBox(height: 12),
                if (bloc.pipeline.isEmpty)
                  const ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Icon(Icons.inbox_outlined),
                    title: Text('No applications found'),
                  ),
                for (final raw in bloc.pipeline)
                  ApplicationTile(item: (raw as Map).cast<String, dynamic>()),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
