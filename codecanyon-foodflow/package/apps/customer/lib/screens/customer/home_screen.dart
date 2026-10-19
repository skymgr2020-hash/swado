import 'package:flutter/material.dart';

import 'home_experience.dart';
import 'home_screen_production.dart';
import 'home_v2/home_screen_v2.dart';
import 'theatre/theatre_entry_bubble.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        ValueListenableBuilder<bool>(
          valueListenable: homeV2Enabled,
          builder: (context, useV2, _) {
            return useV2
                ? const CustomerHomeScreenV2()
                : const CustomerHomeScreenProduction();
          },
        ),
        // Movie-theatre in-seat ordering. Appears ONLY once the customer is
        // detected inside a theatre's geofence, so it costs nothing on the
        // home screen the rest of the time.
        const TheatreEntryBubble(),
      ],
    );
  }
}
