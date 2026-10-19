import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:onboard/main.dart';

void main() {
  testWidgets('shows onboarding login screen', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const OnboardApp());
    await tester.pump();
    await tester.pump();

    expect(find.text('Sign in'), findsOneWidget);
    expect(find.text('API base URL'), findsOneWidget);
    expect(find.text('Continue'), findsOneWidget);
  });
}
